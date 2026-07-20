<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Flag;

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
