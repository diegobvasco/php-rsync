<?php

declare(strict_types=1);

namespace Tests\Support;

use DiegoVasconcelos\Rsync\Filesystem;

/**
 * Transparent forwarding decorator for the Filesystem interface.
 *
 * Useful as a base for test doubles that override only a few methods
 * (e.g., to simulate unreadable paths).
 */
class FilesystemDecorator implements Filesystem
{
    public function __construct(
        private readonly Filesystem $inner,
    ) {}

    #[\Override]
    public function exists(string $path): bool
    {
        return $this->inner->exists($path);
    }

    #[\Override]
    public function isFile(string $path): bool
    {
        return $this->inner->isFile($path);
    }

    #[\Override]
    public function isDir(string $path): bool
    {
        return $this->inner->isDir($path);
    }

    #[\Override]
    public function isReadable(string $path): bool
    {
        return $this->inner->isReadable($path);
    }

    #[\Override]
    public function mkdir(string $path): void
    {
        $this->inner->mkdir($path);
    }

    #[\Override]
    public function copy(string $from, string $to): bool
    {
        return $this->inner->copy($from, $to);
    }

    #[\Override]
    public function deleteFile(string $path): bool
    {
        return $this->inner->deleteFile($path);
    }

    #[\Override]
    public function removeDir(string $path): bool
    {
        return $this->inner->removeDir($path);
    }

    #[\Override]
    public function size(string $path): int
    {
        return $this->inner->size($path);
    }

    #[\Override]
    public function mtime(string $path): int
    {
        return $this->inner->mtime($path);
    }

    #[\Override]
    public function hash(string $path): string
    {
        return $this->inner->hash($path);
    }

    #[\Override]
    public function isEmptyDirectory(string $path): bool
    {
        return $this->inner->isEmptyDirectory($path);
    }

    #[\Override]
    public function scanFiles(string $path): iterable
    {
        return $this->inner->scanFiles($path);
    }

    #[\Override]
    public function scanEntriesDeepFirst(string $path): iterable
    {
        return $this->inner->scanEntriesDeepFirst($path);
    }
}
