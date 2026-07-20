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

    private FlagCollection $excludes;

    private FlagCollection $flags;

    private OptionCollection $options;

    public function __construct(private ?Output $output = null)
    {
        $this->excludes = new FlagCollection();
        $this->flags = new FlagCollection();
        $this->options = new OptionCollection();
    }

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
            $this->excludes = $this->excludes->add(new Flag($pattern));
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
        $this->flags = $this->flags->addFlag('--delete');

        return $this;
    }

    /**
     * Enable recursive mode (--recursive).
     * Process directories recursively.
     */
    public function recursive(): self
    {
        $this->flags = $this->flags->addFlag('--recursive');

        return $this;
    }

    /**
     * Enable archive mode (-a / --archive).
     * Equivalent to -rlptgoD (recursive, links, perms, times, group, owner, devices).
     */
    public function archive(): self
    {
        $this->flags = $this->flags->addFlag('--archive');

        return $this;
    }

    // ─── Metadata Preservation ────────────────────────────────────

    /**
     * Preserve modification times (--times / -t).
     */
    public function times(): self
    {
        $this->flags = $this->flags->addFlag('--times');

        return $this;
    }

    /**
     * Preserve permissions (--perms / -p).
     */
    public function perms(): self
    {
        $this->flags = $this->flags->addFlag('--perms');

        return $this;
    }

    /**
     * Preserve owner (--owner / -o).
     */
    public function owner(): self
    {
        $this->flags = $this->flags->addFlag('--owner');

        return $this;
    }

    /**
     * Preserve group (--group / -g).
     */
    public function group(): self
    {
        $this->flags = $this->flags->addFlag('--group');

        return $this;
    }

    /**
     * Preserve ACLs (--acls / -A).
     */
    public function acls(): self
    {
        $this->flags = $this->flags->addFlag('--acls');

        return $this;
    }

    /**
     * Preserve extended attributes (--xattrs / -X).
     */
    public function xattrs(): self
    {
        $this->flags = $this->flags->addFlag('--xattrs');

        return $this;
    }

    /**
     * Preserve device files (--devices / -D).
     */
    public function devices(): self
    {
        $this->flags = $this->flags->addFlag('--devices');

        return $this;
    }

    /**
     * Preserve special files (--specials / -S).
     */
    public function specials(): self
    {
        $this->flags = $this->flags->addFlag('--specials');

        return $this;
    }

    /**
     * Don't map uid/gid values (--numeric-ids).
     */
    public function numericIds(): self
    {
        $this->flags = $this->flags->addFlag('--numeric-ids');

        return $this;
    }

    // ─── Comparison ───────────────────────────────────────────────

    /**
     * Checksum based comparison (--checksum / -c).
     * Skip based on checksum, not mod-time & size.
     */
    public function checksum(): self
    {
        $this->flags = $this->flags->addFlag('--checksum');

        return $this;
    }

    /**
     * Don't skip files that match size and time (--ignore-times / -I).
     */
    public function ignoreTimes(): self
    {
        $this->flags = $this->flags->addFlag('--ignore-times');

        return $this;
    }

    /**
     * Skip files that match size only (--size-only).
     */
    public function sizeOnly(): self
    {
        $this->flags = $this->flags->addFlag('--size-only');

        return $this;
    }

    /**
     * Skip files that are newer on the receiver (--update / -u).
     */
    public function update(): self
    {
        $this->flags = $this->flags->addFlag('--update');

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

        $option = $this->options->has('exclude')
            ? $this->options->get('exclude')
            : new Option('exclude');

        foreach ($patterns as $pattern) {
            $option = $option->addValue($pattern);
        }

        $this->options = $this->options->remove('exclude')->add($option);

        return $this;
    }

    /**
     * Read exclude patterns from file (--exclude-from).
     */
    public function excludeFrom(string $file): self
    {
        $this->options = $this->options->remove('exclude-from')->add(new Option('exclude-from', [$file]));

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

        $option = $this->options->has('exclude-dir')
            ? $this->options->get('exclude-dir')
            : new Option('exclude-dir');

        foreach ($patterns as $pattern) {
            $option = $option->addValue($pattern);
        }

        $this->options = $this->options->remove('exclude-dir')->add($option);

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

        $option = $this->options->has('include')
            ? $this->options->get('include')
            : new Option('include');

        foreach ($patterns as $pattern) {
            $option = $option->addValue($pattern);
        }

        $this->options = $this->options->remove('include')->add($option);

        return $this;
    }

    /**
     * Read include patterns from file (--include-from).
     */
    public function includeFrom(string $file): self
    {
        $this->options = $this->options->remove('include-from')->add(new Option('include-from', [$file]));

        return $this;
    }

    /**
     * Remove empty directories from file list (--prune-empty-dirs).
     */
    public function pruneEmptyDirs(): self
    {
        $this->flags = $this->flags->addFlag('--prune-empty-dirs');

        return $this;
    }

    // ─── Backup ───────────────────────────────────────────────────

    /**
     * Make backups of changed files (--backup).
     */
    public function backup(): self
    {
        $this->flags = $this->flags->addFlag('--backup');

        return $this;
    }

    /**
     * Set backup directory (--backup-dir).
     */
    public function backupDir(string $dir): self
    {
        $this->options = $this->options->remove('backup-dir')->add(new Option('backup-dir', [$dir]));

        return $this;
    }

    /**
     * Set backup suffix (--suffix).
     */
    public function suffix(string $suffix): self
    {
        $this->options = $this->options->remove('suffix')->add(new Option('suffix', [$suffix]));

        return $this;
    }

    // ─── Symlinks / Hardlinks ─────────────────────────────────────

    /**
     * Copy symlinks as symlinks (--links / -l).
     */
    public function links(): self
    {
        $this->flags = $this->flags->addFlag('--links');

        return $this;
    }

    /**
     * Transform symlinks to referent files (--copy-links / -L).
     */
    public function copyLinks(): self
    {
        $this->flags = $this->flags->addFlag('--copy-links');

        return $this;
    }

    /**
     * Transform unsafe symlinks to referent files only in directories (--copy-unsafe-links).
     */
    public function copyUnsafeLinks(): self
    {
        $this->flags = $this->flags->addFlag('--copy-unsafe-links');

        return $this;
    }

    /**
     * Ignore symlinks that go outside tree (--safe-links).
     */
    public function safeLinks(): self
    {
        $this->flags = $this->flags->addFlag('--safe-links');

        return $this;
    }

    /**
     * Preserve hard links (--hard-links / -H).
     */
    public function hardLinks(): self
    {
        $this->flags = $this->flags->addFlag('--hard-links');

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
        $value = is_int($size) ? (string) $size : $size;
        $this->options = $this->options->remove('max-size')->add(new Option('max-size', [$value]));

        return $this;
    }

    /**
     * Minimum file size to transfer (--min-size).
     *
     * @param  int|string  $size  Size in bytes or with suffix (e.g., '1K')
     */
    public function minSize(int|string $size): self
    {
        $value = is_int($size) ? (string) $size : $size;
        $this->options = $this->options->remove('min-size')->add(new Option('min-size', [$value]));

        return $this;
    }

    // ─── Behavior ─────────────────────────────────────────────────

    /**
     * Show what would be done without doing it (--dry-run / -n).
     */
    public function dryRun(): self
    {
        $this->flags = $this->flags->addFlag('--dry-run');

        return $this;
    }

    /**
     * Force deletion of non-empty directories (--force / -f).
     */
    public function force(): self
    {
        $this->flags = $this->flags->addFlag('--force');

        return $this;
    }

    /**
     * Remove source files after successful transfer (--remove-source-files).
     */
    public function removeSourceFiles(): self
    {
        $this->flags = $this->flags->addFlag('--remove-source-files');

        return $this;
    }

    // ─── Output ───────────────────────────────────────────────────

    /**
     * Verbose output (--verbose / -v).
     */
    public function verbose(): self
    {
        $this->flags = $this->flags->addFlag('--verbose');

        return $this;
    }

    /**
     * Suppress output (--quiet / -q).
     */
    public function quiet(): self
    {
        $this->flags = $this->flags->addFlag('--quiet');

        return $this;
    }

    /**
     * Show progress (--progress).
     */
    public function progress(): self
    {
        $this->flags = $this->flags->addFlag('--progress');

        return $this;
    }

    /**
     * Show statistics (--stats).
     */
    public function stats(): self
    {
        $this->flags = $this->flags->addFlag('--stats');

        return $this;
    }

    /**
     * Show itemized changes (--itemize-changes / -i).
     */
    public function itemizeChanges(): self
    {
        $this->flags = $this->flags->addFlag('--itemize-changes');

        return $this;
    }

    /**
     * Human-readable numbers (--human-readable / -h).
     */
    public function humanReadable(): self
    {
        $this->flags = $this->flags->addFlag('--human-readable');

        return $this;
    }

    // ─── Delete Modes ─────────────────────────────────────────────

    /**
     * Delete before transfer (--delete-before).
     */
    public function deleteBefore(): self
    {
        $this->flags = $this->flags->addFlag('--delete-before');

        return $this;
    }

    /**
     * Delete after transfer (--delete-after).
     */
    public function deleteAfter(): self
    {
        $this->flags = $this->flags->addFlag('--delete-after');

        return $this;
    }

    /**
     * Delete excluded files from destination (--delete-excluded).
     */
    public function deleteExcluded(): self
    {
        $this->flags = $this->flags->addFlag('--delete-excluded');

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
        foreach ($this->flags as $flag) {
            $parts[] = $flag->name;
        }

        // Single-value options
        $singleValueOptions = ['exclude-from', 'include-from', 'backup-dir', 'suffix', 'max-size', 'min-size'];
        foreach ($singleValueOptions as $key) {
            if ($this->options->has($key)) {
                $option = $this->options->get($key);
                if ($option->values !== []) {
                    $parts[] = '--'.$key.'='.sprintf("'%s'", $option->values[0]);
                }
            }
        }

        // Array options
        $arrayOptions = ['exclude', 'include', 'exclude-dir'];
        foreach ($arrayOptions as $key) {
            if ($this->options->has($key)) {
                $option = $this->options->get($key);
                foreach ($option->values as $value) {
                    $parts[] = '--'.$key.'='.sprintf("'%s'", $value);
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
        $this->flags = new FlagCollection();
        $this->options = new OptionCollection();
        $this->excludes = new FlagCollection();
        $this->source = null;
        $this->destination = null;

        return $this;
    }

    /**
     * Get the flags collection.
     */
    public function getFlags(): FlagCollection
    {
        return $this->flags;
    }

    /**
     * Get the options collection.
     */
    public function getOptions(): OptionCollection
    {
        return $this->options;
    }

    /**
     * Get the excludes collection.
     */
    public function getExcludes(): FlagCollection
    {
        return $this->excludes;
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

        if ($this->flags->contains('--dry-run')) {
            return $this->dryRunSync($source, $destination);
        }

        ['sourceFiles' => $sourceFiles, 'excludedFiles' => $excludedFiles, 'destinationFiles' => $destinationFiles] =
            $this->scanFiles($source, $destination);

        $useChecksum = $this->flags->contains('--checksum');

        $copied = [];
        $skipped = [];
        $deleted = [];

        // Copy new or updated files
        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath] ?? null;

            if ($destinationFile !== null && ! $this->shouldSync($sourceFile, $destinationFile, $useChecksum)) {
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

        // Delete files not in source
        if ($this->shouldDelete()) {
            $deleted = $this->deleteFiles($sourceFiles, $destinationFiles);
        }

        // Cleanup empty directories
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
        ['sourceFiles' => $sourceFiles, 'excludedFiles' => $excludedFiles, 'destinationFiles' => $destinationFiles] =
            $this->scanFiles($source, $destination);

        $useChecksum = $this->flags->contains('--checksum');

        $copied = [];
        $skipped = [];
        $deleted = [];

        // Determine what would be copied
        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath] ?? null;

            if ($destinationFile !== null && ! $this->shouldSync($sourceFile, $destinationFile, $useChecksum)) {
                $skipped[] = $sourceFile;

                continue;
            }

            $copied[] = $sourceFile;
        }

        // Determine what would be deleted
        if ($this->shouldDelete()) {
            $deleted = $this->determineFilesToDelete($sourceFiles, $destinationFiles);
        }

        return new Result(
            copied: $copied,
            deleted: $deleted,
            skipped: [...$skipped, ...array_values($excludedFiles)],
        );
    }

    /**
     * Scan source and destination files, applying exclusions to source.
     *
     * @return array{sourceFiles: array<string, FileInfo>, excludedFiles: array<string, FileInfo>, destinationFiles: array<string, FileInfo>}
     */
    private function scanFiles(string $source, string $destination): array
    {
        $allSourceFiles = $this->scanAllFiles($source);
        ['included' => $sourceFiles, 'excluded' => $excludedFiles] = $this->filterByExclusions($allSourceFiles, $this->getEffectiveExcludes());
        $destinationFiles = $this->scanAllFiles($destination);

        return [
            'sourceFiles' => $sourceFiles,
            'excludedFiles' => $excludedFiles,
            'destinationFiles' => $destinationFiles,
        ];
    }

    /**
     * Delete destination files that are not in source and not excluded.
     *
     * @param  array<string, FileInfo>  $sourceFiles
     * @param  array<string, FileInfo>  $destinationFiles
     * @return list<FileInfo>
     */
    private function deleteFiles(array $sourceFiles, array $destinationFiles): array
    {
        $deleted = [];
        $excludes = $this->getEffectiveExcludes();

        foreach ($destinationFiles as $relativePath => $destinationFile) {
            if (isset($sourceFiles[$relativePath])) {
                continue;
            }

            if ($this->matchesExclusion($relativePath, $excludes)) {
                continue;
            }

            if ($this->deleteFile($destinationFile->absolutePath)) {
                $deleted[] = $destinationFile;
                $this->output?->deleted($destinationFile);
            }
        }

        return $deleted;
    }

    /**
     * Determine which destination files would be deleted (dry-run).
     *
     * @param  array<string, FileInfo>  $sourceFiles
     * @param  array<string, FileInfo>  $destinationFiles
     * @return list<FileInfo>
     */
    private function determineFilesToDelete(array $sourceFiles, array $destinationFiles): array
    {
        $deleted = [];
        $excludes = $this->getEffectiveExcludes();

        foreach ($destinationFiles as $relativePath => $destinationFile) {
            if (isset($sourceFiles[$relativePath])) {
                continue;
            }

            if ($this->matchesExclusion($relativePath, $excludes)) {
                continue;
            }

            $deleted[] = $destinationFile;
        }

        return $deleted;
    }

    /**
     * Check if deletion should be performed.
     */
    private function shouldDelete(): bool
    {
        return $this->flags->contains('--delete')
            || $this->flags->contains('--delete-before')
            || $this->flags->contains('--delete-after')
            || $this->flags->contains('--delete-excluded');
    }

    /**
     * Check if empty directory cleanup should be performed.
     */
    private function shouldCleanupEmptyDirs(): bool
    {
        return ! $this->flags->contains('--no-empty-dirs');
    }

    /**
     * Get effective excludes combining skip() patterns and exclude() patterns.
     *
     * @return list<string>
     */
    private function getEffectiveExcludes(): array
    {
        $excludes = $this->excludes->toArray();

        if ($this->options->has('exclude')) {
            $excludes = array_values(array_merge($excludes, $this->options->get('exclude')->values));
        }

        if ($this->options->has('exclude-dir')) {
            foreach ($this->options->get('exclude-dir')->values as $dir) {
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
