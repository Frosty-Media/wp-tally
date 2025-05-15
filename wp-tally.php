<?php
/**
 * Plugin Name: WP Tally
 * Plugin URI: https://wptally.com
 * Description: Track your total WordPress plugin and theme downloads.
 * Version: 1.2.1
 * Author: Pippin Williamson, Daniel J Griffiths & Sean Davis
 * Author URI: https://austin.passy.co
 * Requires at least: 6.8
 * Tested up to: 6.8.1
 * Requires PHP: 8.3
 * Plugin URI: https://github.com/Frosty-Media/wp-tally
 * GitHub Plugin URI: https://github.com/Frosty-Media/wp-tally
 * Primary Branch: develop
 * Release Asset: true
 */

namespace FrostyMedia\WpTally;

defined('ABSPATH') || exit;

use FrostyMedia\WpTally\Route\Api;
use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use function defined;
use function is_readable;

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('wp-tally');
$container = $plugin->getContainer();
$container->register(new ServiceProvider());

$plugin
    ->add(new DisablePluginUpdateCheck())
    ->addOnHook(Route\Api::class, 'after_setup_theme')
    ->addOnHook(Scripts\ScriptsManager::class, 'init')
    ->addOnHook(Shortcodes\Tally::class, 'after_setup_theme', args: [$container])
    ->addOnHook(WpAdmin\DashboardWidget::class, 'load-index.php')
    ->initialize();

add_action('template_redirect', static function (): void {
    if (Api::hasQueryVar()) {
        require_once __DIR__ . '/src/functions.php';
    }
}, -5);

// Make sure we flush rules for our rewrite endpoint.
register_activation_hook(__FILE__, static function (): void {
    flush_rewrite_rules(false);
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules(false);
});
