<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models\Themes;

use TheFrosty\WpUtilities\Models\BaseModel;

/**
 * Class Info
 * @package FrostyMedia\WpTally\Models\Themes
 */
class Info extends BaseModel
{

    public const string SECTION_PAGE = 'page';
    public const string SECTION_PAGES = 'pages';
    public const string SECTION_RESULTS = 'results';

    private int $page;

    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    private int $pages;

    public function setPages(int $pages): self
    {
        $this->pages = $pages;
        return $this;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    private int $results;

    public function setResults(int $results): self
    {
        $this->results = $results;
        return $this;
    }

    public function getResults(): int
    {
        return $this->results;
    }

    /**
     * Get serializable fields.
     * @return string[]
     */
    protected function getSerializableFields(): array
    {
        return [
            self::SECTION_PAGE,
            self::SECTION_PAGES,
            self::SECTION_RESULTS,
        ];
    }
}
