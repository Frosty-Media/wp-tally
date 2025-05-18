<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Route;

use FrostyMedia\WpTally\Models\Plugins\Api as PluginsApi;
use FrostyMedia\WpTally\Models\Plugins\Plugin;
use FrostyMedia\WpTally\Models\Themes\Api as ThemesApi;
use FrostyMedia\WpTally\Models\Themes\Theme;
use FrostyMedia\WpTally\ServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use WP_Http;
use function add_rewrite_endpoint;
use function apply_filters;
use function delete_transient;
use function filter_var;
use function FrostyMedia\WpTally\getRating;
use function FrostyMedia\WpTally\getTransientName;
use function FrostyMedia\WpTally\maybeGetPlugins;
use function FrostyMedia\WpTally\maybeGetThemes;
use function FrostyMedia\WpTally\sort;
use function sanitize_user;
use function session_write_close;
use function trailingslashit;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * Class Api.
 * @package FrostyMedia\WpTally\Route
 */
class Api extends AbstractContainerProvider
{

    use Limiter;

    public const string HOOK_NAME_DISABLE_API = 'frosty_media_wp_tally_disable_api';
    public const string HOOK_NAME_HTTP_REFERRER = 'frosty_media_wp_tally_http_referrer';

    public const string HOOK_NAME_QUERY_VAR = 'frosty_media_wp_tally_query_var';
    private array $data = [];

    /**
     * Get the HTTP_REFERRER value.
     * @return string
     * @uses apply_filters()
     */
    public static function getHttpReferrer(): string
    {
        return apply_filters(self::HOOK_NAME_HTTP_REFERRER, home_url());
    }

    /**
     * Get the registered "query_var" key.
     * @return string
     * @uses apply_filters()
     */
    public static function getQueryVar(): string
    {
        return apply_filters(self::HOOK_NAME_QUERY_VAR, 'wp-tally');
    }

    /**
     * Does the current WP_Query->query_vars contain our variable (defaults to "wp-tally" (previously "api"))?
     * @return bool
     */
    public static function hasQueryVar(): bool
    {
        global $wp_query;

        return isset($wp_query->query_vars[self::getQueryVar()]);
    }

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('init', [$this, 'addRewriteEndpoint']);
        $this->addAction('parse_query', [$this, 'processQuery'], -1);
        $this->addFilter('query_vars', [$this, 'queryVars']);
    }

    /**
     * Get the data.
     * @return array The output data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the data.
     * @param array $data
     */
    protected function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Register our API endpoint.
     */
    protected function addRewriteEndpoint(): void
    {
        add_rewrite_endpoint(self::getQueryVar(), EP_ALL);
    }

    /**
     * Listen for the API and process requests
     */
    protected function processQuery(): void
    {
        /** @var \Symfony\Component\HttpFoundation\Request $request */
        $request = $this->getContainer()->get(ServiceProvider::REQUEST);
        /**
         * Disable the API if filtered off.
         * @param bool $disable
         */
        if (
            filter_var(apply_filters(self::HOOK_NAME_DISABLE_API, false), FILTER_VALIDATE_BOOLEAN) &&
            $request->server->get('HTTP_REFERER') !== self::getHttpReferrer()
        ) {
            return;
        }
        global $wp_query;
        $query_vars = $wp_query->query_vars;

        // Bail if this isn't an "api" call.
        if (!self::hasQueryVar()) {
            return;
        }

        if (empty($query_vars[self::getQueryVar()])) {
            $this->setData([
                'error' => 'No username specified',
            ]);
            $this->render(WP_Http::BAD_REQUEST);
        }

        $username = sanitize_user($query_vars[self::getQueryVar()]);
        $profile = trailingslashit(sprintf('https://profiles.wordpress.org/%s', $username));
        $head = wp_remote_head($profile);
        if (is_wp_error($head) || (isset($head['response']['code']) && $head['response']['code'] !== WP_Http::OK)) {
            $this->setData([
                'error' => 'No user found',
            ]);
            $this->render(WP_Http::NOT_ACCEPTABLE);
        }

        $limit = $this->rateLimiter(5);
        if (is_wp_error($limit)) {
            $this->setData([
                'error' => $limit->get_error_message(),
            ]);
            $this->render(WP_Http::TOO_MANY_REQUESTS);
        }

        if (isset($query_vars['force']) && filter_var($query_vars['force'], FILTER_VALIDATE_BOOLEAN)) {
            delete_transient(getTransientName($username));
            delete_transient(getTransientName($username, 'themes'));
            $force = true;
        }

        $query = static fn(
            string $key,
            string $value,
            string $default,
            string $fallback
        ): string => isset($query_vars[$key]) && $query_vars[$key] === $value ? $default : $fallback;

        $order_by = $query('order-by', 'downloads', 'downloaded', 'name');
        $sort = $query('sort', 'desc', 'desc', 'asc');

        $data = [];
        $data[PluginsApi::SECTION_INFO] = [
            'user' => $username,
            'profile' => $profile,
        ];

        $plugins = maybeGetPlugins($username, isset($force));

        if (is_wp_error($plugins)) {
            $data[PluginsApi::SECTION_PLUGINS] = [
                'error' => sprintf('An error occurred with the plugins API: %s', $plugins->get_error_message()),
            ];
        } else {
            // How many plugins does the user have?
            $count = count($plugins->getPlugins());
            $total_downloads = 0;

            if ($count === 0) {
                $data[PluginsApi::SECTION_PLUGINS] = [
                    'error' => sprintf('No plugins found for %s.', $username),
                ];
            } else {
                // Maybe sort plugins.
                $plugins = sort($plugins->getPlugins(), $order_by, $sort);

                foreach ($plugins as $plugin) {
                    $slug = $plugin->getSlug();
                    $data[PluginsApi::SECTION_PLUGINS][$slug] = [
                        Plugin::SECTION_NAME => $plugin->getName(),
                        'url' => sprintf('https://wordpress.org/plugins/%s', $slug),
                        Plugin::SECTION_VERSION => $plugin->getVersion(),
                        Plugin::SECTION_ADDED => $plugin->getAdded(),
                        Plugin::SECTION_LAST_UPDATED => $plugin->getLastUpdated(),
                        Plugin::SECTION_RATING => getRating($plugin),
                        Plugin::SECTION_DOWNLOADED => $plugin->getDownloaded(),
                        Plugin::SECTION_ACTIVE_INSTALLS => $plugin->getActiveInstalls(),
                    ];

                    $total_downloads += $plugin->getDownloaded();
                }

                $data[PluginsApi::SECTION_INFO]['plugin_count'] = $count;
                $data[PluginsApi::SECTION_INFO]['total_plugin_downloads'] = $total_downloads;
            }
        }

        $themes = maybeGetThemes($username, isset($force));

        if (is_wp_error($themes)) {
            $data[ThemesApi::SECTION_THEMES] = [
                'error' => sprintf('An error occurred with the themes API: %s', $themes->get_error_message()),
            ];
        } else {
            // How many themes does the user have?
            $count = count($themes->getThemes());
            $total_downloads = 0;

            if ($count === 0) {
                $data[ThemesApi::SECTION_THEMES] = [
                    'error' => sprintf('No themes found for %s.', $username),
                ];
            } else {
                // Maybe sort themes.
                $themes = sort($themes->getThemes(), $order_by, $sort);

                foreach ($themes as $theme) {
                    $slug = $theme->getSlug();
                    $data[ThemesApi::SECTION_THEMES][$slug] = [
                        Theme::SECTION_NAME => $theme->getName(),
                        'url' => sprintf('https://wordpress.org/themes/%s', $slug),
                        Theme::SECTION_VERSION => $theme->getVersion(),
                        Theme::SECTION_LAST_UPDATED => $theme->getLastUpdated(),
                        Theme::SECTION_RATING => getRating($theme),
                        'downloads' => $theme->getDownloaded(),
                    ];

                    $total_downloads += $theme->getDownloaded();
                }

                $data[ThemesApi::SECTION_INFO]['theme_count'] = $count;
                $data[ThemesApi::SECTION_INFO]['total_theme_downloads'] = $total_downloads;
            }
        }

        /** @var \FrostyMedia\WpTally\Stats\Lookup $lookup */
        $lookup = $this->getContainer()->get(ServiceProvider::API);
        $lookup->updateCount();
        $lookup->updateUser($username);

        $this->setData($data);
        $this->render();
    }

    /**
     * Register new query vars.
     * @param array $vars
     * @return array
     */
    protected function queryVars(array $vars): array
    {
        $vars[] = 'username';
        $vars[] = 'order-by';
        $vars[] = 'sort';
        $vars[] = 'force';

        return $vars;
    }

    /**
     * Render and encode.
     * @param int $status_code The status code to return
     * @return never
     */
    private function render(int $status_code = Response::HTTP_OK): never
    {
        (new JsonResponse($this->getData(), $status_code))->send();
        session_write_close();
        exit;
    }
}
