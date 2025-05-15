<?php

namespace FrostyMedia\WpTally\Scripts;

use FrostyMedia\WpTally\Route\Api;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use function wp_enqueue_script;
use function wp_enqueue_style;

class ScriptsManager extends AbstractContainerProvider
{

    protected const string HANDLE = 'wp-tally';

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('wp_enqueue_scripts', [$this, 'wpEnqueueScripts']);
        $this->addAction('admin_enqueue_scripts', [$this, 'adminEnqueueScripts']);
    }

    /**
     * Load scripts and styles
     */
    protected function wpEnqueueScripts(): void
    {
        if (!Api::hasQueryVar()) {
            return;
        }

        wp_enqueue_script(self::HANDLE, plugins_url('resources/js/wptally.js', __DIR__), ['jquery']);
        wp_enqueue_style(self::HANDLE, plugins_url('resources/css/style.css', __DIR__));
    }


    /**
     * Load admin scripts and styles
     */
    protected function adminEnqueueScripts(string $hook): void
    {
        if ($hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(self::HANDLE, plugins_url('resources/css/admin.css', __DIR__));
    }
}