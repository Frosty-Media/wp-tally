<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\InputBag;
use function FrostyMedia\WpTally\getRating;
use function FrostyMedia\WpTally\getTransientName;
use function FrostyMedia\WpTally\maybeGetPlugins;
use function FrostyMedia\WpTally\maybeGetThemes;
use function FrostyMedia\WpTally\sort;

/** @var InputBag $query */
$query ??= $this->getRequest()->query;

$username = $query->get('wpusername', '');
$active = $query->has('active') && $query->get('active') === 'themes' ? 'themes' : 'plugins';
$order_by = $query->has('by') && $query->get('by') === 'downloads' ? 'downloaded' : 'name';
$sort = $query->has('dir') && $query->get('dir') === 'desc' ? 'desc' : 'asc';

$search = <<<'HTML'
    <div class="tally-search-box">
    <form class="tally-search-form" method="get" action="" autocomplete="off">
    <input type="text" name="wpusername" class="tally-search-field" placeholder="Enter your WordPress.org username" value="%s" data-1p-ignore>
    <input type="submit" class="tally-search-submit" value="Search" >
    </form>
    </div>
    HTML;

printf($search, sanitize_user($username));

$results = '<div class="tally-search-results" id="search-results">';

if ($username) {
    $lookup_count = get_option('wptally_lookups');
    $lookup_count = $lookup_count ? $lookup_count + 1 : 1;
    update_option('wptally_lookups', $lookup_count);

    if ($query->has('force') && filter_var($query->get('force'), FILTER_VALIDATE_BOOLEAN)) {
        delete_transient(getTransientName($username));
        delete_transient(getTransientName($username, 'themes'));
    }

    $plugins = maybeGetPlugins($username, $query->get('force', false));

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

    if (is_wp_error($plugins)) {
        $results .= '<div class="tally-search-error">An error occurred with the plugins API. Please try again later.</div>';
    } else {
        $plugins = $plugins->plugins;

        // Maybe sort plugins
//        $plugins = sort($plugins, $order_by, $sort);

        // How many plugins does the user have?
        $count = count($plugins);
        $total_downloads = 0;
        $ratings_count = 0;
        $ratings_total = 0;

        if ($count === 0) {
            $results .= sprintf(
                '<div class="tally-search-error">No plugins found for %s!</div>',
                esc_html($username)
            );
        } else {
            foreach ($plugins as $plugin) {
                $rating = getRating($plugin['num_ratings'], $plugin['ratings']);

                // Plugin row
                $results .= '<div class="tally-plugin">';

                // Content left
                $results .= '<div class="tally-plugin-left">';

                // Plugin title
                $results .= sprintf(
                    '<a class="tally-plugin-title" href="https://wordpress.org/plugins/%1$s" target="_blank">%2$s&nbsp;&ndash;&nbsp;%3$s</a>',
                    esc_attr($plugin['slug']),
                    esc_html($plugin['name']),
                    esc_html($plugin['version']),
                );

                // Plugin meta
                $results .= '<div class="tally-plugin-meta">';
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Added:</span> %s</span>',
                    esc_html(date('d M, Y', strtotime((string)$plugin['added'])))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Last Updated:</span> %s</span>',
                    esc_html(date('d M, Y', strtotime((string)$plugin['last_updated'])))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Rating:</span> %s</span>',
                    empty($rating) ? 'not yet rated' : sprintf('%s out of 5 stars', esc_html($rating))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Active Installs:</span> %s</span>',
                    number_format($plugin['active_installs'])
                );
                $results .= '</div>';

                // End content left
                $results .= '</div>';

                // Content right
                $results .= '<div class="tally-plugin-right">';
                $results .= sprintf(
                    '<div class="tally-plugin-downloads">%s</div>',
                    number_format($plugin['downloaded'])
                );
                $results .= '<div class="tally-plugin-downloads-title">Downloads</div>';
                $results .= '</div>';

                // End plugin row
                $results .= '</div>';

                $total_downloads += $plugin['downloaded'];

                if (!empty($rating)) {
                    $ratings_total += $rating;
                    $ratings_count++;
                }
            }

            $plugins_total = number_format($count);
            $cumulative_rating = $ratings_total / $ratings_count;

            // Totals row
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
        }
    }
    $results .= '</div>';

    $themes = maybeGetThemes($username, $query->get('force', false));

    // Maybe sort themes (Disable for now)
    // $themes = sort((array)$themes, $order_by, $sort);

    $results .= sprintf(
        '<div class="tally-search-results-themes"%s>',
        $active === 'themes' ? '' : ' style="display: none;"'
    );

    if (is_wp_error($themes)) {
        $results .= '<div class="tally-search-error">An error occurred with the themes API. Please try again later.</div>';
    } else {
        // How many themes does the user have?
        $count = count($themes);
        $total_downloads = 0;
        $ratings_count = 0;
        $ratings_total = 0;

        if ($count === 0) {
            $results .= '<div class="tally-search-error">No themes found for ' . $username . '!</div>';
        } else {
            foreach ($themes as $theme) {
                $rating = getRating($theme['num_ratings'], $theme['rating']);

                // Theme row
                $results .= '<div class="tally-plugin">';

                // Content left
                $results .= '<div class="tally-plugin-left">';

                // Theme title
                $results .= sprintf(
                    '<a class="tally-plugin-title" href="https://wordpress.org/themes/%1$s" target="_blank">%2$s&nbsp;&ndash;&nbsp;%3$s</a>',
                    esc_attr($theme['slug']),
                    esc_html($theme['name']),
                    esc_html($theme['version']),
                );

                // Theme meta
                $results .= '<div class="tally-plugin-meta">';
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Last Updated:</span> %s</span>',
                    esc_html(date('d M, Y', strtotime((string)$theme['last_updated'])))
                );
                $results .= sprintf(
                    '<span class="tally-plugin-meta-item"><span class="tally-plugin-meta-title">Rating:</span> %s</span>',
                    empty($rating) ? 'not yet rated' : sprintf('%s out of 5 stars', esc_html($rating))
                );
                $results .= '</div>';

                // End content left
                $results .= '</div>';

                // Content right
                $results .= '<div class="tally-plugin-right">';
                $results .= sprintf(
                    '<div class="tally-plugin-downloads">%s</div>',
                    number_format($theme['downloaded'])
                );
                $results .= '<div class="tally-plugin-downloads-title">Downloads</div>';
                $results .= '</div>';

                // End theme row
                $results .= '</div>';

                $total_downloads += $theme['downloaded'];

                if (!empty($rating)) {
                    $ratings_total += $rating;
                    $ratings_count++;
                }
            }

            $themes_total = number_format($count);
            $cumulative_rating = $ratings_total / $ratings_count;

            // Totals row
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
        }
    }
    $results .= '</div>';
}

$results .= '</div>';

echo $results;