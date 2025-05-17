<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Shortcodes;

use FrostyMedia\WpTally\ServiceProvider;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestInterface;
use TheFrosty\WpUtilities\Plugin\HttpFoundationRequestTrait;
use TheFrosty\WpUtilities\Utils\Viewable;
use function add_shortcode;
use function dirname;
use function filemtime;
use function plugins_url;
use function wp_enqueue_script;
use function wp_enqueue_style;

class Tally extends AbstractContainerProvider implements HttpFoundationRequestInterface
{

    use HttpFoundationRequestTrait, Viewable;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('init', [$this, 'addShortcode']);
    }

    protected function addShortcode(): void
    {
        add_shortcode('tally', function (): string {
            wp_enqueue_script(
                'wp-tally',
                plugins_url('resources/js/wptally.js', dirname(__DIR__)),
                ['jquery'],
                filemtime(plugin_dir_path(dirname(__DIR__)) . 'resources/js/wptally.js')
            );
            wp_enqueue_style(
                'wp-tally',
                plugins_url('resources/css/style.css', dirname(__DIR__)),
                filemtime(\plugin_dir_path(dirname(__DIR__)) . 'resources/css/style.css')
            );

            return $this->getView(ServiceProvider::WP_UTILITIES_VIEW)->retrieve(
                'shortcodes/tally',
                ['lookup' => $this->getContainer()->get(ServiceProvider::API), 'query' => $this->getRequest()->query]
            );
        });
    }
}