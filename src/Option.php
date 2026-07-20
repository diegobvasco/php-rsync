<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * Represents an rsync option with a key and one or more values (e.g., --exclude=pattern).
 */
final readonly class Option implements \Stringable
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
        return $this->toCommandString();
    }

    /**
     * Generate command string representation for this option.
     */
    public function toCommandString(): string
    {
        if ($this->values === []) {
            return '--'.$this->key;
        }

        if (count($this->values) === 1) {
            return '--'.$this->key.'='.sprintf("'%s'", $this->values[0]);
        }

        $key = $this->key;

        return implode(' ', array_map(
            static fn (string $value): string => '--'.$key.'='.sprintf("'%s'", $value),
            $this->values,
        ));
    }
}
