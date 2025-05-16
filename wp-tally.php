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

use TheFrosty\WpUtilities\Plugin\PluginFactory;
use TheFrosty\WpUtilities\WpAdmin\DisablePluginUpdateCheck;
use function defined;
use function flush_rewrite_rules;
use function is_readable;
use function register_activation_hook;
use function register_deactivation_hook;

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    include_once __DIR__ . '/vendor/autoload.php';
}

$plugin = PluginFactory::create('wp-tally');
$container = $plugin->getContainer();
$container->register(new ServiceProvider());

$plugin
    ->add(new DisablePluginUpdateCheck())
    ->addOnHook(Route\Api::class, 'after_setup_theme')
    ->addOnHook(Shortcodes\Tally::class, 'after_setup_theme', args: [$container])
    ->addOnHook(WpAdmin\DashboardWidget::class, 'load-index.php')
    ->initialize();

// Make sure we flush rules for our rewrite endpoint.
register_activation_hook(__FILE__, static function (): void {
    flush_rewrite_rules(false);
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules(false);
});
