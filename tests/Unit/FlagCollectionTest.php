<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Command\FlagCollection;
use DiegoVasconcelos\Rsync\Command\FlagType;

it('creates empty collection', function (): void {
    $collection = new FlagCollection();

    expect($collection->isEmpty())->toBeTrue()
        ->and($collection->count())->toBe(0);
});

it('creates collection from FlagType cases', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection->count())->toBe(2)
        ->and($collection->isEmpty())->toBeFalse();
});

it('adds flag immutably', function (): void {
    $collection = new FlagCollection();
    $newCollection = $collection->addFlag(FlagType::DELETE);

    expect($collection->isEmpty())->toBeTrue()
        ->and($newCollection->count())->toBe(1);
});

it('deduplicates flags by enum case', function (): void {
    $collection = new FlagCollection();
    $collection = $collection->addFlag(FlagType::DELETE);
    $collection = $collection->addFlag(FlagType::DELETE);

    expect($collection->count())->toBe(1);
});

it('adds flag via add() with FlagType', function (): void {
    $collection = new FlagCollection();
    $collection = $collection->add(FlagType::RECURSIVE);

    expect($collection->count())->toBe(1)
        ->and($collection->first())->toBe(FlagType::RECURSIVE);
});

it('checks contains with FlagType enum', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection->contains(FlagType::DELETE))->toBeTrue()
        ->and($collection->contains(FlagType::ARCHIVE))->toBeFalse();
});

it('removes flag by FlagType enum', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);
    $newCollection = $collection->remove(FlagType::DELETE);

    expect($newCollection->count())->toBe(1)
        ->and($newCollection->first())->toBe(FlagType::RECURSIVE);
});

it('throws exception for non-FlagType value', function (): void {
    $collection = new FlagCollection();
    $collection->add('not-a-flag');
})->throws(BadMethodCallException::class);

it('merges collections without duplicates', function (): void {
    $a = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);
    $b = new FlagCollection([FlagType::RECURSIVE, FlagType::ARCHIVE]);
    $merged = $a->merge($b);

    expect($merged->count())->toBe(3)
        ->and($merged->names())->toBe(['--delete', '--recursive', '--archive']);
});

it('gets flag names', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection->names())->toBe(['--delete', '--recursive']);
});

it('converts to array of flag values', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection->toArray())->toBe(['--delete', '--recursive']);
});

it('converts to string', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect((string) $collection)->toBe('--delete --recursive');
});

it('supports foreach', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);
    $values = [];

    foreach ($collection as $flag) {
        $values[] = $flag->value;
    }

    expect($values)->toBe(['--delete', '--recursive']);
});

it('supports array access', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection[0])->toBe(FlagType::DELETE)
        ->and($collection[1])->toBe(FlagType::RECURSIVE);
});

it('throws on offset set', function (): void {
    $collection = new FlagCollection();
    $collection[0] = FlagType::DELETE;
})->throws(BadMethodCallException::class);

it('throws on offset unset', function (): void {
    $collection = new FlagCollection([FlagType::DELETE]);
    unset($collection[0]);
})->throws(BadMethodCallException::class);

it('gets first and last items', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE, FlagType::ARCHIVE]);

    expect($collection->first())->toBe(FlagType::DELETE)
        ->and($collection->last())->toBe(FlagType::ARCHIVE);
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
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE, FlagType::ARCHIVE]);
    $filtered = $collection->filter(fn (FlagType $flag): bool => $flag === FlagType::DELETE);

    expect($filtered->count())->toBe(1)
        ->and($filtered->first())->toBe(FlagType::DELETE);
});

it('maps collection to values', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);
    $mapped = $collection->map(fn (FlagType $flag): string => $flag->value);

    expect($mapped)->toBe(['--delete', '--recursive']);
});

it('serializes to JSON', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect(json_encode($collection))->toBe('["--delete","--recursive"]');
});

it('implements countable', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect(count($collection))->toBe(2);
});

it('all returns all items', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect($collection->all())->toHaveCount(2);
});

it('offsetExists checks if offset is set', function (): void {
    $collection = new FlagCollection([FlagType::DELETE, FlagType::RECURSIVE]);

    expect(isset($collection[0]))->toBeTrue()
        ->and(isset($collection[99]))->toBeFalse();
});
