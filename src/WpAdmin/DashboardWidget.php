<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\WpAdmin;

use FrostyMedia\WpTally\Stats\Lookup;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use function _n;
use function admin_url;
use function esc_url;
use function filemtime;
use function number_format_i18n;
use function plugins_url;
use function printf;
use function wp_enqueue_style;

/**
 * Class DashboardWidget.
 * @package FrostyMedia\WpTally\WpAdmin
 */
class DashboardWidget extends AbstractContainerProvider
{
    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('dashboard_glance_items', [$this, 'glanceItems']);
        $this->addAction('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    /**
     * Get our total lookups from the DB.
     */
    protected function glanceItems(): void
    {
        $count = number_format_i18n(Lookup::getTotalCount());
        $label = _n('Lookup', 'Lookups', $count, 'wp-tally');

        printf(
            '<li class="wptally-count"><a href="%3$s">%1$d %2$s</a></span></li>',
            $count,
            $label,
            esc_url(admin_url('index.php?page=tally-stats'))
        );
    }

    /**
     * Load admin scripts and styles
     * @param string $hook
     */
    protected function adminEnqueueScripts(string $hook): void
    {
        if ($hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(
            'wp-tally',
            plugins_url('resources/css/admin.css', dirname(__DIR__)),
            filemtime(plugin_dir_path(dirname(__DIR__)) . 'resources/css/admin.css')
        );
    }
}
