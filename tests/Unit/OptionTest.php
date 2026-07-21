<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Option;

it('stores option key and values correctly', function (): void {
    $option = new Option('exclude', ['*.log', 'cache/']);

    expect($option->key)->toBe('exclude')
        ->and($option->values)->toBe(['*.log', 'cache/']);
});

it('stores option with no values', function (): void {
    $option = new Option('dry-run');

    expect($option->key)->toBe('dry-run')
        ->and($option->values)->toBe([]);
});

it('adds value immutably', function (): void {
    $option = new Option('exclude', ['*.log']);
    $newOption = $option->addValue('cache/');

    expect($option->values)->toBe(['*.log'])
        ->and($newOption->values)->toBe(['*.log', 'cache/']);
});

it('converts to string with values', function (): void {
    $option = new Option('exclude', ['*.log', 'cache/']);

    expect((string) $option)->toBe("--exclude='*.log' --exclude='cache/'");
});

it('converts to string without values', function (): void {
    $option = new Option('dry-run');

    expect((string) $option)->toBe('--dry-run');
});

it('converts single value to string', function (): void {
    $option = new Option('backup-dir', ['/tmp/backup']);

    expect((string) $option)->toBe("--backup-dir='/tmp/backup'");
});

it('escapes single quotes in values', function (): void {
    $option = new Option('exclude', ["it's/*.log"]);

    expect((string) $option)->toBe("--exclude='it'\\''s/*.log'");
});
