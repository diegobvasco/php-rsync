<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Filesystem;

trait FileOperations
{
    abstract protected function filesystem(): Filesystem;

    /**
     * Copy a single file from source to destination.
     */
    protected function copyFile(string $from, string $to): bool
    {
        $fs = $this->filesystem();
        $directory = dirname($to);

        if (! $fs->isDir($directory)) {
            $fs->mkdir($directory);
        }

        return $fs->copy($from, $to);
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
        $fs = $this->filesystem();

        if (! $fs->isFile($path)) {
            return false;
        }

        return $fs->deleteFile($path);
    }
}
