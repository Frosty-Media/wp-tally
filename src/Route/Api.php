<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use function add_rewrite_endpoint;
use function apply_filters;
use function delete_transient;
use function filter_var;
use function FrostyMedia\WpTally\getRating;
use function FrostyMedia\WpTally\getTransientName;
use function FrostyMedia\WpTally\maybeGetPlugins;
use function FrostyMedia\WpTally\maybeGetThemes;
use function FrostyMedia\WpTally\sort;
use function get_option;
use function sanitize_user;
use function session_write_close;
use function strtolower;
use function update_option;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Api.
 */
class Api implements WpHooksInterface
{

    use HooksTrait;

    /**
     * Data.
     * @var array $data
     */
    private array $data = [];

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
        $this->addAction('template_redirect', [$this, 'processQuery'], -1);
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
            $this->render();
        }

        $username = sanitize_user($query_vars[self::getQueryVar()]);
        $lookup_count = get_option('wptally_lookups', 0);
        $lookup_count += $lookup_count;
        update_option('wptally_lookups', (int)$lookup_count);

        if (filter_var($query_vars['force'], FILTER_VALIDATE_BOOLEAN)) {
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
        $data['info'] = [
            'user' => $username,
            'profile' => sprintf('https://profiles.wordpress.org/%s', $username),
        ];

        $plugins = maybeGetPlugins($username, isset($force));

        if (!$plugins || is_wp_error($plugins)) {
            $data['plugins'] = [
                'error' => sprintf('An error occurred with the plugins API: %s', $plugins->get_error_message()),
            ];
        } else {
            // How many plugins does the user have?
            $count = count($plugins->getPlugins());
            $total_downloads = 0;

            if ($count === 0) {
                $data['plugins'] = [
                    'error' => sprintf('No plugins found for %s.', $username),
                ];
            } else {
                // Maybe sort plugins
                $plugins = sort($plugins->getPlugins(), $order_by, $sort);

                foreach ($plugins as $plugin) {
                    $data['plugins'][$plugin->getSlug()] = [
                        'name' => $plugin->getName(),
                        'url' => sprintf('https://wordpress.org/plugins/%s', $plugin->getSlug()),
                        'version' => $plugin->getVersion(),
                        'added' => $plugin->getAdded(),
                        'updated' => $plugin->getLastUpdated(),
                        'rating' => getRating($plugin->getNumRatings(), $plugin->getRating()),
                        'downloads' => $plugin->getDownloaded(),
                        'installs' => $plugin->getActiveInstalls(),
                    ];

                    $total_downloads += $plugin->getDownloaded();
                }

                $data['info']['plugin_count'] = $count;
                $data['info']['total_plugin_downloads'] = $total_downloads;
            }
        }

        $themes = maybeGetThemes($username, isset($force));

        if (is_wp_error($themes)) {
            $data['themes'] = [
                'error' => sprintf('An error occurred with the themes API: %s', $themes->get_error_message()),
            ];
        } else {
            // How many plugins does the user have?
            $count = count((array)$themes);
            $total_downloads = 0;

            if ($count === 0) {
                $data['themes'] = [
                    'error' => sprintf('No themes found for %s.', $username),
                ];
            } else {
                // Maybe sort themes
                $themes = sort((array)$themes, $order_by, $sort);

                foreach ($themes as $theme) {
                    $rating = getRating($theme['num_ratings'], $theme['rating']);

                    $data['themes'][$theme['slug']] = [
                        'name' => $theme['name'],
                        'url' => 'https://wordpress.org/themes/' . $theme['slug'],
                        'version' => $theme['version'],
                        'updated' => date('d M, Y', strtotime((string)$theme['last_updated'])),
                        'rating' => $rating,
                        'downloads' => $theme['downloaded'],
                    ];

                    $total_downloads += $theme['downloaded'];
                }

                $data['info']['theme_count'] = $count;
                $data['info']['total_theme_downloads'] = $total_downloads;
            }
        }

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

    protected static function getQueryVar(): string
    {
        return apply_filters('frosty_media_wp_tally_query_var', 'wp-tally');
    }

    /**
     * Render and encode.
     * @param int $status_code The status code to return
     * @return never
     */
    private function render(int $status_code = Response::HTTP_OK): never
    {
        (new JsonResponse($this->data, $status_code))->send();
        session_write_close();
        exit;
    }
}
