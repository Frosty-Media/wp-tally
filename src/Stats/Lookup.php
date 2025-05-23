<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Stats;

use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\AbstractSingletonProvider;
use function admin_url;
use function filter_var;
use function get_plugin_data;
use function in_array;
use function sanitize_text_field;
use function sprintf;
use function update_option;
use function wp_get_referer;
use function wp_kses_post;
use function wp_safe_redirect;
use function wpautop;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * Class Lookup
 * @package FrostyMedia\WpTally\Stats
 */
class Lookup extends AbstractSingletonProvider
{
    public const string OPTION = '_wptally_stats';
    public const string VIEW_API = 'api';
    public const string VIEW_SHORTCODE = 'shortcode';
    public const string TOTAL_COUNT = 'total_count';
    public const string USERS = 'users';
    public const string USERS_COUNT = 'count';
    public const string USERS_VIEW = 'view';
    protected const string ACTION = 'wptally_upgrade';
    protected const string VERSION = 'db_version';

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('admin_notices', [$this, 'adminNotice']);
        $this->addAction('admin_post_' . self::ACTION, [$this, 'upgradeOption']);
    }

    /**
     * Get the option.
     * @return array
     */
    public static function getOption(): array
    {
        return get_option(self::OPTION, self::getDefault());
    }

    /**
     * Get the total count.
     * @return int
     */
    public static function getTotalCount(): int
    {
        return absint(self::getOption()[self::TOTAL_COUNT]);
    }

    /**
     * Update the total count.
     */
    public static function updateCount(): void
    {
        $option = self::getOption();
        $option[self::TOTAL_COUNT]++;
        self::updateOption($option);
    }

    /**
     * Update the current users count.
     * @param string $username The requested .org user
     * @param string $view The view type
     */
    public static function updateUser(string $username, string $view = self::VIEW_API): void
    {
        $option = self::getOption();
        // Set up the current requested user total count.
        if (!isset($option[self::USERS][$username][self::TOTAL_COUNT])) {
            $option[self::USERS][$username][self::TOTAL_COUNT] = 0;
        }
        // Set up the current requested user view count stat.
        if (!isset($option[self::USERS][$username][self::USERS_VIEW])) {
            $option[self::USERS][$username][self::USERS_VIEW] = [
                self::VIEW_API => [],
                self::VIEW_SHORTCODE => [],
            ];
        }
        // Increment the total count of the requested user.
        $option[self::USERS][$username][self::TOTAL_COUNT]++;
        $ip = self::getIpAddress();
        // Increment the count by client (IP) of the requested user for the view type.
        $count = $option[self::USERS][$username][self::USERS_VIEW][$view][$ip] ?? 0;
        $option[self::USERS][$username][self::USERS_VIEW][$view][$ip] = ++$count;
        self::updateOption($option);
    }

    /**
     * Admin notice to run our option update.
     */
    protected function adminNotice(): void
    {
        $option = self::getOption();
        $db_version = $option[self::VERSION] ?? null;
        $upgrades = ['2.1.1'];
        if ($db_version === null || in_array($db_version, $upgrades, true)) {
            $message = sprintf(
                'WP Tally requires an update. Please <a href="%s">click here</a>.',
                esc_url(add_query_arg('action', self::ACTION, admin_url('admin-post.php')))
            );
            echo wp_kses_post(sprintf('<div class="notice">%s</div>', wpautop($message)));
        }
    }

    /**
     * Maybe trigger an option upgrade.
     */
    protected function upgradeOption(): never
    {
        $option = self::getOption();
        $current_version = get_plugin_data($this->getPlugin()->getFile(), translate: false)['Version'];
        $db_version = $option[self::VERSION] ?? null;
        $ip = self::getIpAddress();
        // Version 2.1.0 (Added db_version to options).
        if ($db_version === null) {
            $option[self::VERSION] = '2.0.0';
            self::updateOption($option);
            $this->upgradeOption();
        }
        // Version 2.2.0 (DB options structure change).
        if ($db_version <= '2.1.1') {
            $users = $option[self::USERS] ?? [];
            $_users = [];
            foreach ($users as $username => $data) {
                $_users[$username][self::TOTAL_COUNT] = $data[self::USERS_COUNT];
                if (isset($data[self::USERS_VIEW])) {
                    foreach ($data[self::USERS_VIEW] as $view => $count) {
                        $_users[$username][self::USERS_VIEW][$view][$ip] = $count;
                    }
                }
            }
            $option[self::USERS] = $_users;
            $option[self::VERSION] = $current_version;
            self::updateOption($option);
        }

        wp_safe_redirect(wp_get_referer() ? wp_get_referer() : admin_url());
        exit;
    }

    /**
     * Retrieve the current client's IP address.
     * @return string
     */
    private static function getIpAddress(): string
    {
        $request = Request::createFromGlobals();

        $ip = $request->server->get(
            'HTTP_CLIENT_IP',
            $request->server->get(
                'HTTP_CF_CONNECTING_IP',
                $request->server->get(
                    'HTTP_X_FORWARDED',
                    $request->server->get(
                        'HTTP_X_FORWARDED_FOR',
                        $request->server->get(
                            'HTTP_FORWARDED',
                            $request->server->get(
                                'HTTP_FORWARDED_FOR',
                                $request->server->get('REMOTE_ADDR')
                            )
                        )
                    )
                )
            )
        );

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return 'Unknown';
        }

        return sanitize_text_field($ip);
    }

    /**
     * Update the option.
     * @param array $option
     */
    private static function updateOption(array $option): void
    {
        update_option(self::OPTION, $option);
    }

    /**
     * The default option array model.
     * @return array
     */
    private static function getDefault(): array
    {
        return [
            self::TOTAL_COUNT => 0,
            self::USERS => [],
            self::VERSION => get_plugin_data(self::getInstance()->getPlugin()->getFile(), translate: false)['Version'],
        ];
    }
}
