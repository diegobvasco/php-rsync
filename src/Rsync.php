<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\HasFilesystem;
use InvalidArgumentException;

class Rsync
{
    use HasFilesystem;

    private ?string $source = null;

    private ?string $destination = null;

    /** @var array<string> */
    private array $excludes = [];

    /** @var array<int, string> */
    private array $flags = [];

    /** @var array<string, string|array<string>> */
    private array $options = [];

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

    // ─── Core Flags ───────────────────────────────────────────────

    /**
     * Enable delete mode (--delete).
     * Removes files from destination that don't exist in source.
     */
    public function delete(): self
    {
        $this->flags[] = '--delete';

        return $this;
    }

    /**
     * Enable recursive mode (--recursive).
     * Process directories recursively.
     */
    public function recursive(): self
    {
        $this->flags[] = '--recursive';

        return $this;
    }

    /**
     * Enable archive mode (-a / --archive).
     * Equivalent to -rlptgoD (recursive, links, perms, times, group, owner, devices).
     */
    public function archive(): self
    {
        $this->flags[] = '--archive';

        return $this;
    }

    // ─── Metadata Preservation ────────────────────────────────────

    /**
     * Preserve modification times (--times / -t).
     */
    public function times(): self
    {
        $this->flags[] = '--times';

        return $this;
    }

    /**
     * Preserve permissions (--perms / -p).
     */
    public function perms(): self
    {
        $this->flags[] = '--perms';

        return $this;
    }

    /**
     * Preserve owner (--owner / -o).
     */
    public function owner(): self
    {
        $this->flags[] = '--owner';

        return $this;
    }

    /**
     * Preserve group (--group / -g).
     */
    public function group(): self
    {
        $this->flags[] = '--group';

        return $this;
    }

    /**
     * Preserve ACLs (--acls / -A).
     */
    public function acls(): self
    {
        $this->flags[] = '--acls';

        return $this;
    }

    /**
     * Preserve extended attributes (--xattrs / -X).
     */
    public function xattrs(): self
    {
        $this->flags[] = '--xattrs';

        return $this;
    }

    /**
     * Preserve device files (--devices / -D).
     */
    public function devices(): self
    {
        $this->flags[] = '--devices';

        return $this;
    }

    /**
     * Preserve special files (--specials / -S).
     */
    public function specials(): self
    {
        $this->flags[] = '--specials';

        return $this;
    }

    /**
     * Don't map uid/gid values (--numeric-ids).
     */
    public function numericIds(): self
    {
        $this->flags[] = '--numeric-ids';

        return $this;
    }

    // ─── Comparison ───────────────────────────────────────────────

    /**
     * Checksum based comparison (--checksum / -c).
     * Skip based on checksum, not mod-time & size.
     */
    public function checksum(): self
    {
        $this->flags[] = '--checksum';

        return $this;
    }

    /**
     * Don't skip files that match size and time (--ignore-times / -I).
     */
    public function ignoreTimes(): self
    {
        $this->flags[] = '--ignore-times';

        return $this;
    }

    /**
     * Skip files that match size only (--size-only).
     */
    public function sizeOnly(): self
    {
        $this->flags[] = '--size-only';

        return $this;
    }

    /**
     * Skip files that are newer on the receiver (--update / -u).
     */
    public function update(): self
    {
        $this->flags[] = '--update';

        return $this;
    }

    // ─── Excludes / Includes ──────────────────────────────────────

    /**
     * Add file or directory patterns to exclude (--exclude).
     *
     * @param  string|array<string>  $patterns
     */
    public function exclude(string|array $patterns): self
    {
        $patterns = (array) $patterns;

        /** @var array<string> $existing */
        $existing = $this->options['exclude'] ?? [];
        $this->options['exclude'] = array_merge($existing, $patterns);

        return $this;
    }

    /**
     * Read exclude patterns from file (--exclude-from).
     */
    public function excludeFrom(string $file): self
    {
        $this->options['exclude-from'] = $file;

        return $this;
    }

    /**
     * Add directory exclusion patterns (--exclude-dir).
     *
     * @param  string|array<string>  $patterns
     */
    public function excludeDir(string|array $patterns): self
    {
        $patterns = (array) $patterns;

        /** @var array<string> $existing */
        $existing = $this->options['exclude-dir'] ?? [];
        $this->options['exclude-dir'] = array_merge($existing, $patterns);

        return $this;
    }

    /**
     * Add file or directory patterns to include (--include).
     *
     * @param  string|array<string>  $patterns
     */
    public function include(string|array $patterns): self
    {
        $patterns = (array) $patterns;

        /** @var array<string> $existing */
        $existing = $this->options['include'] ?? [];
        $this->options['include'] = array_merge($existing, $patterns);

        return $this;
    }

    /**
     * Read include patterns from file (--include-from).
     */
    public function includeFrom(string $file): self
    {
        $this->options['include-from'] = $file;

        return $this;
    }

    /**
     * Remove empty directories from file list (--prune-empty-dirs).
     */
    public function pruneEmptyDirs(): self
    {
        $this->flags[] = '--prune-empty-dirs';

        return $this;
    }

    // ─── Backup ───────────────────────────────────────────────────

    /**
     * Make backups of changed files (--backup).
     */
    public function backup(): self
    {
        $this->flags[] = '--backup';

        return $this;
    }

    /**
     * Set backup directory (--backup-dir).
     */
    public function backupDir(string $dir): self
    {
        $this->options['backup-dir'] = $dir;

        return $this;
    }

    /**
     * Set backup suffix (--suffix).
     */
    public function suffix(string $suffix): self
    {
        $this->options['suffix'] = $suffix;

        return $this;
    }

    // ─── Symlinks / Hardlinks ─────────────────────────────────────

    /**
     * Copy symlinks as symlinks (--links / -l).
     */
    public function links(): self
    {
        $this->flags[] = '--links';

        return $this;
    }

    /**
     * Transform symlinks to referent files (--copy-links / -L).
     */
    public function copyLinks(): self
    {
        $this->flags[] = '--copy-links';

        return $this;
    }

    /**
     * Transform unsafe symlinks to referent files only in directories (--copy-unsafe-links).
     */
    public function copyUnsafeLinks(): self
    {
        $this->flags[] = '--copy-unsafe-links';

        return $this;
    }

    /**
     * Ignore symlinks that go outside tree (--safe-links).
     */
    public function safeLinks(): self
    {
        $this->flags[] = '--safe-links';

        return $this;
    }

    /**
     * Preserve hard links (--hard-links / -H).
     */
    public function hardLinks(): self
    {
        $this->flags[] = '--hard-links';

        return $this;
    }

    // ─── Size Limits ──────────────────────────────────────────────

    /**
     * Maximum file size to transfer (--max-size).
     *
     * @param  int|string  $size  Size in bytes or with suffix (e.g., '10M')
     */
    public function maxSize(int|string $size): self
    {
        $this->options['max-size'] = is_int($size) ? (string) $size : $size;

        return $this;
    }

    /**
     * Minimum file size to transfer (--min-size).
     *
     * @param  int|string  $size  Size in bytes or with suffix (e.g., '1K')
     */
    public function minSize(int|string $size): self
    {
        $this->options['min-size'] = is_int($size) ? (string) $size : $size;

        return $this;
    }

    // ─── Behavior ─────────────────────────────────────────────────

    /**
     * Show what would be done without doing it (--dry-run / -n).
     */
    public function dryRun(): self
    {
        $this->flags[] = '--dry-run';

        return $this;
    }

    /**
     * Force deletion of non-empty directories (--force / -f).
     */
    public function force(): self
    {
        $this->flags[] = '--force';

        return $this;
    }

    /**
     * Remove source files after successful transfer (--remove-source-files).
     */
    public function removeSourceFiles(): self
    {
        $this->flags[] = '--remove-source-files';

        return $this;
    }

    // ─── Output ───────────────────────────────────────────────────

    /**
     * Verbose output (--verbose / -v).
     */
    public function verbose(): self
    {
        $this->flags[] = '--verbose';

        return $this;
    }

    /**
     * Suppress output (--quiet / -q).
     */
    public function quiet(): self
    {
        $this->flags[] = '--quiet';

        return $this;
    }

    /**
     * Show progress (--progress).
     */
    public function progress(): self
    {
        $this->flags[] = '--progress';

        return $this;
    }

    /**
     * Show statistics (--stats).
     */
    public function stats(): self
    {
        $this->flags[] = '--stats';

        return $this;
    }

    /**
     * Show itemized changes (--itemize-changes / -i).
     */
    public function itemizeChanges(): self
    {
        $this->flags[] = '--itemize-changes';

        return $this;
    }

    /**
     * Human-readable numbers (--human-readable / -h).
     */
    public function humanReadable(): self
    {
        $this->flags[] = '--human-readable';

        return $this;
    }

    // ─── Delete Modes ─────────────────────────────────────────────

    /**
     * Delete before transfer (--delete-before).
     */
    public function deleteBefore(): self
    {
        $this->flags[] = '--delete-before';

        return $this;
    }

    /**
     * Delete after transfer (--delete-after).
     */
    public function deleteAfter(): self
    {
        $this->flags[] = '--delete-after';

        return $this;
    }

    /**
     * Delete excluded files from destination (--delete-excluded).
     */
    public function deleteExcluded(): self
    {
        $this->flags[] = '--delete-excluded';

        return $this;
    }

    // ─── Command Generation ───────────────────────────────────────

    /**
     * Generate equivalent rsync shell command for debugging.
     */
    public function toCommand(): string
    {
        $parts = ['rsync'];

        // Flags
        foreach (array_unique($this->flags) as $flag) {
            $parts[] = $flag;
        }

        // Single-value options
        $singleValueOptions = ['exclude-from', 'include-from', 'backup-dir', 'suffix', 'max-size', 'min-size'];
        foreach ($singleValueOptions as $option) {
            if (isset($this->options[$option]) && is_string($this->options[$option])) {
                $parts[] = '--'.$option.'='.sprintf("'%s'", $this->options[$option]);
            }
        }

        // Array options
        $arrayOptions = ['exclude', 'include', 'exclude-dir'];
        foreach ($arrayOptions as $option) {
            if (isset($this->options[$option]) && is_array($this->options[$option])) {
                foreach ($this->options[$option] as $value) {
                    $parts[] = '--'.$option.'='.sprintf("'%s'", $value);
                }
            }
        }

        // Source and destination
        if ($this->source !== null) {
            $parts[] = sprintf("'%s'", $this->source);
        }

        if ($this->destination !== null) {
            $parts[] = sprintf("'%s'", $this->destination);
        }

        return implode(' ', $parts);
    }

    /**
     * Reset all flags and options.
     */
    public function reset(): self
    {
        $this->flags = [];
        $this->options = [];
        $this->excludes = [];
        $this->source = null;
        $this->destination = null;

        return $this;
    }

    // ─── Core Execution ───────────────────────────────────────────

    /**
     * Execute the sync operation and return a report.
     */
    public function run(): Result
    {
        $this->validateConfiguration();

        $source = $this->source ?? '';
        $destination = $this->destination ?? '';

        // Check for dry-run mode
        if (in_array('--dry-run', $this->flags, true)) {
            return $this->dryRunSync($source, $destination);
        }

        // Scan all source files and separate by exclusion patterns
        $allSourceFiles = $this->scanAllFiles($source);
        ['included' => $sourceFiles, 'excluded' => $excludedFiles] = $this->filterByExclusions($allSourceFiles, $this->getEffectiveExcludes());

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
        if ($this->shouldDelete()) {
            foreach ($destinationFiles as $relativePath => $destinationFile) {
                if (isset($sourceFiles[$relativePath])) {
                    continue;
                }

                if ($this->matchesExclusion($relativePath, $this->getEffectiveExcludes())) {
                    continue;
                }

                if ($this->deleteFile($destinationFile->absolutePath)) {
                    $deleted[] = $destinationFile;
                    $this->output?->deleted($destinationFile);
                }
            }
        }

        // Cleanup empty directories in destination
        if ($this->shouldCleanupEmptyDirs()) {
            $this->cleanupEmptyDirectories();
        }

        return new Result(
            copied: $copied,
            deleted: $deleted,
            skipped: [...$skipped, ...array_values($excludedFiles)],
        );
    }

    /**
     * Simulate sync for dry-run mode without making changes.
     */
    private function dryRunSync(string $source, string $destination): Result
    {
        $allSourceFiles = $this->scanAllFiles($source);
        ['included' => $sourceFiles, 'excluded' => $excludedFiles] = $this->filterByExclusions($allSourceFiles, $this->getEffectiveExcludes());

        $destinationFiles = $this->scanAllFiles($destination);

        $copied = [];
        $skipped = [];
        $deleted = [];

        // Determine what would be copied
        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath] ?? null;

            if ($destinationFile !== null && ! $this->shouldSync($sourceFile, $destinationFile)) {
                $skipped[] = $sourceFile;

                continue;
            }

            $copied[] = $sourceFile;
        }

        // Determine what would be deleted
        if ($this->shouldDelete()) {
            foreach ($destinationFiles as $relativePath => $destinationFile) {
                if (isset($sourceFiles[$relativePath])) {
                    continue;
                }

                if ($this->matchesExclusion($relativePath, $this->getEffectiveExcludes())) {
                    continue;
                }

                $deleted[] = $destinationFile;
            }
        }

        return new Result(
            copied: $copied,
            deleted: $deleted,
            skipped: [...$skipped, ...array_values($excludedFiles)],
        );
    }

    /**
     * Check if deletion should be performed.
     */
    private function shouldDelete(): bool
    {
        return in_array('--delete', $this->flags, true)
            || in_array('--delete-before', $this->flags, true)
            || in_array('--delete-after', $this->flags, true)
            || in_array('--delete-excluded', $this->flags, true);
    }

    /**
     * Check if empty directory cleanup should be performed.
     */
    private function shouldCleanupEmptyDirs(): bool
    {
        return ! in_array('--no-empty-dirs', $this->flags, true);
    }

    /**
     * Get effective excludes combining skip() patterns and exclude() patterns.
     *
     * @return array<string>
     */
    private function getEffectiveExcludes(): array
    {
        $excludes = $this->excludes;

        if (isset($this->options['exclude']) && is_array($this->options['exclude'])) {
            $excludes = array_merge($excludes, $this->options['exclude']);
        }

        if (isset($this->options['exclude-dir']) && is_array($this->options['exclude-dir'])) {
            foreach ($this->options['exclude-dir'] as $dir) {
                $excludes[] = $dir.'/';
            }
        }

        return $excludes;
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

        if (! $this->isReadable($this->source)) {
            throw new InvalidArgumentException('Source directory is not readable: '.$this->source);
        }
    }

    /**
     * Check if a path is readable. Protected to allow testing on platforms where chmod doesn't work.
     */
    protected function isReadable(string $path): bool
    {
        return is_readable($path);
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
