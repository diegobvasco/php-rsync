<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Command\FlagType;

it('has 35 cases', function (): void {
    expect(FlagType::cases())->toHaveCount(35);
});

it('has string values prefixed with --', function (): void {
    foreach (FlagType::cases() as $flag) {
        expect($flag->value)->toStartWith('--');
    }
});

it('resolves cases from values via from()', function (): void {
    expect(FlagType::from('--delete'))->toBe(FlagType::DELETE)
        ->and(FlagType::from('--recursive'))->toBe(FlagType::RECURSIVE)
        ->and(FlagType::from('--archive'))->toBe(FlagType::ARCHIVE);
});

it('returns null for unknown values via tryFrom()', function (): void {
    expect(FlagType::tryFrom('--custom-flag'))->toBeNull();
});

it('has unique values', function (): void {
    $values = array_map(fn (FlagType $f): string => $f->value, FlagType::cases());

    expect(array_unique($values))->toHaveCount(35);
});
