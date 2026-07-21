<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Command;

use BadMethodCallException;
use DiegoVasconcelos\Rsync\Support\AbstractCollection;
use JsonSerializable;
use OutOfBoundsException;
use Override;
use Stringable;

/**
 * Immutable collection of Option objects.
 *
 * Options with the same key are merged into a single Option with multiple values.
 *
 * @extends AbstractCollection<Option>
 */
final readonly class OptionCollection extends AbstractCollection implements JsonSerializable, Stringable
{
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

        return new self($filtered);
    }

    /** @param  callable(Option, int): bool  $callback */
    public function filter(callable $callback): static
    {
        /** @var array<int, Option> $filtered */
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
     * @param  callable(Option, int): T  $callback
     * @return list<T>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    /** Get an option by key. */
    public function get(string $key): Option
    {
        return array_find($this->items, fn (Option $option): bool => $option->key === $key)
            ?? throw new OutOfBoundsException(sprintf("Option '%s' not found.", $key));
    }

    public function has(string $key): bool
    {
        return array_any($this->items, fn (Option $option): bool => $option->key === $key);
    }

    /**
     * Get all option keys.
     *
     * @return list<string>
     */
    public function keys(): array
    {
        return array_values(array_map(
            static fn (Option $option): string => $option->key,
            $this->items,
        ));
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

    #[Override]
    public function __toString(): string
    {
        return implode(' ', array_map(
            static fn (Option $option): string => (string) $option,
            $this->items,
        ));
    }

    /** @return array<string, string|array<int, string>> */
    #[Override]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
