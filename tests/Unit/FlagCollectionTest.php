<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Flag;
use DiegoVasconcelos\Rsync\FlagCollection;

it('creates empty collection', function (): void {
    $collection = new FlagCollection();

    expect($collection->isEmpty())->toBeTrue()
        ->and($collection->count())->toBe(0);
});

it('creates collection from array', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection->count())->toBe(2)
        ->and($collection->isEmpty())->toBeFalse();
});

it('adds flag immutably', function (): void {
    $collection = new FlagCollection();
    $newCollection = $collection->addFlag('--delete');

    expect($collection->isEmpty())->toBeTrue()
        ->and($newCollection->count())->toBe(1);
});

it('deduplicates flags by name', function (): void {
    $collection = new FlagCollection();
    $collection = $collection->addFlag('--delete');
    $collection = $collection->addFlag('--delete');

    expect($collection->count())->toBe(1);
});

it('adds flag object', function (): void {
    $collection = new FlagCollection();
    $flag = new Flag('--recursive');
    $collection = $collection->add($flag);

    expect($collection->count())->toBe(1)
        ->and($collection->first()->name)->toBe('--recursive');
});

it('throws exception for non-flag object', function (): void {
    $collection = new FlagCollection();
    $collection->add('not-a-flag');
})->throws(BadMethodCallException::class);

it('removes flag by name', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);
    $newCollection = $collection->remove('--delete');

    expect($collection->count())->toBe(2)
        ->and($newCollection->count())->toBe(1)
        ->and($newCollection->first()->name)->toBe('--recursive');
});

it('merges collections without duplicates', function (): void {
    $a = FlagCollection::fromArray(['--delete', '--recursive']);
    $b = FlagCollection::fromArray(['--recursive', '--archive']);
    $merged = $a->merge($b);

    expect($merged->count())->toBe(3)
        ->and($merged->names())->toBe(['--delete', '--recursive', '--archive']);
});

it('checks if flag exists', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection->contains('--delete'))->toBeTrue()
        ->and($collection->contains('--archive'))->toBeFalse();
});

it('gets flag names', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection->names())->toBe(['--delete', '--recursive']);
});

it('converts to array', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection->toArray())->toBe(['--delete', '--recursive']);
});

it('converts to string', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect((string) $collection)->toBe('--delete --recursive');
});

it('supports foreach', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);
    $names = [];

    foreach ($collection as $flag) {
        $names[] = $flag->name;
    }

    expect($names)->toBe(['--delete', '--recursive']);
});

it('supports array access', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection[0]->name)->toBe('--delete')
        ->and($collection[1]->name)->toBe('--recursive');
});

it('throws on offset set', function (): void {
    $collection = new FlagCollection();
    $collection[0] = new Flag('--delete');
})->throws(BadMethodCallException::class);

it('throws on offset unset', function (): void {
    $collection = FlagCollection::fromArray(['--delete']);
    unset($collection[0]);
})->throws(BadMethodCallException::class);

it('gets first and last items', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive', '--archive']);

    expect($collection->first()->name)->toBe('--delete')
        ->and($collection->last()->name)->toBe('--archive');
});

it('throws on first of empty collection', function (): void {
    $collection = new FlagCollection();
    $collection->first();
})->throws(BadMethodCallException::class);

it('throws on last of empty collection', function (): void {
    $collection = new FlagCollection();
    $collection->last();
})->throws(BadMethodCallException::class);

it('filters collection', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive', '--archive']);
    $filtered = $collection->filter(fn (Flag $flag): bool => str_contains($flag->name, 'delete'));

    expect($filtered->count())->toBe(1)
        ->and($filtered->first()->name)->toBe('--delete');
});

it('maps collection', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);
    $mapped = $collection->map(fn (Flag $flag): string => $flag->name);

    expect($mapped)->toBe(['--delete', '--recursive']);
});

it('serializes to JSON', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect(json_encode($collection))->toBe('["--delete","--recursive"]');
});

it('implements countable', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect(count($collection))->toBe(2);
});

it('all returns all items', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect($collection->all())->toHaveCount(2);
});

it('offsetExists checks if offset is set', function (): void {
    $collection = FlagCollection::fromArray(['--delete', '--recursive']);

    expect(isset($collection[0]))->toBeTrue()
        ->and(isset($collection[99]))->toBeFalse();
});
