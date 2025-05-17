<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Stats;

use function update_option;

/**
 * Class Lookup
 * @package FrostyMedia\WpTally\Stats
 */
class Lookup
{
    public const string OPTION = '_wptally_stats';
    public const string VIEW_API = 'api';
    public const string VIEW_BLOCK = 'block';
    public const string VIEW_SHORTCODE = 'shortcode';
    protected const string TOTAL_COUNT = 'total_count';
    protected const string USERS = 'users';
    protected const string USERS_COUNT = 'count';
    protected const string USERS_VIEW = 'view';

    /**
     * Get the option.
     * @return array
     */
    public function getOption(): array
    {
        return get_option(self::OPTION, $this->getDefault());
    }

    /**
     * Get the total count.
     * @return int
     */
    public function getTotalCount(): int
    {
        return absint($this->getOption()[self::TOTAL_COUNT]);
    }

    /**
     * Update the total count.
     */
    public function updateCount(): void
    {
        $option = $this->getOption();
        $option[self::TOTAL_COUNT]++;
        $this->updateOption($option);
    }

    /**
     * Update the current users count.
     * @param string $username
     * @param string $view
     */
    public function updateUser(string $username, string $view = self::VIEW_API): void
    {
        $option = $this->getOption();
        if (!isset($option[self::USERS][$username][self::USERS_COUNT])) {
            $option[self::USERS][$username][self::USERS_COUNT] = 0;
        }
        if (!isset($option[self::USERS][$username][self::USERS_VIEW])) {
            $option[self::USERS][$username][self::USERS_VIEW] = [
                self::VIEW_API => 0,
                self::VIEW_BLOCK => 0,
                self::VIEW_SHORTCODE => 0,
            ];
        }
        $option[self::USERS][$username][self::USERS_COUNT]++;
        $option[self::USERS][$username][self::USERS_VIEW][$view]++;
        $this->updateOption($option);
    }

    /**
     * Update the option.
     * @param array $option
     */
    private function updateOption(array $option): void
    {
        update_option(self::OPTION, $option);
    }

    /**
     * The default option array model.
     * @return array
     */
    private function getDefault(): array
    {
        return [
            self::TOTAL_COUNT => 0,
            self::USERS => [],
        ];
    }
}
