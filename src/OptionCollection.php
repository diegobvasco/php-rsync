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
 * Immutable collection of Option objects.
 *
 * Options with the same key are merged into a single Option with multiple values.
 *
 * @implements IteratorAggregate<int, Option>
 * @implements ArrayAccess<int, Option>
 */
final readonly class OptionCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int, Option> */
    private array $items;

    /**
     * @param  array<int, Option>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    public function add(mixed $item): static
    {
        if (! $item instanceof Option) {
            throw new BadMethodCallException('OptionCollection only accepts Option objects.');
        }

        // Merge with existing option of same key
        foreach ($this->items as $existing) {
            if ($existing->key === $item->key) {
                $mergedValues = [...$existing->values, ...$item->values];
                $mergedOption = new Option($existing->key, $mergedValues);

                /** @var array<int, Option> $mapped */
                $mapped = array_map(
                    static fn (Option $opt): Option => $opt->key === $existing->key ? $mergedOption : $opt,
                    $this->items,
                );

                return new self($mapped);
            }
        }

        return new self([...$this->items, $item]);
    }

    public function addOption(string $key, string $value = ''): static
    {
        return $this->add(new Option($key, $value !== '' ? [$value] : []));
    }

    public function merge(self $collection): static
    {
        $result = $this;

        foreach ($collection as $option) {
            $result = $result->add($option);
        }

        return $result;
    }

    public function remove(string $key): static
    {
        /** @var array<int, Option> $filtered */
        $filtered = array_values(array_filter(
            $this->items,
            static fn (Option $option): bool => $option->key !== $key,
        ));

        return new static($filtered);
    }

    /**
     * @return array<int, Option>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function first(): Option
    {
        return $this->items[0] ?? throw new BadMethodCallException('Collection is empty.');
    }

    public function last(): Option
    {
        if ($this->items === []) {
            throw new BadMethodCallException('Collection is empty.');
        }

        return $this->items[array_key_last($this->items)];
    }

    /**
     * @param  callable(Option, int): bool  $callback
     */
    public function filter(callable $callback): static
    {
        /** @var array<int, Option> $filtered */
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
     * @param  callable(Option, int): T  $callback
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    /**
     * Get an option by key.
     */
    public function get(string $key): Option
    {
        foreach ($this->items as $option) {
            if ($option->key === $key) {
                return $option;
            }
        }

        throw new \OutOfBoundsException("Option '{$key}' not found.");
    }

    public function has(string $key): bool
    {
        foreach ($this->items as $option) {
            if ($option->key === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all option keys.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_map(
            static fn (Option $option): string => $option->key,
            $this->items,
        );
    }

    /**
     * Get options as an associative array.
     *
     * @return array<string, string|array<int, string>>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->items as $option) {
            $result[$option->key] = count($option->values) === 1
                ? $option->values[0]
                : array_values($option->values);
        }

        return $result;
    }

    public function __toString(): string
    {
        return implode(' ', array_map(
            static fn (Option $option): string => (string) $option,
            $this->items,
        ));
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, Option>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): Option
    {
        return $this->items[$offset] ?? throw new \OutOfBoundsException("Offset {$offset} does not exist.");
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('OptionCollection is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('OptionCollection is immutable.');
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
