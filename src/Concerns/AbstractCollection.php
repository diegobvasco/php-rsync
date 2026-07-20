<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Countable;
use IteratorAggregate;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 * @implements ArrayAccess<int, T>
 */
abstract readonly class AbstractCollection implements ArrayAccess, Countable, IteratorAggregate
{
    /** @var array<int, T> */
    protected array $items;

    /**
     * @param  array<int, T>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    /**
     * @return array<int, T>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @return T
     */
    public function first(): mixed
    {
        return $this->items[0] ?? throw new BadMethodCallException('Collection is empty.');
    }

    /**
     * @return T
     */
    public function last(): mixed
    {
        if ($this->items === []) {
            throw new BadMethodCallException('Collection is empty.');
        }

        return array_last($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? throw new \OutOfBoundsException(sprintf('Offset %d does not exist.', $offset));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException(static::class.' is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException(static::class.' is immutable.');
    }
}
