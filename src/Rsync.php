<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\HasFilesystem;
use InvalidArgumentException;

final class Rsync
{
    use HasFilesystem;

    private ?string $source = null;

    private ?string $destination = null;

    /** @var array<string> */
    private array $excludes = [];

    public function __construct(private ?Output $output = null) {}

    /**
     * Set the source and destination directories.
     */
    public function copy(string $source, string $destination): self
    {
        $this->source = rtrim($source, DIRECTORY_SEPARATOR);
        $this->destination = rtrim($destination, DIRECTORY_SEPARATOR);

        return $this;
    }

    /**
     * Add file or directory patterns to skip.
     *
     * @param  string|array<string>  $patterns
     */
    public function skip(string|array $patterns): self
    {
        $patterns = (array) $patterns;

        foreach ($patterns as $pattern) {
            $this->excludes[] = $pattern;
        }

        return $this;
    }

    /**
     * Execute the sync operation and return a report.
     */
    public function run(): Result
    {
        $this->validateConfiguration();

        $source = $this->source ?? '';
        $destination = $this->destination ?? '';

        // Scan all source files and separate by exclusion patterns
        $allSourceFiles = $this->scanAllFiles($source);
        ['included' => $sourceFiles, 'excluded' => $excludedFiles] = $this->filterByExclusions($allSourceFiles, $this->excludes);

        // Scan destination without exclusions
        $destinationFiles = $this->scanAllFiles($destination);

        $copied = [];
        $skipped = [];
        $deleted = [];

        // Process source files - copy new or updated files
        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath] ?? null;

            if ($destinationFile !== null && ! $this->shouldSync($sourceFile, $destinationFile)) {
                $skipped[] = $sourceFile;
                $this->output?->skipped($sourceFile);

                continue;
            }

            $destPath = $destination.DIRECTORY_SEPARATOR.$relativePath;

            if ($this->copyFile($sourceFile->absolutePath, $destPath)) {
                $copied[] = $sourceFile;
                $this->output?->copied($sourceFile);
            }
        }

        // Process destination files - delete files not in source and not excluded
        foreach ($destinationFiles as $relativePath => $destinationFile) {
            if (isset($sourceFiles[$relativePath])) {
                continue;
            }

            if ($this->matchesExclusion($relativePath, $this->excludes)) {
                continue;
            }

            if ($this->deleteFile($destinationFile->absolutePath)) {
                $deleted[] = $destinationFile;
                $this->output?->deleted($destinationFile);
            }
        }

        // Cleanup empty directories in destination
        $this->cleanupEmptyDirectories();

        return new Result(
            copied: $copied,
            deleted: $deleted,
            skipped: [...$skipped, ...array_values($excludedFiles)],
        );
    }

    /**
     * Validate that required configuration is set.
     */
    private function validateConfiguration(): void
    {
        if ($this->source === null || $this->destination === null) {
            throw new InvalidArgumentException('Source and destination must be set using copy() method.');
        }

        if (! is_dir($this->source)) {
            throw new InvalidArgumentException('Source directory does not exist: '.$this->source);
        }

        if (! is_readable($this->source)) {
            throw new InvalidArgumentException('Source directory is not readable: '.$this->source);
        }
    }

    /**
     * Remove empty directories from destination after sync.
     */
    private function cleanupEmptyDirectories(): void
    {
        $destination = $this->destination ?? '';

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
    private function isEmptyDirectory(string $path): bool
    {
        return is_dir($path) && count(scandir($path)) === 2; // Only . and ..
    }
}
