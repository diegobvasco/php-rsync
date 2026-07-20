<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * Represents an rsync option with a key and one or more values (e.g., --exclude=pattern).
 */
final readonly class Option
{
    /**
     * @param  array<string>  $values
     */
    public function __construct(
        public string $key,
        public array $values = [],
    ) {}

    /**
     * Add a value to this option, returning a new instance.
     */
    public function addValue(string $value): static
    {
        return new self($this->key, [...$this->values, $value]);
    }

    /**
     * Get the option as a command string (e.g., --exclude='pattern').
     */
    public function __toString(): string
    {
        if ($this->values === []) {
            return '--'.$this->key;
        }

        return '--'.$this->key.'='.implode(',', $this->values);
    }
}
