<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Stats;

use function get_plugin_data;
use function TheFrosty\WpUtilities\getIpAddress;
use function update_option;
use const FrostyMedia\WpTally\PLUGIN_FILE;

/**
 * Class Lookup
 * @package FrostyMedia\WpTally\Stats
 */
class Lookup
{
    public const string OPTION = '_wptally_stats';
    public const string TOTAL_COUNT = 'total_count';
    public const string USERS = 'users';
    public const string USERS_VIEW = 'view';
    protected const string ACTION = 'wptally_upgrade';
    protected const string VERSION = 'db_version';

    /**
     * Get the option.
     * @return array
     */
    public static function getOption(): array
    {
        return (array)get_option(self::OPTION, self::getDefault());
    }

    /**
     * Get the total count.
     * @return int
     */
    public static function getTotalCount(): int
    {
        return absint(self::getOption()[self::TOTAL_COUNT] ?? 0);
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
     * @param View $view The view type
     */
    public static function updateUser(string $username, View $view = View::API): void
    {
        $option = self::getOption();
        // Set up the current requested user total count.
        if (!isset($option[self::USERS][$username][self::TOTAL_COUNT])) {
            $option[self::USERS][$username][self::TOTAL_COUNT] = 0;
        }
        // Set up the current requested user view count stat.
        if (!isset($option[self::USERS][$username][self::USERS_VIEW])) {
            $option[self::USERS][$username][self::USERS_VIEW] = [
                View::API->value => [],
                View::SHORTCODE->value => [],
            ];
        }
        // Increment the total count of the requested user.
        $option[self::USERS][$username][self::TOTAL_COUNT]++;
        $ip = getIpAddress();
        // Increment the count by client (IP) of the requested user for the view type.
        $count = $option[self::USERS][$username][self::USERS_VIEW][$view->value][$ip] ?? 0;
        $option[self::USERS][$username][self::USERS_VIEW][$view->value][$ip] = ++$count;
        self::updateOption($option);
    }

    /**
     * Update the option.
     * @param array $option
     */
    public static function updateOption(array $option): void
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
            self::VERSION => get_plugin_data(PLUGIN_FILE, translate: false)['Version'],
        ];
    }
}
