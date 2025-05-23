<?php

declare(strict_types=1);

use FrostyMedia\WpTally\Stats\Lookup;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Api\TransientsTrait;
use function FrostyMedia\WpTally\getRating;
use function FrostyMedia\WpTally\getTransientName;
use function FrostyMedia\WpTally\maybeGetPlugins;
use function FrostyMedia\WpTally\maybeGetThemes;
use function FrostyMedia\WpTally\sort;

/** @phpcs:disable Generic.Files.LineLength.TooLong */

$request ??= Request::createFromGlobals();
$post = $request->request;
$transients = new class {
    use TransientsTrait;
};

$username = $post->get('wpusername');
$active = $post->has('active') && $post->get('active') === 'themes' ? 'themes' : 'plugins';
$order_by = $post->has('by') && $post->get('by') === 'downloads' ? 'downloaded' : 'name';
$sort = $post->has('dir') && $post->get('dir') === 'desc' ? 'desc' : 'asc';

$search = <<<'HTML'
    <div class="tally-search-box">
    <form class="tally-search-form" method="post" action="" autocomplete="off">
    <input class="tally-search-field" type="text" name="wpusername" placeholder="Enter your WordPress.org username" 
    value="%1$s" pattern="\w{1,30}" required data-1p-ignore>
    <input type="submit" class="tally-search-submit" value="Search">
    %2$s
    </form>
    </div>
    HTML;

printf(
    $search,
    sanitize_user($username),
    wp_nonce_field('tally-search-form', '_tally_ho', display: false)
);

$results = '<div class="tally-search-results" id="search-results">';

if (!empty($username)) {
    // Maybe show cache timeout.
    $cache_results = static function (string $type) use ($transients, $username): string {
        $timeout = $transients->getTransientTimeout(getTransientName($username, $type));
        $html = '';
        if ($timeout) {
            $html = '<div class="tally-cache-results"><div>';
            $html .= sprintf(
                '<span class="tally-cache-results-title">Cached until: </span><time datetime="%1$s" title="%3$s">%2$s</time>',
                esc_attr(date_i18n('c', $timeout)),
                esc_html(date_i18n('r', $timeout)),
                esc_attr(sprintf(__('%s from now', 'wp-tally'), human_time_diff($timeout, time())))
            );
            $html .= '</div></div><!-- .tally-search-results-cache -->';
        }
        return $html;
    };

    if (
        $request->server->get('REQUEST_METHOD') === 'POST' &&
        wp_verify_nonce($post->get('_tally_ho'), 'tally-search-form')
    ) {
        Lookup::updateCount();
        Lookup::updateUser($username, Lookup::VIEW_SHORTCODE);
    }

    if ($request->query->has('force') && filter_var($request->query->get('force'), FILTER_VALIDATE_BOOLEAN)) {
        delete_transient(getTransientName($username));
        delete_transient(getTransientName($username, 'themes'));
    }

    $plugins = maybeGetPlugins($username, filter_var($request->query->get('force', false), FILTER_VALIDATE_BOOLEAN));

    $results .= '<div class="tally-search-results-wrapper">';
    $results .= sprintf(
        '<a class="tally-search-results-plugins-header%s">Plugins</a>',
        $active === 'plugins' ? ' active' : ''
    );
    $results .= sprintf(
        '<a class="tally-search-results-themes-header%s">Themes</a>',
        $active === 'themes' ? ' active' : ''
    );

    $results .= '<div class="tally-search-results-sort">';
    $results .= '<div class="tally-search-results-sort-by">';
    $results .= '<span class="tally-search-results-sort-title">Order By: </span>';
    $results .= sprintf(
        '<a href="%s#search-results"%s>Name</a>',
        add_query_arg('by', 'name'),
        $order_by === 'name' ? ' class="active"' : ''
    );
    $results .= ' / ';
    $results .= sprintf(
        '<a href="%s#search-results"%s>Downloads</a>',
        add_query_arg('by', 'downloads'),
        $order_by === 'downloaded' ? ' class="active"' : ''
    );
    $results .= '</div>';
    $results .= '<div class="tally-search-results-order">';
    $results .= '<span class="tally-search-results-sort-title">Sort: </span>';
    $results .= sprintf(
        '<a href="%s#search-results"%s>ASC</a>',
        add_query_arg('dir', 'asc'),
        $order_by === 'asc' ? ' class="active"' : ''
    );
    $results .= ' / ';

    $results .= sprintf(
        '<a href="%s#search-results"%s>DESC</a>',
        add_query_arg('dir', 'desc'),
        $order_by === 'desc' ? ' class="active"' : ''
    );
    $results .= '</div>';
    $results .= '</div>';

    $results .= sprintf(
        '<div class="tally-search-results-plugins"%s>',
        $active === 'plugins' ? '' : ' style="display: none;"'
    );

    if (!$plugins || is_wp_error($plugins)) {
        $results .= '<div class="tally-search-error">An error occurred with the plugins API. Please try again later.</div>';
    } else {
        // Maybe sort plugins.
        $plugins = sort($plugins->getPlugins(), $order_by, $sort);

        // How many plugins does the user have?
        $count = count($plugins);
        $total_downloads = 0;
        $ratings_count = 0;
        $ratings_total = 0;

        if ($count === 0) {
            $results .= sprintf(
                '<div class="tally-search-error">No plugins found for %s.</div>',
                esc_html($username)
            );
        } else {
            foreach ($plugins as $plugin) {
                $rating = getRating($plugin);

                // Plugin row.
                $results .= '<div class="tally-plugin">';

                // Content left.
                $results .= '<div class="tally-plugin-left">';

                // Plugin title.
                $results .= sprintf(
                    '<a class="tally-plugin-title" href="https://wordpress.org/plugins/%1$s" target="_blank">%2$s&nbsp;&ndash;&nbsp;%3$s</a>',
                    esc_attr($plugin->getSlug()),
                    esc_html($plugin->getName()),
                    esc_html($plugin->getVersion()),
                );

                // Plugin meta.
                $results .= '<div class="tally-plugin-meta">';
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Added:</span> %s</span>',
                    esc_html(date('d M, Y', strtotime($plugin->getAdded())))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Last Updated:</span> %s</span>',
                    esc_html(date('d M, Y', strtotime($plugin->getLastUpdated())))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Rating:</span> %s</span>',
                    empty($rating) ? 'not yet rated' : sprintf('%s out of 5 stars', esc_html($rating))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Active Installs:</span> %s</span>',
                    number_format($plugin->getActiveInstalls())
                );
                $results .= '</div>';

                // End content left.
                $results .= '</div>';

                // Content right.
                $results .= '<div class="tally-plugin-right">';
                $results .= sprintf(
                    '<div class="tally-plugin-downloads">%s</div>',
                    number_format($plugin->getDownloaded())
                );
                $results .= '<div class="tally-plugin-downloads-title">Downloads</div>';
                $results .= '</div>';

                // End plugin row.
                $results .= '</div>';

                $total_downloads += $plugin->getDownloaded();

                if (!empty($rating)) {
                    $ratings_total += $rating;
                    $ratings_count++;
                }
            }

            $plugins_total = number_format($count);
            $cumulative_rating = absint($ratings_count) === 0 ? 0 : $ratings_total / $ratings_count;

            // Totals row.
            $results .= '<div class="tally-plugin">';
            $results .= '<div class="tally-plugin-left">';
            $results .= '<div class="tally-info">';
            $results .= sprintf(
                '<span class="tally-count-rating">You have <span class="tally-count">%1$s</span> %2$s %3$s</span>',
                $plugins_total,
                $plugins_total === '1' ? 'plugin' : 'plugins',
                empty($ratings_count) ? ' with no ratings.' : sprintf(
                    ' with a cumulative rating of <span class="tally-rating">%s out of 5 stars.</span>',
                    number_format($cumulative_rating, 2, '.', '')
                )
            );
            $results .= '</div>';
            $results .= '</div>';
            $results .= '<div class="tally-plugin-right">';
            $results .= sprintf(
                '<div class="tally-plugin-downloads">%s</div>',
                number_format($total_downloads)
            );
            $results .= '<div class="tally-plugin-downloads-title">Total Downloads</div>';
            $results .= '</div>';
            $results .= '</div>';
            $results .= $cache_results('plugins');
        }
    }
    $results .= '</div>';

    $themes = maybeGetThemes($username, filter_var($request->query->get('force', false), FILTER_VALIDATE_BOOLEAN));

    $results .= sprintf(
        '<div class="tally-search-results-themes"%s>',
        $active === 'themes' ? '' : ' style="display: none;"'
    );

    if (!$themes || is_wp_error($themes)) {
        $results .= '<div class="tally-search-error">An error occurred with the themes API. Please try again later.</div>';
    } else {
        // Maybe sort themes.
        $themes = sort($themes->getThemes(), $order_by, $sort);

        // How many themes does the user have?
        $count = count($themes);
        $total_downloads = 0;
        $ratings_count = 0;
        $ratings_total = 0;

        if ($count === 0) {
            $results .= '<div class="tally-search-error">No themes found for ' . $username . '.</div>';
        } else {
            foreach ($themes as $theme) {
                $rating = getRating($theme);

                // Theme row.
                $results .= '<div class="tally-plugin">';

                // Content left.
                $results .= '<div class="tally-plugin-left">';

                // Theme title.
                $results .= sprintf(
                    '<a class="tally-plugin-title" href="https://wordpress.org/themes/%1$s" target="_blank">%2$s&nbsp;&ndash;&nbsp;%3$s</a>',
                    esc_attr($theme->getSlug()),
                    esc_html($theme->getName()),
                    esc_html($theme->getVersion()),
                );

                // Theme meta.
                $results .= '<div class="tally-plugin-meta">';
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Last Updated:</span> %s</span>',
                    esc_html($theme->getLastUpdated())
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Rating:</span> %s</span>',
                    empty($rating) ? 'not yet rated' : sprintf('%s out of 5 stars', esc_html($rating))
                );
                $results .= '</div>';

                // End content left.
                $results .= '</div>';

                // Content right.
                $results .= '<div class="tally-plugin-right">';
                $results .= sprintf(
                    '<div class="tally-plugin-downloads">%s</div>',
                    number_format($theme->getDownloaded())
                );
                $results .= '<div class="tally-plugin-downloads-title">Downloads</div>';
                $results .= '</div>';

                // End theme row.
                $results .= '</div>';

                $total_downloads += $theme->getDownloaded();

                if (!empty($rating)) {
                    $ratings_total += $rating;
                    $ratings_count++;
                }
            }

            $themes_total = number_format($count);
            $cumulative_rating = absint($ratings_count) === 0 ? 0 : $ratings_total / $ratings_count;

            // Totals row.
            $results .= '<div class="tally-plugin">';
            $results .= '<div class="tally-plugin-left">';
            $results .= '<div class="tally-info">';
            $results .= sprintf(
                '<span class="tally-count-rating">You have <span class="tally-count">%1$s</span> %2$s %3$s</span>',
                $themes_total,
                $themes_total === '1' ? 'theme' : 'themes',
                empty($ratings_count) ? ' with no ratings.' : sprintf(
                    ' with a cumulative rating of <span class="tally-rating">%s out of 5 stars.</span>',
                    number_format($cumulative_rating, 2, '.', '')
                )
            );
            $results .= '</div>';
            $results .= '</div>';
            $results .= '<div class="tally-plugin-right">';
            $results .= sprintf(
                '<div class="tally-plugin-downloads">%s</div>',
                number_format($total_downloads)
            );
            $results .= '<div class="tally-plugin-downloads-title">Total Downloads</div>';
            $results .= '</div>';
            $results .= '</div>';
            $results .= $cache_results('themes');
        }
    }
    $results .= '</div>';
}

$results .= '</div>';

echo $results;
