<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally;

use FrostyMedia\WpTally\Stats\Lookup;
use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use TheFrosty\WpUtilities\Utils\View;
use function dirname;

/**
 * Class ServiceProvider
 * @package FrostyMedia\WpTally
 */
class ServiceProvider implements ServiceProviderInterface
{

    public const string API = 'lookup.api';
    public const string REQUEST = 'request';
    public const string WP_UTILITIES_VIEW = 'wp_utilities.view';

    /**
     * Register services.
     * @param PimpleContainer $pimple Container instance.
     */
    public function register(PimpleContainer $pimple): void
    {
        $pimple[self::API] = static fn(): Lookup => new Lookup();

        $pimple[self::REQUEST] = static fn(): Request => Request::createFromGlobals();

        $pimple[self::WP_UTILITIES_VIEW] = static function (): View {
            $view = new View();
            $view->addPath(dirname(__DIR__) . '/resources/views/');

            return $view;
        };
    }
}
