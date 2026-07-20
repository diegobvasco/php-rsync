<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * Represents an rsync flag (e.g., --delete, --recursive, --archive).
 */
final readonly class Flag implements \Stringable
{
    public function __construct(
        public string $name,
    ) {}

    public function __toString(): string
    {
        return $this->name;
    }
}
