<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Plugins;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Plugin
 * @package FrostyMedia\WpTally\Models\Plugins
 */
class Plugin extends BaseModel
{

    public const string SECTION_NAME = 'name';
    public const string SECTION_SLUG = 'slug';
    public const string SECTION_VERSION = 'version';
    public const string SECTION_AUTHOR = 'author';
    public const string SECTION_AUTHOR_PROFILE = 'author_profile';
    public const string SECTION_REQUIRES = 'requires';
    public const string SECTION_TESTED = 'tested';
    public const string SECTION_REQUIRES_PHP = 'requires_php';
    public const string SECTION_REQUIRES_PLUGINS = 'requires_plugins';
    public const string SECTION_RATING = 'rating';
    public const string SECTION_RATINGS = 'ratings';
    public const string SECTION_NUM_RATINGS = 'num_ratings';
    public const string SECTION_SUPPORT_THREADS = 'support_threads';
    public const string SECTION_SUPPORT_THREADS_RESOLVED = 'support_threads_resolved';
    public const string SECTION_ACTIVE_INSTALLS = 'active_installs';
    public const string SECTION_DOWNLOADED = 'downloaded';
    public const string SECTION_LAST_UPDATED = 'last_updated';
    public const string SECTION_ADDED = 'added';
    public const string SECTION_HOMEPAGE = 'homepage';
    public const string SECTION_DOWNLOAD_LINK = 'download_link';
    public const string SECTION_ICONS = 'icons';

    private string $name;

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private string $slug;

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    private string $version;

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    private string $author;

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    private string $authorProfile;

    public function setAuthorProfile(string $authorProfile): self
    {
        $this->authorProfile = $authorProfile;
        return $this;
    }

    public function getAuthorProfile(): string
    {
        return $this->authorProfile;
    }

    private string|false $requires;

    public function setRequires(string|false $requires): self
    {
        $this->requires = $requires;
        return $this;
    }

    public function getRequires(): string|false
    {
        return $this->requires;
    }

    private string|false $tested;

    public function setTested(string|false $tested): self
    {
        $this->tested = $tested;
        return $this;
    }

    public function getTested(): string|false
    {
        return $this->tested;
    }

    private string|false $requiresPhp;

    public function setRequiresPhp(string|false $requiresPhp): self
    {
        $this->requiresPhp = $requiresPhp;
        return $this;
    }

    public function getRequiresPhp(): string|false
    {
        return $this->requiresPhp;
    }

    private array $requiresPlugins;

    public function setRequiresPlugins(array $requiresPlugins): self
    {
        $this->requiresPlugins = $requiresPlugins;
        return $this;
    }

    public function getRequiresPlugins(): array
    {
        return $this->requiresPlugins;
    }

    private int $rating;

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    private array $ratings;

    public function setRatings(array $ratings): self
    {
        $this->ratings = $ratings;
        return $this;
    }

    public function getRatings(): array
    {
        return $this->ratings;
    }

    private int $numRatings;

    public function setNumRatings(int $numRatings): self
    {
        $this->numRatings = $numRatings;
        return $this;
    }

    public function getNumRatings(): int
    {
        return $this->numRatings;
    }

    private int $supportThreads;

    public function setSupportThreads(int $supportThreads): self
    {
        $this->supportThreads = $supportThreads;
        return $this;
    }

    public function getSupportThreads(): int
    {
        return $this->supportThreads;
    }

    private int $activeInstalls;

    public function setActiveInstalls(int $activeInstalls): self
    {
        $this->activeInstalls = $activeInstalls;
        return $this;
    }

    public function getActiveInstalls(): int
    {
        return $this->activeInstalls;
    }

    private int $downloaded;

    public function setDownloaded(int $downloaded): self
    {
        $this->downloaded = $downloaded;
        return $this;
    }

    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    private string $lastUpdated;

    public function setLastUpdated(string $lastUpdated): self
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public function getLastUpdated(): string
    {
        return $this->lastUpdated;
    }

    private string $added;

    public function setAdded(string $added): self
    {
        $this->added = $added;
        return $this;
    }

    public function getAdded(): string
    {
        return $this->added;
    }

    private string $homepage;

    public function setHomepage(string $homepage): self
    {
        $this->homepage = $homepage;
        return $this;
    }

    public function getHomepage(): string
    {
        return $this->homepage;
    }

    private string $downloadLink;

    public function setDownloadLink(string $downloadLink): self
    {
        $this->downloadLink = $downloadLink;
        return $this;
    }

    public function getDownloadLink(): string
    {
        return $this->downloadLink;
    }

    private array $icons;

    public function setIcons(array $icons): self
    {
        $this->icons = $icons;
        return $this;
    }

    public function getIcons(): array
    {
        return $this->icons;
    }

    /**
     * Get serializable fields.
     * @return string[]
     */
    protected function getSerializableFields(): array
    {
        return [
            self::SECTION_NAME,
            self::SECTION_SLUG,
            self::SECTION_VERSION,
            self::SECTION_AUTHOR,
            self::SECTION_AUTHOR_PROFILE,
            self::SECTION_REQUIRES,
            self::SECTION_TESTED,
            self::SECTION_REQUIRES_PHP,
            self::SECTION_REQUIRES_PLUGINS,
            self::SECTION_RATING,
            self::SECTION_RATINGS,
            self::SECTION_NUM_RATINGS,
            self::SECTION_SUPPORT_THREADS,
            self::SECTION_SUPPORT_THREADS_RESOLVED,
            self::SECTION_ACTIVE_INSTALLS,
            self::SECTION_DOWNLOADED,
            self::SECTION_LAST_UPDATED,
            self::SECTION_ADDED,
            self::SECTION_HOMEPAGE,
            self::SECTION_DOWNLOAD_LINK,
            self::SECTION_ICONS,
        ];
    }
}