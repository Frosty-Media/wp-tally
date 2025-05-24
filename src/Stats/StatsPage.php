<?php

declare(strict_types=1);


namespace FrostyMedia\WpTally\Stats;

use FrostyMedia\WpTally\ServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Plugin\AbstractContainerProvider;
use TheFrosty\WpUtilities\Utils\Viewable;
use function add_dashboard_page;
use function current_user_can;
use function wp_enqueue_script;

/**
 * Class SettingsPage
 * @package FrostyMedia\WpTally\Stats
 */
class StatsPage extends AbstractContainerProvider
{
    use Viewable;

    protected false|string $hook = false;

    /**
     * Add class hooks.
     */
    public function addHooks(): void
    {
        $this->addAction('admin_menu', [$this, 'addDashboardPage']);
        $this->addAction('admin_enqueue_scripts', [$this, 'adminEnqueueScript']);
    }

    /**
     * Add our settings page.
     */
    protected function addDashboardPage(): void
    {
        if (Lookup::getTotalCount() === 0) {
            return;
        }
        $this->hook = add_dashboard_page(
            'WP Tally Stats',
            'Tally Stats',
            'manage_options',
            'tally-stats',
            function (): void {
                if (!current_user_can('manage_options')) {
                    return;
                }
                $this->maybeClearStats();
                $this->getView(ServiceProvider::WP_UTILITIES_VIEW)->render(
                    'settings/settings',
                    ['data' => Lookup::getOption()]
                );
            }
        );
    }

    /**
     * Enqueue our Chart.js script on our settings page.
     * @param string $hook
     */
    protected function adminEnqueueScript(string $hook): void
    {
        if (!$this->hook || $this->hook !== $hook) {
            return;
        }
        wp_enqueue_script('chart.js', 'https://cdn.jsdelivr.net/npm/chart.js', ver: null);
    }

    /**
     * Maybe clear database user settings.
     */
    private function maybeClearStats(): void
    {
        /** @var Request $request */
        $request = $this->getContainer()->get(ServiceProvider::REQUEST);
        if (
            !$request->query->has('_wpnonce') ||
            !wp_verify_nonce($request->query->get('_wpnonce'), '_wp_tally_nonce')
        ) {
            return;
        }

        // Clear user IP stats.
        if (
            ($request->query->has('_wp_tally_clear_user') && $request->query->get('_wp_tally_clear_user') === '1') &&
            $request->query->has('ip') &&
            $request->query->has('username') &&
            $request->query->has('view')
        ) {
            $ip = $request->get('ip');
            $username = $request->get('username');
            $view = $request->get('view');
            $option = Lookup::getOption();
            unset($option[Lookup::USERS][$username][Lookup::USERS_VIEW][$view][$ip]);
            Lookup::updateOption($option);
        }

        // Clear user all user stats.
        if (
            ($request->query->has('_wp_tally_clear') && $request->query->get('_wp_tally_clear') === '1') &&
            $request->query->has('username')
        ) {
            $username = $request->get('username');
            $option = Lookup::getOption();
            unset($option[Lookup::USERS][$username]);
            Lookup::updateOption($option);
        }
    }
}
