<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\FileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait FileScanner
{
    /**
     * Recursively scan a directory and return all FileInfo objects (unfiltered).
     *
     * @return array<string, FileInfo>
     */
    protected function scanAllFiles(string $path): array
    {
        $files = [];

        if (! is_dir($path)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relativePath = ltrim(substr($file->getPathname(), strlen($path)), DIRECTORY_SEPARATOR);

            $files[$relativePath] = new FileInfo(
                relativePath: $relativePath,
                absolutePath: $file->getPathname(),
                size: $file->getSize(),
                mtime: $file->getMTime(),
                checksum: hash_file('xxh128', $file->getPathname()) ?: '',
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
