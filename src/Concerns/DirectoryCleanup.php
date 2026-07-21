<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\Filesystem;

trait DirectoryCleanup
{
    abstract protected function filesystem(): Filesystem;

    /**
     * Remove empty directories from destination after sync.
     */
    protected function cleanupEmptyDirectories(): void
    {
        $destination = $this->destination ?? '';

        if ($destination === '' || ! $this->filesystem()->isDir($destination)) {
            return;
        }

        $fs = $this->filesystem();

        foreach ($fs->scanEntriesDeepFirst($destination) as $entry) {
            if ($fs->isDir($entry) && $fs->isEmptyDirectory($entry)) {
                $fs->removeDir($entry);
            }
        }
    }
}
