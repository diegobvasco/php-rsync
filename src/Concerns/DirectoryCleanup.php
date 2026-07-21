<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

trait DirectoryCleanup
{
    /**
     * Remove empty directories from destination after sync.
     */
    protected function cleanupEmptyDirectories(): void
    {
        $destination = $this->destination ?? '';

        if ($destination === '' || ! is_dir($destination)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($destination, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            if ($item->isDir() && $this->isEmptyDirectory($item->getPathname())) {
                rmdir($item->getPathname());
            }
        }
    }

    /**
     * Check if a directory is empty.
     */
    protected function isEmptyDirectory(string $path): bool
    {
        if (! is_dir($path)) {
            return false;
        }

        /** @var list<string>|false $entries */
        $entries = scandir($path);

        return $entries !== false && count($entries) === 2; // Only . and ..
    }
}
