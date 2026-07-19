<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * @param  array<int, FileInfo>  $copied
 * @param  array<int, FileInfo>  $deleted
 * @param  array<int, FileInfo>  $skipped
 */
final readonly class Result
{
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
     * Format bytes to human readable size.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Generate human-readable summary.
     */
    public function summary(): string
    {
        $parts = [];

        $parts[] = 'Copied: '.$this->copiedCount().' files ('.$this->formatBytes($this->totalBytesCopied()).')';
        $parts[] = 'Deleted: '.$this->deletedCount().' files ('.$this->formatBytes($this->totalBytesDeleted()).')';
        $parts[] = 'Skipped: '.$this->skippedCount().' files';

        return implode("\n", $parts);
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
