<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Default Filesystem backed by PHP's global FS functions.
 */
final class LocalFilesystem implements Filesystem
{
    #[Override]
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    #[Override]
    public function isFile(string $path): bool
    {
        return is_file($path);
    }

    #[Override]
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    #[Override]
    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    #[Override]
    public function mkdir(string $path): void
    {
        \mkdir($path, recursive: true);
    }

    #[Override]
    public function copy(string $from, string $to): bool
    {
        return copy($from, $to);
    }

    #[Override]
    public function deleteFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    #[Override]
    public function removeDir(string $path): bool
    {
        return rmdir($path);
    }

    #[Override]
    public function size(string $path): int
    {
        return is_file($path) ? (filesize($path) ?: 0) : 0;
    }

    #[Override]
    public function mtime(string $path): int
    {
        return is_file($path) ? (filemtime($path) ?: 0) : 0;
    }

    /** Compute the xxh128 hash of a file's contents. */
    #[Override]
    public function hash(string $path): string
    {
        $hash = is_file($path) ? hash_file('xxh128', $path) : false;

        return $hash !== false ? $hash : '';
    }

    #[Override]
    public function isEmptyDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        $entries = scandir($path);

        /** @var list<string>|false $entries */
        return $entries !== false && count($entries) === 2; // Only . and ..
    }

    #[Override]
    public function scanFiles(string $path): iterable
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->isFile()) {
                yield $file->getPathname();
            }
        }
    }

    #[Override]
    public function scanEntriesDeepFirst(string $path): iterable
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            yield $item->getPathname();
        }
    }
}
