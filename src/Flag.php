<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * Represents an rsync flag value object.
 *
 * Can be created from a FlagType enum case or a custom string for
 * flags not covered by the predefined FlagType enum.
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

    /**
     * Create a Flag from a FlagType enum case.
     */
    public static function fromType(FlagType $type): self
    {
        return new self($type->value);
    }

    /**
     * Try to get an existing Flag for a string value, returns null if not a known flag.
     *
     * @param  string  $name  Flag name (with or without -- prefix)
     */
    public static function tryFromValue(string $name): ?self
    {
        $value = '--'.ltrim($name, '-');

        return FlagType::tryFrom($value) !== null
            ? new self($value)
            : null;
    }

    /**
     * Get all known flag names as an array.
     *
     * @return list<string>
     */
    public static function getAllNames(): array
    {
        return array_map(
            static fn (FlagType $flag): string => $flag->value,
            FlagType::cases(),
        );
    }
}
