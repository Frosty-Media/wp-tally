<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Plugins;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Api
 * @package FrostyMedia\WpTally\Models\Plugins
 */
class Api extends BaseModel
{

    public const string SECTION_INFO = 'info';
    public const string SECTION_PLUGINS = 'plugins';

    private Info $info;

    public function setInfo(array $info): self
    {
        $this->info = new Info($info);
        return $this;
    }

    public function getInfo(): Info
    {
        return $this->info;
    }

    /** @var Plugin[] $plugins */
    private array $plugins;

    public function setPlugins(array $plugins): self
    {
        foreach ($plugins as $plugin) {
            $this->plugins[] = new Plugin($plugin);
        }
        return $this;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * Get serializable fields.
     * @return string[]
     */
    protected function getSerializableFields(): array
    {
        return [
            self::SECTION_INFO,
            self::SECTION_PLUGINS,
        ];
    }
}