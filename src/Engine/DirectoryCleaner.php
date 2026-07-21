<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Engine;

use DiegoVasconcelos\Rsync\Filesystem\Filesystem;

/**
 * @internal Removes empty directories from a destination tree after a sync.
 */
final readonly class DirectoryCleaner
{
    public function __construct(
        private Filesystem $filesystem,
    ) {}

    /**
     * Remove empty directories beneath $destination (deepest first).
     * No-op when $destination is empty or does not exist.
     */
    public function cleanup(string $destination): void
    {
        if ($destination === '' || ! $this->filesystem->isDir($destination)) {
            return;
        }

        $fs = $this->filesystem;

        foreach ($fs->scanEntriesDeepFirst($destination) as $entry) {
            if ($fs->isDir($entry) && $fs->isEmptyDirectory($entry)) {
                $fs->removeDir($entry);
            }
        }
    }
}
