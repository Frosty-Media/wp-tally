<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally;

use function delete_transient;
use function function_exists;
use function get_transient;
use function is_array;
use function plugins_api;
use function round;
use function set_transient;
use function sprintf;
use function strcmp;
use function themes_api;
use function usort;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get a transient name key.
 * @param string $username
 * @param string $type
 * @return string
 */
function getTransientName(string $username, string $type = 'plugins'): string
{
    return sprintf('wp-tally-user-%s_%s', $username, $type);
}

/**
 * Get a users plugin data.
 * @param false|string $username The user to check
 * @param bool $force Forcibly remove any existing transient and requery
 * @return object|false $plugins The users plugins
 */
function maybeGetPlugins(false|string $username = false, bool $force = false): object|false
{
    if (!$username) {
        return false;
    }

    if (!function_exists('plugins_api')) {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    }

    if ($force) {
        delete_transient(getTransientName($username));
    }

    $plugins = get_transient(getTransientName($username));
    if (!$plugins) {
        $plugins = plugins_api(
            'query_plugins',
            [
                'author' => $username,
                'per_page' => 999,
                'fields' => [
                    'downloaded' => true,
                    'description' => false,
                    'short_description' => false,
                    'donate_link' => false,
                    'tags' => false,
                    'sections' => false,
                    'added' => true,
                    'last_updated' => true,
                    'active_installs' => true,
                ],
            ]
        );

        set_transient(getTransientName($username), $plugins, DAY_IN_SECONDS);
    }

    return (object)$plugins;
}

/**
 * Get a users theme data.
 * @param false|string $username The user to check
 * @param bool $force Forcibly remove any existing transient and requery
 * @return object|false $plugins The users plugins
 */
function maybeGetThemes(false|string $username = false, bool $force = false): object|false
{
    if (!$username) {
        return false;
    }
    if (!function_exists('themes_api')) {
        require_once ABSPATH . 'wp-admin/includes/theme.php';
    }

    if ($force) {
        delete_transient(getTransientName($username, 'themes'));
    }

    $themes = get_transient(getTransientName($username, 'themes'));
    if (!$themes) {
        $themes = [];
        $theme_list = themes_api(
            'query_themes',
            [
                'author' => $username,
                'per_page' => 999,
            ]
        );

        foreach ($theme_list->themes as $data) {
            $themes[] = (array)themes_api(
                'theme_information',
                [
                    'slug' => $data->slug,
                    'fields' => [
                        'downloaded' => true,
                        'description' => false,
                        'short_description' => false,
                        'tags' => false,
                        'sections' => false,
                        'last_updated' => true,
                        'ratings' => true,
                    ],
                ]
            );
        }

        set_transient(getTransientName($username, 'themes'), $themes, DAY_IN_SECONDS);
    }

    return (object)$themes;
}

/**
 * Get the actual rating for a given plugin.
 * @param int $num_ratings The total number of ratings
 * @param mixed $ratings The actual rating counts
 * @return float|int $rating The calculated rating
 * @since 1.0.0
 */
function getRating(int $num_ratings, mixed $ratings): float|int
{
    if ($num_ratings <= 0) {
        return 0;
    }

    if (is_array($ratings)) {
        $rating = ($ratings[5] > 0 ? $ratings[5] * 5 : 0);
        $rating += $ratings[4] > 0 ? $ratings[4] * 4 : 0;
        $rating += $ratings[3] > 0 ? $ratings[3] * 3 : 0;
        $rating += $ratings[2] > 0 ? $ratings[2] * 2 : 0;
        $rating += $ratings[1] > 0 ? $ratings[1] * 1 : 0;
        $rating = round($rating / $num_ratings, 1);
    } elseif ($ratings > 0 && $ratings < 10) {
        $rating = 0.5;
    } elseif ($ratings >= 10 && $ratings < 20) {
        $rating = 1;
    } elseif ($ratings >= 20 && $ratings < 30) {
        $rating = 1.5;
    } elseif ($ratings >= 30 && $ratings < 40) {
        $rating = 2;
    } elseif ($ratings >= 40 && $ratings < 50) {
        $rating = 2.5;
    } elseif ($ratings >= 50 && $ratings < 60) {
        $rating = 3;
    } elseif ($ratings >= 60 && $ratings < 70) {
        $rating = 3.5;
    } elseif ($ratings >= 70 && $ratings < 80) {
        $rating = 4;
    } elseif ($ratings >= 80 && $ratings < 90) {
        $rating = 4.5;
    } elseif ($ratings >= 90) {
        $rating = 5;
    }

    return $rating ?? 0;
}

/**
 * Sort themes/plugins/
 * @param array $items The themes or plugins to sort
 * @param string $order_by The field to sort by
 * @param string $sort The direction to sort
 * @return array $items The sorted items
 * @since 1.2.0
 */
function sort(array $items, string $order_by, string $sort): array
{
    if ($order_by === 'downloaded') {
        if ($sort === 'desc') {
            usort($items, fn($a, $b) => $b['downloaded'] - $a['downloaded']);
        } else {
            usort($items, fn($a, $b) => $a['downloaded'] - $b['downloaded']);
        }
    } else {
        if ($sort === 'desc') {
            usort($items, fn($a, $b) => strcmp((string)$b['slug'], (string)$a['slug']));
        } else {
            usort($items, fn($a, $b) => strcmp((string)$a['slug'], (string)$b['slug']));
        }
    }

    return $items;
}
