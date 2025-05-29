<?php

declare(strict_types=1);

namespace FrostyMedia\WpTally\Models;

/**
 * Trait ErrorTrait
 * @package FrostyMedia\WpTally\Models
 */
trait ErrorTrait
{
    public const string SECTION_ERROR = 'error';

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

    public function hasError(): bool
    {
        return $this->error !== null;
    }
}
