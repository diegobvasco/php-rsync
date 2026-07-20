<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\FileInfo;

trait FileOperations
{
    /**
     * Copy a single file from source to destination.
     */
    protected function copyFile(string $from, string $to): bool
    {
        $directory = dirname($to);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        return copy($from, $to);
    }

    /**
     * Check if a file should be synced based on modification time, size, or checksum.
     */
    protected function shouldSync(FileInfo $source, FileInfo $destination, bool $useChecksum = false): bool
    {
        if ($useChecksum) {
            return $source->checksum !== $destination->checksum;
        }

        return $source->mtime !== $destination->mtime
            || $source->size !== $destination->size;
    }

    /**
     * Delete a single file.
     */
    protected function deleteFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }
}
