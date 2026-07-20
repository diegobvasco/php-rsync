<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Option;
use DiegoVasconcelos\Rsync\OptionCollection;

it('creates empty collection', function (): void {
    $collection = new OptionCollection();

    expect($collection->isEmpty())->toBeTrue()
        ->and($collection->count())->toBe(0);
});

it('adds option immutably', function (): void {
    $collection = new OptionCollection();
    $option = new Option('exclude', ['*.log']);
    $newCollection = $collection->add($option);

    expect($collection->isEmpty())->toBeTrue()
        ->and($newCollection->count())->toBe(1);
});

it('merges options with same key', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->add(new Option('exclude', ['*.log']));
    $collection = $collection->add(new Option('exclude', ['cache/']));

    expect($collection->count())->toBe(1)
        ->and($collection->get('exclude')->values)->toBe(['*.log', 'cache/']);
});

it('adds option using shorthand', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    expect($collection->count())->toBe(1)
        ->and($collection->get('exclude')->values)->toBe(['*.log']);
});

it('throws exception for non-option object', function (): void {
    $collection = new OptionCollection();
    $collection->add('not-an-option');
})->throws(BadMethodCallException::class);

it('removes option by key', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    $newCollection = $collection->remove('exclude');

    expect($collection->count())->toBe(2)
        ->and($newCollection->count())->toBe(1)
        ->and($newCollection->has('exclude'))->toBeFalse()
        ->and($newCollection->has('backup-dir'))->toBeTrue();
});

it('merges collections', function (): void {
    $a = new OptionCollection();
    $a = $a->addOption('exclude', '*.log');

    $b = new OptionCollection();
    $b = $b->addOption('backup-dir', '/tmp');

    $merged = $a->merge($b);

    expect($merged->count())->toBe(2)
        ->and($merged->has('exclude'))->toBeTrue()
        ->and($merged->has('backup-dir'))->toBeTrue();
});

it('merges collections with duplicate keys', function (): void {
    $a = new OptionCollection();
    $a = $a->addOption('exclude', '*.log');

    $b = new OptionCollection();
    $b = $b->addOption('exclude', 'cache/');

    $merged = $a->merge($b);

    expect($merged->count())->toBe(1)
        ->and($merged->get('exclude')->values)->toBe(['*.log', 'cache/']);
});

it('checks if option exists', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    expect($collection->has('exclude'))->toBeTrue()
        ->and($collection->has('backup-dir'))->toBeFalse();
});

it('gets option by key', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    $option = $collection->get('exclude');

    expect($option->key)->toBe('exclude')
        ->and($option->values)->toBe(['*.log']);
});

it('throws on get nonexistent option', function (): void {
    $collection = new OptionCollection();
    $collection->get('nonexistent');
})->throws(OutOfBoundsException::class);

it('gets option keys', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    expect($collection->keys())->toBe(['exclude', 'backup-dir']);
});

it('converts to array', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    expect($collection->toArray())->toBe([
        'exclude' => '*.log',
        'backup-dir' => '/tmp',
    ]);
});

it('converts multi-value option to array', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->add(new Option('exclude', ['*.log', 'cache/']));

    expect($collection->toArray())->toBe([
        'exclude' => ['*.log', 'cache/'],
    ]);
});

it('converts to string', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    expect((string) $collection)->toBe("--exclude='*.log' --backup-dir='/tmp'");
});

it('supports foreach', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    $keys = [];

    foreach ($collection as $option) {
        $keys[] = $option->key;
    }

    expect($keys)->toBe(['exclude', 'backup-dir']);
});

it('supports array access', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    expect($collection[0]->key)->toBe('exclude');
});

it('throws on offset set', function (): void {
    $collection = new OptionCollection();
    $collection[0] = new Option('exclude');
})->throws(BadMethodCallException::class);

it('throws on offset unset', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    unset($collection[0]);
})->throws(BadMethodCallException::class);

it('gets first and last items', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');
    $collection = $collection->addOption('suffix', '.bak');

    expect($collection->first()->key)->toBe('exclude')
        ->and($collection->last()->key)->toBe('suffix');
});

it('throws on first of empty collection', function (): void {
    $collection = new OptionCollection();
    $collection->first();
})->throws(BadMethodCallException::class);

it('throws on last of empty collection', function (): void {
    $collection = new OptionCollection();
    $collection->last();
})->throws(BadMethodCallException::class);

it('filters collection', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    $filtered = $collection->filter(fn (Option $option): bool => $option->key === 'exclude');

    expect($filtered->count())->toBe(1)
        ->and($filtered->first()->key)->toBe('exclude');
});

it('maps collection', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    $mapped = $collection->map(fn (Option $option): string => $option->key);

    expect($mapped)->toBe(['exclude', 'backup-dir']);
});

it('serializes to JSON', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    expect(json_encode($collection, JSON_UNESCAPED_SLASHES))->toBe('{"exclude":"*.log","backup-dir":"/tmp"}');
});

it('implements countable', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');
    $collection = $collection->addOption('backup-dir', '/tmp');

    expect(count($collection))->toBe(2);
});

it('all returns all items', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    expect($collection->all())->toHaveCount(1);
});

it('offsetExists checks if offset is set', function (): void {
    $collection = new OptionCollection();
    $collection = $collection->addOption('exclude', '*.log');

    expect(isset($collection[0]))->toBeTrue()
        ->and(isset($collection[99]))->toBeFalse();
});
