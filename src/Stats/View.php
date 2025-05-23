<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Stats;

enum View: string
{

    case API = 'api';
    case SHORTCODE = 'shortcode';
}
