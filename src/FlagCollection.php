<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Support\AbstractCollection;
use JsonSerializable;
use Stringable;

/**
 * Immutable collection of FlagType cases.
 *
 * @extends AbstractCollection<FlagType>
 */
final readonly class FlagCollection extends AbstractCollection implements JsonSerializable, Stringable
{
    public function add(mixed $item): static
    {
        if (! $item instanceof FlagType) {
            throw new \BadMethodCallException('FlagCollection only accepts FlagType cases.');
        }

        // Deduplicate by enum case identity.
        foreach ($this->items as $existing) {
            if ($existing === $item) {
                return $this;
            }
        }

        return new self([...$this->items, $item]);
    }

    /**
     * Convenience alias of add() with a typed parameter.
     */
    public function addFlag(FlagType $flag): static
    {
        return $this->add($flag);
    }

    public function merge(self $collection): static
    {
        $items = $this->items;

        foreach ($collection as $flag) {
            $found = array_any($items, fn (FlagType $existing): bool => $existing === $flag);

            if (! $found) {
                $items[] = $flag;
            }
        }

        /** @var array<int, FlagType> $items */
        return new self($items);
    }

    public function remove(FlagType $flag): static
    {
        /** @var array<int, FlagType> $filtered */
        $filtered = array_values(array_filter(
            $this->items,
            static fn (FlagType $current): bool => $current !== $flag,
        ));

        return new self($filtered);
    }

    /**
     * @param  callable(FlagType, int): bool  $callback
     */
    public function filter(callable $callback): static
    {
        /** @var array<int, FlagType> $filtered */
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
     * @param  callable(FlagType, int): T  $callback
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    public function contains(FlagType $flag): bool
    {
        return array_any($this->items, fn (FlagType $current): bool => $current === $flag);
    }

    /**
     * Get all flag values as strings (e.g. ['--delete', '--recursive']).
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_map(
            static fn (FlagType $flag): string => $flag->value,
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
