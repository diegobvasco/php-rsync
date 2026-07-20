<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\AbstractCollection;
use JsonSerializable;

/**
 * Immutable collection of Flag objects.
 *
 * @extends AbstractCollection<Flag>
 */
final readonly class FlagCollection extends AbstractCollection implements \Stringable, JsonSerializable
{
    /**
     * Create a new FlagCollection from an array of flag names.
     *
     * @param  list<string>  $names
     */
    public static function fromArray(array $names): static
    {
        return new self(array_map(
            static fn (string $name): Flag => new Flag($name),
            $names,
        ));
    }

    public function add(mixed $item): static
    {
        if (! $item instanceof Flag) {
            throw new \BadMethodCallException('FlagCollection only accepts Flag objects.');
        }

        // Deduplicate by name
        foreach ($this->items as $existing) {
            if ($existing->name === $item->name) {
                return $this;
            }
        }

        return new self([...$this->items, $item]);
    }

    public function addFlag(string $name): static
    {
        return $this->add(new Flag($name));
    }

    public function merge(self $collection): static
    {
        $items = $this->items;

        foreach ($collection as $flag) {
            $found = array_any($items, fn (Flag $existing): bool => $existing->name === $flag->name);
            if (! $found) {
                $items[] = $flag;
            }
        }

        /** @var array<int, Flag> $items */
        return new self($items);
    }

    public function remove(string $name): static
    {
        /** @var array<int, Flag> $filtered */
        $filtered = array_values(array_filter(
            $this->items,
            static fn (Flag $flag): bool => $flag->name !== $name,
        ));

        return new self($filtered);
    }

    /**
     * @param  callable(Flag, int): bool  $callback
     */
    public function filter(callable $callback): static
    {
        /** @var array<int, Flag> $filtered */
        $filtered = array_values(array_filter(
            $this->items,
            $callback,
            ARRAY_FILTER_USE_BOTH,
        ));

        return new self($filtered);
    }

    /**
     * @template T
     *
     * @param  callable(Flag, int): T  $callback
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    public function contains(string $name): bool
    {
        return array_any($this->items, fn (Flag $item): bool => $item->name === $name);
    }

    /**
     * Get all flag names as strings.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_map(
            static fn (Flag $flag): string => $flag->name,
            $this->items,
        ));
    }

    /**
     * @return list<string>
     */
    public function toArray(): array
    {
        return $this->names();
    }

    public function __toString(): string
    {
        return implode(' ', $this->names());
    }

    /**
     * @return list<string>
     */
    public function jsonSerialize(): array
    {
        return $this->names();
    }
}
