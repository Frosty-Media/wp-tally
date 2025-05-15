<?php

namespace FrostyMedia\WpTally\Shortcodes;

use FrostyMedia\WpTally\ServiceProvider;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Utils\Viewable;

class Tally extends AbstractContainerProvider
{

    use Viewable;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('init', [$this, 'addShortcode']);
    }

    protected function addShortcode(): void
    {
        add_shortcode('tally', function(): void {
            $this->getView(ServiceProvider::WP_UTILITIES_VIEW)->render(
                'shortcodes/tally'
            );
        });
    }
}