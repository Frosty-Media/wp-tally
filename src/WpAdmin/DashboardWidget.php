<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\WpAdmin;

use TheFrosty\WpUtilities\Plugin\HooksTrait;
use TheFrosty\WpUtilities\Plugin\WpHooksInterface;
use function _n;
use function get_option;
use function number_format_i18n;
use function printf;

class DashboardWidget implements WpHooksInterface
{

    use HooksTrait;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('dashboard_glance_items', [$this, 'glanceItems']);
    }

    protected function glanceItems(): void
    {
        $count = get_option('wptally_lookups', 0);

        $count = $count ? number_format_i18n($count) : 0;

        $label = _n('Lookup', 'Lookups', (int)$count, 'wp-tally');

        printf('<li class="wptally-count"><span>%d %s</span></li>', $count, $label);
    }
}
