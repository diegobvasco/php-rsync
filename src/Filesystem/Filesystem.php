<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Filesystem;

/**
 * Abstraction over filesystem operations used by the sync engine.
 *
 * Allows decoupling the domain logic from PHP's global FS functions and
 * enables testing against an in-memory implementation.
 */
interface Filesystem
{
    public function exists(string $path): bool;

    public function isFile(string $path): bool;

    public function isDir(string $path): bool;

    public function isReadable(string $path): bool;

    public function mkdir(string $path): void;

    public function copy(string $from, string $to): bool;

    public function deleteFile(string $path): bool;

    public function removeDir(string $path): bool;

    public function size(string $path): int;

    public function mtime(string $path): int;

    /** Compute the xxh128 hash of a file's contents. */
    public function hash(string $path): string;

    public function isEmptyDirectory(string $path): bool;

    /**
     * Yield absolute paths of every file beneath $path (recursively).
     *
     * @return iterable<string>
     */
    public function scanFiles(string $path): iterable;

    /**
     * Yield absolute paths of every entry (files and directories) beneath
     * $path, deepest-first, so inner directories are visited before their
     * parents (required for empty-directory cleanup).
     *
     * @return iterable<string>
     */
    public function scanEntriesDeepFirst(string $path): iterable;
}
