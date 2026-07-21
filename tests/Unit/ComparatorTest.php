<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Comparator;
use DiegoVasconcelos\Rsync\FileInfo;

beforeEach(function (): void {
    $this->comparator = new Comparator();
});

it('syncs when size differs (no checksum)', function (): void {
    $source = new FileInfo('f.txt', '/s/f.txt', 20, 1000);
    $dest = new FileInfo('f.txt', '/d/f.txt', 10, 1000);

    expect($this->comparator->shouldSync($source, $dest))->toBeTrue();
});

it('syncs when mtime differs (no checksum)', function (): void {
    $source = new FileInfo('f.txt', '/s/f.txt', 10, 2000);
    $dest = new FileInfo('f.txt', '/d/f.txt', 10, 1000);

    expect($this->comparator->shouldSync($source, $dest))->toBeTrue();
});

it('does not sync when size and mtime match', function (): void {
    $source = new FileInfo('f.txt', '/s/f.txt', 10, 1000, fn (): string => 'hash_a');
    $dest = new FileInfo('f.txt', '/d/f.txt', 10, 1000, fn (): string => 'hash_b');

    expect($this->comparator->shouldSync($source, $dest))->toBeFalse();
});

it('syncs when checksum differs (checksum mode)', function (): void {
    $source = new FileInfo('f.txt', '/s/f.txt', 10, 1000, fn (): string => 'hash_a');
    $dest = new FileInfo('f.txt', '/d/f.txt', 10, 1000, fn (): string => 'hash_b');

    expect($this->comparator->shouldSync($source, $dest, useChecksum: true))->toBeTrue();
});

it('does not sync when checksum matches (checksum mode)', function (): void {
    $source = new FileInfo('f.txt', '/s/f.txt', 10, 1000, fn (): string => 'same');
    $dest = new FileInfo('f.txt', '/d/f.txt', 10, 9999, fn (): string => 'same');

    expect($this->comparator->shouldSync($source, $dest, useChecksum: true))->toBeFalse();
});
