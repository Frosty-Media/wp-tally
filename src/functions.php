<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally;

use FrostyMedia\WpTally\Models\Plugins\Api as PluginsApi;
use FrostyMedia\WpTally\Models\Plugins\Plugin;
use FrostyMedia\WpTally\Models\Themes\Api as ThemesApi;
use FrostyMedia\WpTally\Models\Themes\Theme;
use FrostyMedia\WpTally\Route\Api;
use WP_Error;
use WP_Http;
use function delete_transient;
use function esc_url_raw;
use function function_exists;
use function get_transient;
use function hash;
use function home_url;
use function is_wp_error;
use function json_decode;
use function json_last_error;
use function plugins_api;
use function round;
use function set_transient;
use function sprintf;
use function strcmp;
use function TheFrosty\WpUtilities\getIpAddress;
use function themes_api;
use function trailingslashit;
use function usort;
use function wp_remote_retrieve_body;
use function wp_remote_retrieve_header;
use function wp_remote_retrieve_response_code;
use function wp_safe_remote_get;
use const JSON_ERROR_NONE;
use const JSON_THROW_ON_ERROR;

/**
 * Get the Tally API endpoint URL.
 * @return string
 */
function getTallyUrl(): string
{
    return trailingslashit(home_url(Api::getQueryVar()));
}

/**
 * Get the tally.
 * @param string $username
 * @return object|null
 * @throws \JsonException
 */
function getTally(string $username): ?object
{
    $url = sprintf('%s%s', getTallyUrl(), $username);
    $response = wp_safe_remote_get(
        esc_url_raw($url),
        [
            'headers' => [
                'Accept' => 'application/json',
                'Referer' => Api::getHttpReferrer(),
                'X-Client-IP' => getIpAddress(),
            ],
        ]
    );

    if (
        is_wp_error($response) ||
        wp_remote_retrieve_response_code($response) !== WP_Http::OK ||
        !str_contains(wp_remote_retrieve_header($response, 'content-type'), 'json')
    ) {
        return null;
    }

    $tally = json_decode(wp_remote_retrieve_body($response), false, flags: JSON_THROW_ON_ERROR);
    return json_last_error() === JSON_ERROR_NONE ? $tally : null;
}

/**
 * Get the tally, and cache the results.
 * @param string $username
 * @param string|null $transient_key
 * @param int|null $expiration
 * @return object|null
 * @throws \JsonException
 */
function getTallyCached(string $username, ?string $transient_key = null, ?int $expiration = null): ?object
{
    $url = sprintf('%s%s', getTallyUrl(), $username);
    $transient_key ??= sprintf('%s_%s', __FUNCTION__, hash('sha256', $url));
    $expiration ??= WEEK_IN_SECONDS;

    $response = get_transient($transient_key);
    if ($response === false) {
        $response = getTally($username);
        if ($response) {
            set_transient($transient_key, $response, $expiration);
        }
    }

    return $response;
}

/**
 * Get a transient name key.
 * @param string $username
 * @param string $type
 * @return string
 */
function getTransientName(string $username, string $type = 'plugins'): string
{
    return sprintf('wp-tally-user-%s_%s', sanitize_user($username), $type);
}

/**
 * Get a users plugin data.
 * @param string $username The user to check
 * @param bool $force Forcibly remove any existing cache.
 * @return PluginsApi|WP_Error
 */
function maybeGetPlugins(string $username, bool $force = false): PluginsApi|WP_Error
{
    if (!function_exists('plugins_api')) {
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    }

    if ($force) {
        delete_transient(getTransientName($username));
    }

    $plugins = get_transient(getTransientName($username));
    if (!$plugins instanceof PluginsApi) {
        $plugins = plugins_api(
            'query_plugins',
            [
                'author' => $username,
                'per_page' => 99,
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

        if ($plugins instanceof WP_Error) {
            // Cache the error for one minute as a sort of limit.
            set_transient(getTransientName($username), $plugins, MINUTE_IN_SECONDS);
            return $plugins;
        }

        $plugins = new PluginsApi((array)$plugins);
        if ($plugins->getInfo()->getResults() === 0) {
            $expiration = MONTH_IN_SECONDS;
        }
        set_transient(getTransientName($username), $plugins, $expiration ?? DAY_IN_SECONDS);
    }

    return $plugins;
}

/**
 * Get a users theme data.
 * @param string $username The user to check
 * @param bool $force Forcibly remove any existing cache.
 * @return ThemesApi|WP_Error
 */
function maybeGetThemes(string $username, bool $force = false): ThemesApi|WP_Error
{
    if (!function_exists('themes_api')) {
        require_once ABSPATH . 'wp-admin/includes/theme.php';
    }

    if ($force) {
        delete_transient(getTransientName($username, 'themes'));
    }

    $themes = get_transient(getTransientName($username, 'themes'));
    if (!$themes instanceof ThemesApi) {
        $themes = [];
        $theme_list = themes_api(
            'query_themes',
            [
                'author' => $username,
                'per_page' => 99,
            ]
        );

        if ($theme_list instanceof WP_Error) {
            // Cache the error for one minute as a sort of limit.
            set_transient(getTransientName($username, 'themes'), $theme_list, MINUTE_IN_SECONDS);
            return $theme_list;
        }

        $themes[ThemesApi::SECTION_INFO] = $theme_list->info ?? [];
        foreach ($theme_list->themes as $data) {
            $themes[ThemesApi::SECTION_THEMES][] = themes_api(
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

        $themes = new ThemesApi($themes);
        if ($themes->getInfo()->getResults() === 0) {
            $expiration = MONTH_IN_SECONDS;
        }
        set_transient(getTransientName($username, 'themes'), $themes, $expiration ?? DAY_IN_SECONDS);
    }

    return $themes;
}

/**
 * Get the actual rating for a given plugin.
 * @param Plugin|Theme $api
 * @return float
 */
function getRating(Plugin|Theme $api): float
{
    if ($api->getNumRatings() === 0) {
        return $api->getRating();
    }

    $ratings = $api->getRatings();
    $rating = $ratings[5] > 0 ? $ratings[5] * 5 : 0;
    $rating += $ratings[4] > 0 ? $ratings[4] * 4 : 0;
    $rating += $ratings[3] > 0 ? $ratings[3] * 3 : 0;
    $rating += $ratings[2] > 0 ? $ratings[2] * 2 : 0;
    $rating += $ratings[1] > 0 ? $ratings[1] * 1 : 0;

    return round($rating / $api->getNumRatings(), 1);
}

/**
 * Sort plugins or themes.
 * @param Plugin[]|Theme[] $items The plugins or themes to sort
 * @param string $order_by The field to sort by
 * @param string $sort The direction to sort
 * @return Plugin[]|Theme[]
 */
function sort(array $items, string $order_by, string $sort): array
{
    if ($order_by === 'downloaded') {
        if ($sort === 'desc') {
            usort($items, static fn(Plugin|Theme $a, Plugin|Theme $b) => $b->getDownloaded() - $a->getDownloaded());
        } else {
            usort($items, static fn(Plugin|Theme $a, Plugin|Theme $b) => $a->getDownloaded() - $b->getDownloaded());
        }
    } elseif ($sort === 'desc') {
        usort($items, static fn(Plugin|Theme $a, Plugin|Theme $b) => strcmp($b->getSlug(), $a->getSlug()));
    } else {
        usort($items, static fn(Plugin|Theme $a, Plugin|Theme $b) => strcmp($a->getSlug(), $b->getSlug()));
    }

    return $items;
}
