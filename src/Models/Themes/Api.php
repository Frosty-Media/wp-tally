<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Themes;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Api
 * @package FrostyMedia\WpTally\Models\Themes
 */
class Api extends BaseModel
{

    public const string SECTION_INFO = 'info';
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

    /** @var Theme[] $themes */
    private array $themes = [];

    public function setThemes(array $themes): self
    {
        foreach ($themes as $theme) {
            $this->themes[] = new Theme((array)$theme);
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
            self::SECTION_THEMES,
        ];
    }
}
