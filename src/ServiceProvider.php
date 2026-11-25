<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally;

use TheFrosty\WpUtilities\Plugin\DefaultServiceProvider;
use function dirname;

/**
 * Class ServiceProvider
 * @package FrostyMedia\WpTally
 */
class ServiceProvider  extends DefaultServiceProvider
{

    protected function getViewPaths(): array
    {
        return [dirname(__DIR__) . '/resources/views/'];
    }
}
