<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\ByteFormatter;

/**
 * Immutable result of a sync operation.
 */
final readonly class Result
{
    use ByteFormatter;

    /**
     * @param  array<int, FileInfo>  $copied
     * @param  array<int, FileInfo>  $deleted
     * @param  array<int, FileInfo>  $skipped
     */
    public function __construct(
        public array $copied = [],
        public array $deleted = [],
        public array $skipped = [],
    ) {}

    /**
     * Get total number of copied files.
     */
    public function copiedCount(): int
    {
        return count($this->copied);
    }

    /**
     * Get total number of deleted files.
     */
    public function deletedCount(): int
    {
        return count($this->deleted);
    }

    /**
     * Get total number of skipped files.
     */
    public function skippedCount(): int
    {
        return count($this->skipped);
    }

    /**
     * Get total bytes copied.
     */
    public function totalBytesCopied(): int
    {
        return array_reduce(
            $this->copied,
            static fn (int $carry, FileInfo $file): int => $carry + $file->size,
            0,
        );
    }

    /**
     * Get total bytes deleted.
     */
    public function totalBytesDeleted(): int
    {
        return array_reduce(
            $this->deleted,
            static fn (int $carry, FileInfo $file): int => $carry + $file->size,
            0,
        );
    }

    /**
     * Generate human-readable summary.
     */
    public function summary(): string
    {
        $parts = [];

        $parts[] = sprintf('Copied: %d %s (%s)', $this->copiedCount(), $this->pluralizeFile($this->copiedCount()), self::formatBytes($this->totalBytesCopied()));
        $parts[] = sprintf('Deleted: %d %s (%s)', $this->deletedCount(), $this->pluralizeFile($this->deletedCount()), self::formatBytes($this->totalBytesDeleted()));
        $parts[] = sprintf('Skipped: %d %s', $this->skippedCount(), $this->pluralizeFile($this->skippedCount()));

        return implode("\n", $parts);
    }

    /**
     * Return "file" or "files" based on the count.
     */
    private function pluralizeFile(int $count): string
    {
        return $count === 1 ? 'file' : 'files';
    }

    /**
     * Get copied file paths.
     *
     * @return array<int, string>
     */
    public function copiedPaths(): array
    {
        return array_map(
            static fn (FileInfo $file): string => $file->relativePath,
            $this->copied,
        );
    }

    /**
     * Get deleted file paths.
     *
     * @return array<int, string>
     */
    public function deletedPaths(): array
    {
        return array_map(
            static fn (FileInfo $file): string => $file->relativePath,
            $this->deleted,
        );
    }

    /**
     * Get skipped file paths.
     *
     * @return array<int, string>
     */
    public function skippedPaths(): array
    {
        return array_map(
            static fn (FileInfo $file): string => $file->relativePath,
            $this->skipped,
        );
    }
}
