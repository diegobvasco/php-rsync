<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Filesystem;

trait FileScanner
{
    abstract protected function filesystem(): Filesystem;

    /**
     * Recursively scan a directory and return all FileInfo objects (unfiltered).
     *
     * @return array<string, FileInfo>
     */
    protected function scanAllFiles(string $path): array
    {
        $files = [];
        $fs = $this->filesystem();

        foreach ($fs->scanFiles($path) as $absolutePath) {
            $relativePath = ltrim(substr((string) $absolutePath, strlen($path)), DIRECTORY_SEPARATOR);

            $files[$relativePath] = new FileInfo(
                relativePath: $relativePath,
                absolutePath: $absolutePath,
                size: $fs->size($absolutePath),
                mtime: $fs->mtime($absolutePath),
                checksum: $fs->hash($absolutePath),
            );
        }

        return $files;
    }

    /**
     * Filter files, separating included from excluded based on patterns.
     *
     * @param  array<string, FileInfo>  $files
     * @param  array<string>  $excludes
     * @return array{included: array<string, FileInfo>, excluded: array<string, FileInfo>}
     */
    protected function filterByExclusions(array $files, array $excludes): array
    {
        $included = [];
        $excluded = [];

        foreach ($files as $relativePath => $file) {
            if ($this->matchesExclusion($relativePath, $excludes)) {
                $excluded[$relativePath] = $file;
            } else {
                $included[$relativePath] = $file;
            }
        }

        return ['included' => $included, 'excluded' => $excluded];
    }
}
