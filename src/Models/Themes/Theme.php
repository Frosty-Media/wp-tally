<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Themes;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Theme
 * @package FrostyMedia\WpTally\Models\Themes
 */
class Theme extends BaseModel
{

    public const string SECTION_NAME = 'name';
    public const string SECTION_SLUG = 'slug';
    public const string SECTION_URL = 'url';
    public const string SECTION_VERSION = 'version';
    public const string SECTION_PREVIEW_URL = 'preview_url';
    public const string SECTION_AUTHOR = 'author';
    public const string SECTION_SCREENSHOT_URL = 'screenshot_url';
    public const string SECTION_REQUIRES = 'requires';
    public const string SECTION_REQUIRES_PHP = 'requires_php';
    public const string SECTION_RATING = 'rating';
    public const string SECTION_RATINGS = 'ratings';
    public const string SECTION_NUM_RATINGS = 'num_ratings';
    public const string SECTION_REVIEWS_URL = 'reviews_url';
    public const string SECTION_DOWNLOADED = 'downloaded';
    public const string SECTION_LAST_UPDATED = 'last_updated';
    public const string SECTION_CREATION_TIME = 'creation_time';
    public const string SECTION_HOMEPAGE = 'homepage';
    public const string SECTION_DOWNLOAD_LINK = 'download_link';
    public const string SECTION_IS_COMMERCIAL = 'is_commercial';
    public const string SECTION_EXTERNAL_SUPPORT_URL = 'external_support_url';
    public const string SECTION_IS_COMMUNITY = 'is_community';
    public const string SECTION_EXTERNAL_REPOSITORY_URL = 'external_repository_url';
    public const string SECTION_ERROR = 'error';

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

    private string $url;

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
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

    private string $previewUrl;

    public function setPreviewUrl(string $previewUrl): self
    {
        $this->previewUrl = $previewUrl;
        return $this;
    }

    public function getPreviewUrl(): string
    {
        return $this->previewUrl;
    }

    private array $author;

    public function setAuthor(array $author): self
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return array{
     *     'user_nicename': string,
     *     'profile': string,
     *     'avatar': string,
     *     'display_name': string,
     *     'author': string,
     *     'author_url': string
     * }
     */
    public function getAuthor(): array
    {
        return $this->author;
    }

    private string $screenshotUrl;

    public function setScreenshotUrl(string $screenshotUrl): self
    {
        $this->screenshotUrl = $screenshotUrl;
        return $this;
    }

    public function getScreenshotUrl(): string
    {
        return $this->screenshotUrl;
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

    private float|int $rating;

    public function setRating(float|int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getRating(): float|int
    {
        return $this->rating;
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

    private string $creationTime;

    public function setCreationTime(string $creationTime): self
    {
        $this->creationTime = $creationTime;
        return $this;
    }

    public function getCreationTime(): string
    {
        return $this->creationTime;
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

    private bool $isCommercial;

    public function setIsCommercial(bool $isCommercial): self
    {
        $this->isCommercial = $isCommercial;
        return $this;
    }

    public function getIsCommercial(): bool
    {
        return $this->isCommercial;
    }

    private string|false $externalSupportUrl;

    public function setExternalSupportUrl(string|false $externalSupportUrl): self
    {
        $this->externalSupportUrl = $externalSupportUrl;
        return $this;
    }

    public function getExternalSupportUrl(): string|false
    {
        return $this->externalSupportUrl;
    }

    private bool $isCommunity;

    public function setIsCommunity(bool $isCommunity): self
    {
        $this->isCommunity = $isCommunity;
        return $this;
    }

    public function getIsCommunity(): bool
    {
        return $this->isCommunity;
    }

    private string|false $externalRepositoryUrl;

    public function setExternalRepositoryUrl(string|false $externalRepositoryUrl): self
    {
        $this->externalRepositoryUrl = $externalRepositoryUrl;
        return $this;
    }

    public function getExternalRepositoryUrl(): string|false
    {
        return $this->externalRepositoryUrl;
    }

    private ?string $error = null;

    public function setError(string $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
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
            self::SECTION_URL,
            self::SECTION_VERSION,
            self::SECTION_PREVIEW_URL,
            self::SECTION_AUTHOR,
            self::SECTION_SCREENSHOT_URL,
            self::SECTION_RATINGS,
            self::SECTION_RATING,
            self::SECTION_NUM_RATINGS,
            self::SECTION_REVIEWS_URL,
            self::SECTION_DOWNLOADED,
            self::SECTION_LAST_UPDATED,
            self::SECTION_CREATION_TIME,
            self::SECTION_HOMEPAGE,
            self::SECTION_DOWNLOAD_LINK,
            self::SECTION_REQUIRES,
            self::SECTION_REQUIRES_PHP,
            self::SECTION_IS_COMMERCIAL,
            self::SECTION_EXTERNAL_SUPPORT_URL,
            self::SECTION_IS_COMMUNITY,
            self::SECTION_EXTERNAL_REPOSITORY_URL,
            self::SECTION_ERROR,
        ];
    }
}
