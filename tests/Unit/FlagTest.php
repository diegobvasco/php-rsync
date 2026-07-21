<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Flag;
use DiegoVasconcelos\Rsync\FlagType;

it('stores flag name correctly', function (): void {
    $flag = new Flag('--delete');

    expect($flag->name)->toBe('--delete');
});

it('converts to string correctly', function (): void {
    $flag = new Flag('--recursive');

    expect((string) $flag)->toBe('--recursive');
});

it('is immutable', function (): void {
    $flag = new Flag('--archive');

    expect($flag->name)->toBe('--archive');
});

it('creates flags with different names', function (): void {
    $flags = ['--delete', '--recursive', '--archive', '--perms', '--times'];

    foreach ($flags as $name) {
        $flag = new Flag($name);
        expect($flag->name)->toBe($name);
        expect((string) $flag)->toBe($name);
    }
});

it('creates flag from FlagType enum case', function (): void {
    $flag = Flag::fromType(FlagType::DELETE);

    expect($flag->name)->toBe('--delete')
        ->and((string) $flag)->toBe('--delete');
});

it('resolves known flag via tryFromValue', function (): void {
    $flag = Flag::tryFromValue('--delete');

    expect($flag)->not->toBeNull()
        ->and($flag->name)->toBe('--delete');
});

it('resolves known flag without dashes via tryFromValue', function (): void {
    $flag = Flag::tryFromValue('recursive');

    expect($flag)->not->toBeNull()
        ->and($flag->name)->toBe('--recursive');
});

it('returns null for unknown flag via tryFromValue', function (): void {
    expect(Flag::tryFromValue('--custom-flag'))->toBeNull();
});

it('returns all known flag names', function (): void {
    $names = Flag::getAllNames();

    expect($names)->toContain('--delete', '--recursive', '--archive')
        ->and($names)->toHaveCount(35)
        ->and(array_unique($names))->toHaveCount(35);
});
