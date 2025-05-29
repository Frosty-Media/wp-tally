<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Tally;

use FrostyMedia\WpTally\Models\Plugins\Plugin;
use FrostyMedia\WpTally\Models\Themes\Theme;
use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Tally
 * @package FrostyMedia\WpTally\Models
 */
class Tally extends BaseModel
{

    public const string SECTION_INFO = 'info';
    public const string SECTION_PLUGINS = 'plugins';
    public const string SECTION_THEMES = 'themes';

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
    private array $plugins = [];

    public function setPlugins(array $plugins): self
    {
        foreach ($plugins as $key => $plugin) {
            if ($key === Plugin::SECTION_ERROR) {
                // User has no plugins.
                $this->$plugin[] = new Plugin($plugin);
                continue;
            }
            $this->plugins[] = new Plugin($plugin);
        }
        return $this;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /** @var Theme[] $themes */
    private array $themes = [];

    public function setThemes(array $themes): self
    {
        foreach ($themes as $key => $theme) {
            if ($key === Theme::SECTION_ERROR) {
                // User has no themes.
                $this->themes[] = new Theme($themes);
                continue;
            }
            $this->themes[] = new Theme($theme);
        }
        return $this;
    }

    public function getThemes(): array
    {
        return $this->themes;
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
            self::SECTION_THEMES,
        ];
    }
}
