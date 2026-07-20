<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * Immutable collection of Flag objects.
 *
 * @implements IteratorAggregate<int, Flag>
 * @implements ArrayAccess<int, Flag>
 */
final readonly class FlagCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int, Flag> */
    private array $items;

    /**
     * @param  array<int, Flag>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

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
            throw new BadMethodCallException('FlagCollection only accepts Flag objects.');
        }

        // Deduplicate by name
        foreach ($this->items as $existing) {
            if ($existing->name === $item->name) {
                return $this;
            }
        }

        return new static([...$this->items, $item]);
    }

    public function addFlag(string $name): static
    {
        return $this->add(new Flag($name));
    }

    public function merge(self $collection): static
    {
        $items = $this->items;

        foreach ($collection as $flag) {
            $found = false;
            foreach ($items as $existing) {
                if ($existing->name === $flag->name) {
                    $found = true;

                    break;
                }
            }

            if (! $found) {
                $items[] = $flag;
            }
        }

        /** @var array<int, Flag> $items */
        return new static($items);
    }

    public function remove(string $name): static
    {
        /** @var array<int, Flag> $filtered */
        $filtered = array_values(array_filter(
            $this->items,
            static fn (Flag $flag): bool => $flag->name !== $name,
        ));

        return new static($filtered);
    }

    /**
     * @return array<int, Flag>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function first(): Flag
    {
        return $this->items[0] ?? throw new BadMethodCallException('Collection is empty.');
    }

    public function last(): Flag
    {
        if ($this->items === []) {
            throw new BadMethodCallException('Collection is empty.');
        }

        return $this->items[array_key_last($this->items)];
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

        return new static($filtered);
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
        foreach ($this->items as $item) {
            if ($item->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all flag names as strings.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(
            static fn (Flag $flag): string => $flag->name,
            $this->items,
        );
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

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, Flag>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): Flag
    {
        return $this->items[$offset] ?? throw new \OutOfBoundsException("Offset {$offset} does not exist.");
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('FlagCollection is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('FlagCollection is immutable.');
    }

    /**
     * @return list<string>
     */
    public function jsonSerialize(): array
    {
        return $this->names();
    }
}
