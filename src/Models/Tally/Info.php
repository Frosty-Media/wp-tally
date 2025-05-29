<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Tally;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Info
 * @package FrostyMedia\WpTally\Models\Plugins
 */
class Info extends BaseModel
{

    public const string SECTION_USER = 'user';
    public const string SECTION_PROFILE = 'profile';
    public const string SECTION_PLUGIN_COUNT = 'plugin_count';
    public const string SECTION_TOTAL_PLUGIN_DOWNLOADS = 'total_plugin_downloads';
    public const string SECTION_THEME_COUNT = 'theme_count';
    public const string SECTION_TOTAL_THEME_DOWNLOADS = 'total_theme_downloads';

    private string $user;

    public function setUser(string $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    private string $profile;

    public function setProfile(string $profile): self
    {
        $this->profile = $profile;
        return $this;
    }

    public function getProfile(): string
    {
        return $this->profile;
    }

    private int $pluginCount;

    public function setPluginCount(int $pluginCount): self
    {
        $this->pluginCount = $pluginCount;
        return $this;
    }

    public function getPluginCount(): int
    {
        return $this->pluginCount;
    }

    private int $totalPluginDownloads;

    public function setTotalPluginDownloads(int $totalPluginDownloads): self
    {
        $this->totalPluginDownloads = $totalPluginDownloads;
        return $this;
    }

    public function getTotalPluginDownloads(): int
    {
        return $this->totalPluginDownloads;
    }

    /**
     * Get serializable fields.
     * @return string[]
     */
    protected function getSerializableFields(): array
    {
        return [
            self::SECTION_USER,
            self::SECTION_PROFILE,
            self::SECTION_PLUGIN_COUNT,
            self::SECTION_TOTAL_PLUGIN_DOWNLOADS,
        ];
    }
}
