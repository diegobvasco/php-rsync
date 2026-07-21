<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Result;

it('calculates copied count correctly', function (): void {
    $result = new Result(
        copied: [
            new FileInfo('file1.txt', '/path/file1.txt', 100, time()),
            new FileInfo('file2.txt', '/path/file2.txt', 200, time()),
        ],
    );

    expect($result->copiedCount())->toBe(2);
});

it('calculates deleted count correctly', function (): void {
    $result = new Result(
        deleted: [
            new FileInfo('old.txt', '/path/old.txt', 50, time()),
        ],
    );

    expect($result->deletedCount())->toBe(1);
});

it('calculates skipped count correctly', function (): void {
    $result = new Result(
        skipped: [
            new FileInfo('skip.log', '/path/skip.log', 150, time()),
        ],
    );

    expect($result->skippedCount())->toBe(1);
});

it('calculates total bytes copied correctly', function (): void {
    $result = new Result(
        copied: [
            new FileInfo('file1.txt', '/path/file1.txt', 100, time()),
            new FileInfo('file2.txt', '/path/file2.txt', 200, time()),
        ],
    );

    expect($result->totalBytesCopied())->toBe(300);
});

it('calculates total bytes deleted correctly', function (): void {
    $result = new Result(
        deleted: [
            new FileInfo('old1.txt', '/path/old1.txt', 50, time()),
            new FileInfo('old2.txt', '/path/old2.txt', 75, time()),
        ],
    );

    expect($result->totalBytesDeleted())->toBe(125);
});

it('returns copied file paths', function (): void {
    $result = new Result(
        copied: [
            new FileInfo('file1.txt', '/path/file1.txt', 100, time()),
            new FileInfo('subdir/file2.txt', '/path/subdir/file2.txt', 200, time()),
        ],
    );

    expect($result->copiedPaths())->toBe(['file1.txt', 'subdir/file2.txt']);
});

it('returns deleted file paths', function (): void {
    $result = new Result(
        deleted: [
            new FileInfo('old.txt', '/path/old.txt', 50, time()),
        ],
    );

    expect($result->deletedPaths())->toBe(['old.txt']);
});

it('returns skipped file paths', function (): void {
    $result = new Result(
        skipped: [
            new FileInfo('skip.log', '/path/skip.log', 150, time()),
            new FileInfo('vendor/file.php', '/path/vendor/file.php', 250, time()),
        ],
    );

    expect($result->skippedPaths())->toBe(['skip.log', 'vendor/file.php']);
});

it('generates human readable summary', function (): void {
    $result = new Result(
        copied: [
            new FileInfo('file1.txt', '/path/file1.txt', 1024, time()),
        ],
        deleted: [
            new FileInfo('old.txt', '/path/old.txt', 512, time()),
        ],
        skipped: [
            new FileInfo('skip.log', '/path/skip.log', 256, time()),
        ],
    );

    $summary = $result->summary();

    expect($summary)->toContain('Copied: 1 file (1 KB)')
        ->and($summary)->toContain('Deleted: 1 file (512 B)')
        ->and($summary)->toContain('Skipped: 1 file');
});

it('handles empty result', function (): void {
    $result = new Result();

    expect($result->copiedCount())->toBe(0)
        ->and($result->deletedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(0)
        ->and($result->totalBytesCopied())->toBe(0)
        ->and($result->totalBytesDeleted())->toBe(0);
});

it('formats bytes correctly', function (): void {
    $file1 = new FileInfo('file1.txt', '/path/file1.txt', 1024 * 1024, time()); // 1 MB
    $result1 = new Result(copied: [$file1]);
    expect($result1->summary())->toContain('1 MB');

    $file2 = new FileInfo('file2.txt', '/path/file2.txt', 1024 * 1024 * 1024, time()); // 1 GB
    $result2 = new Result(copied: [$file2]);
    expect($result2->summary())->toContain('1 GB');

    $file3 = new FileInfo('file3.txt', '/path/file3.txt', 100 * 1024, time()); // 100 KB
    $result3 = new Result(copied: [$file3]);
    expect($result3->summary())->toContain('100 KB');
});
