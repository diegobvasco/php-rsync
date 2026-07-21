<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use Override;
use Stringable;

/**
 * Represents an rsync option with a key and one or more values (e.g., --exclude=pattern).
 */
final readonly class Option implements Stringable
{
    /** @param  array<string>  $values */
    public function __construct(
        public string $key,
        public array $values = [],
    ) {}

    /** Add a value to this option, returning a new instance. */
    public function addValue(string $value): static
    {
        return new self($this->key, [...$this->values, $value]);
    }

    /** Get the option as a command string (e.g., --exclude='pattern'). */
    #[Override]
    public function __toString(): string
    {
        return $this->toCommandString();
    }

    /** Generate command string representation for this option. */
    public function toCommandString(): string
    {
        if ($this->values === []) {
            return '--'.$this->key;
        }

        if (count($this->values) === 1) {
            return '--'.$this->key.'='.$this->escapeValue($this->values[0]);
        }

        $key = $this->key;

        return implode(' ', array_map(
            fn (string $value): string => '--'.$key.'='.$this->escapeValue($value),
            $this->values,
        ));
    }

    /** Escape a value for use in a single-quoted shell argument (POSIX style). */
    private function escapeValue(string $value): string
    {
        return "'".str_replace("'", "'\\''", $value)."'";
    }
}
