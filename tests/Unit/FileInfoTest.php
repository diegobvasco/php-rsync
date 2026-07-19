<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;

it('stores file properties correctly', function (): void {
    $file = new FileInfo(
        relativePath: 'src/app.php',
        absolutePath: '/path/to/src/app.php',
        size: 1024,
        mtime: 1234567890,
    );

    expect($file->relativePath)->toBe('src/app.php')
        ->and($file->absolutePath)->toBe('/path/to/src/app.php')
        ->and($file->size)->toBe(1024)
        ->and($file->mtime)->toBe(1234567890);
});

it('formats file size in bytes', function (): void {
    $file = new FileInfo('file.txt', '/path/file.txt', 500, time());

    expect($file->formattedSize())->toBe('500 B');
});

it('formats file size in kilobytes', function (): void {
    $file = new FileInfo('file.txt', '/path/file.txt', 1536, time()); // 1.5 KB

    expect($file->formattedSize())->toBe('1.5 KB');
});

it('formats file size in megabytes', function (): void {
    $file = new FileInfo('file.txt', '/path/file.txt', 1048576, time()); // 1 MB

    expect($file->formattedSize())->toBe('1 MB');
});

it('formats file size in gigabytes', function (): void {
    $file = new FileInfo('file.txt', '/path/file.txt', 1073741824, time()); // 1 GB

    expect($file->formattedSize())->toBe('1 GB');
});

it('formats modification time as ISO 8601', function (): void {
    $file = new FileInfo('file.txt', '/path/file.txt', 100, 1234567890);

    expect($file->formattedMtime())->toBe('2009-02-13T23:31:30+00:00');
});

it('stores checksum correctly', function (): void {
    $file = new FileInfo(
        relativePath: 'src/app.php',
        absolutePath: '/path/to/src/app.php',
        size: 1024,
        mtime: 1234567890,
        checksum: 'abc123hash',
    );

    expect($file->checksum)->toBe('abc123hash');
});

it('defaults checksum to empty string', function (): void {
    $file = new FileInfo(
        relativePath: 'file.txt',
        absolutePath: '/path/file.txt',
        size: 100,
        mtime: time(),
    );

    expect($file->checksum)->toBe('');
});
