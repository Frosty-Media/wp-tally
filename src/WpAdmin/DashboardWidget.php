<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\WpAdmin;

use FrostyMedia\WpTally\ServiceProvider;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use function _n;
use function filemtime;
use function number_format_i18n;
use function plugins_url;
use function printf;
use function wp_enqueue_style;

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
        /** @var \FrostyMedia\WpTally\Stats\Lookup $lookup */
        $lookup = $this->getContainer()->get(ServiceProvider::API);
        $count = number_format_i18n($lookup->getTotalCount());
        $label = _n('Lookup', 'Lookups', $count, 'wp-tally');

        printf('<li class="wptally-count"><span>%d %s</span></li>', $count, $label);
    }

    /**
     * Load admin scripts and styles
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
