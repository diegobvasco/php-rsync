<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * @internal Recursively scans directories into FileInfo maps and splits
 * them into included/excluded sets based on glob patterns.
 */
final readonly class FileScanner
{
    public function __construct(
        private Filesystem $filesystem,
        private GlobMatcher $matcher,
    ) {}

    /**
     * Recursively scan a directory and return all FileInfo objects (unfiltered).
     *
     * @return array<string, FileInfo>
     */
    public function scan(string $path): array
    {
        $files = [];

        foreach ($this->filesystem->scanFiles($path) as $absolutePath) {
            $relativePath = ltrim(substr($absolutePath, strlen($path)), DIRECTORY_SEPARATOR);
            $files[$relativePath] = $this->buildFileInfo($relativePath, $absolutePath);
        }

        return $files;
    }

    /**
     * Look up a single file beneath $basePath (returns null when absent).
     *
     * Used to avoid scanning the whole destination tree when no deletion
     * mode is enabled.
     */
    public function fileAt(string $basePath, string $relativePath): ?FileInfo
    {
        $absolutePath = $basePath.DIRECTORY_SEPARATOR.$relativePath;

        if (! $this->filesystem->isFile($absolutePath)) {
            return null;
        }

        return $this->buildFileInfo($relativePath, $absolutePath);
    }

    /**
     * Build a FileInfo with a lazily-resolved checksum.
     */
    private function buildFileInfo(string $relativePath, string $absolutePath): FileInfo
    {
        $fs = $this->filesystem;

        return new FileInfo(
            relativePath: $relativePath,
            absolutePath: $absolutePath,
            size: $fs->size($absolutePath),
            mtime: $fs->mtime($absolutePath),
            checksumProvider: fn (): string => $fs->hash($absolutePath),
        );
    }

    /**
     * Split files into included/excluded based on exclusion patterns.
     *
     * @param  array<string, FileInfo>  $files
     * @param  list<string>  $excludes
     * @return array{included: array<string, FileInfo>, excluded: array<string, FileInfo>}
     */
    public function partition(array $files, array $excludes): array
    {
        $included = [];
        $excluded = [];

        foreach ($files as $relativePath => $file) {
            if ($excludes !== [] && $this->matcher->matches($relativePath, $excludes)) {
                $excluded[$relativePath] = $file;
            } else {
                $included[$relativePath] = $file;
            }
        }

        return ['included' => $included, 'excluded' => $excluded];
    }
}
