<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\DirectoryCleanup;
use DiegoVasconcelos\Rsync\Concerns\FileOperations;
use DiegoVasconcelos\Rsync\Concerns\FileScanner;
use DiegoVasconcelos\Rsync\Concerns\GlobMatcher;
use InvalidArgumentException;

class Rsync
{
    use DirectoryCleanup;
    use FileOperations;
    use FileScanner;
    use GlobMatcher;

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
        $this->flags = $this->flags->addFlag(FlagType::DELETE);

        return $this;
    }

    /**
     * Enable recursive mode (--recursive).
     * Process directories recursively.
     */
    public function recursive(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::RECURSIVE);

        return $this;
    }

    /**
     * Enable archive mode (-a / --archive).
     * Equivalent to -rlptgoD (recursive, links, perms, times, group, owner, devices).
     */
    public function archive(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::ARCHIVE);

        return $this;
    }

    // ─── Metadata Preservation ────────────────────────────────────

    /**
     * Preserve modification times (--times / -t).
     */
    public function times(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::TIMES);

        return $this;
    }

    /**
     * Preserve permissions (--perms / -p).
     */
    public function perms(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::PERMS);

        return $this;
    }

    /**
     * Preserve owner (--owner / -o).
     */
    public function owner(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::OWNER);

        return $this;
    }

    /**
     * Preserve group (--group / -g).
     */
    public function group(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::GROUP);

        return $this;
    }

    /**
     * Preserve ACLs (--acls / -A).
     */
    public function acls(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::ACLS);

        return $this;
    }

    /**
     * Preserve extended attributes (--xattrs / -X).
     */
    public function xattrs(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::XTRAS);

        return $this;
    }

    /**
     * Preserve device files (--devices / -D).
     */
    public function devices(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::DEVICES);

        return $this;
    }

    /**
     * Preserve special files (--specials / -S).
     */
    public function specials(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::SPECIALS);

        return $this;
    }

    /**
     * Don't map uid/gid values (--numeric-ids).
     */
    public function numericIds(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::NUMERIC_IDS);

        return $this;
    }

    // ─── Comparison ───────────────────────────────────────────────

    /**
     * Checksum based comparison (--checksum / -c).
     * Skip based on checksum, not mod-time & size.
     */
    public function checksum(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::CHECKSUM);

        return $this;
    }

    /**
     * Don't skip files that match size and time (--ignore-times / -I).
     */
    public function ignoreTimes(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::IGNORE_TIMES);

        return $this;
    }

    /**
     * Skip files that match size only (--size-only).
     */
    public function sizeOnly(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::SIZE_ONLY);

        return $this;
    }

    /**
     * Skip files that are newer on the receiver (--update / -u).
     */
    public function update(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::UPDATE);

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
        $this->flags = $this->flags->addFlag(FlagType::PRUNE_EMPTY_DIRS);

        return $this;
    }

    // ─── Backup ───────────────────────────────────────────────────

    /**
     * Make backups of changed files (--backup).
     */
    public function backup(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::BACKUP);

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
        $this->flags = $this->flags->addFlag(FlagType::LINKS);

        return $this;
    }

    /**
     * Transform symlinks to referent files (--copy-links / -L).
     */
    public function copyLinks(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::COPY_LINKS);

        return $this;
    }

    /**
     * Transform unsafe symlinks to referent files only in directories (--copy-unsafe-links).
     */
    public function copyUnsafeLinks(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::COPY_UNSAFE_LINKS);

        return $this;
    }

    /**
     * Ignore symlinks that go outside tree (--safe-links).
     */
    public function safeLinks(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::SAFE_LINKS);

        return $this;
    }

    /**
     * Preserve hard links (--hard-links / -H).
     */
    public function hardLinks(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::HARD_LINKS);

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
        $this->flags = $this->flags->addFlag(FlagType::DRY_RUN);

        return $this;
    }

    /**
     * Force deletion of non-empty directories (--force / -f).
     */
    public function force(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::FORCE);

        return $this;
    }

    /**
     * Remove source files after successful transfer (--remove-source-files).
     */
    public function removeSourceFiles(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::REMOVE_SOURCE_FILES);

        return $this;
    }

    // ─── Output ───────────────────────────────────────────────────

    /**
     * Verbose output (--verbose / -v).
     */
    public function verbose(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::VERBOSE);

        return $this;
    }

    /**
     * Suppress output (--quiet / -q).
     */
    public function quiet(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::QUIET);

        return $this;
    }

    /**
     * Show progress (--progress).
     */
    public function progress(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::PROGRESS);

        return $this;
    }

    /**
     * Show statistics (--stats).
     */
    public function stats(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::STATS);

        return $this;
    }

    /**
     * Show itemized changes (--itemize-changes / -i).
     */
    public function itemizeChanges(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::ITEMIZE_CHANGES);

        return $this;
    }

    /**
     * Human-readable numbers (--human-readable / -h).
     */
    public function humanReadable(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::HUMAN_READABLE);

        return $this;
    }

    // ─── Delete Modes ─────────────────────────────────────────────

    /**
     * Delete before transfer (--delete-before).
     */
    public function deleteBefore(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::DELETE_BEFORE);

        return $this;
    }

    /**
     * Delete after transfer (--delete-after).
     */
    public function deleteAfter(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::DELETE_AFTER);

        return $this;
    }

    /**
     * Delete excluded files from destination (--delete-excluded).
     */
    public function deleteExcluded(): self
    {
        $this->flags = $this->flags->addFlag(FlagType::DELETE_EXCLUDED);

        return $this;
    }

    // ─── Command Generation ───────────────────────────────────────

    /**
     * Generate equivalent rsync shell command for debugging.
     */
    public function toCommand(): string
    {
        $parts = ['rsync'];

        foreach ($this->flags as $flag) {
            $parts[] = $flag->name;
        }

        foreach ($this->options as $option) {
            $parts[] = $option->toCommandString();
        }

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

        $operation = $this->flags->contains(FlagType::DRY_RUN)
            ? new DryRunSyncOperation()
            : new RealSyncOperation($this->output);

        ['sourceFiles' => $sourceFiles, 'excludedFiles' => $excludedFiles, 'destinationFiles' => $destinationFiles] =
            $this->scanFiles($source, $destination);

        $useChecksum = $this->flags->contains(FlagType::CHECKSUM);

        $copied = [];
        $skipped = [];
        $deleted = [];

        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath] ?? null;

            if ($destinationFile !== null && ! $this->shouldSync($sourceFile, $destinationFile, $useChecksum)) {
                $skipped[] = $sourceFile;
                $operation->notifySkipped($sourceFile);

                continue;
            }

            $destPath = $destination.DIRECTORY_SEPARATOR.$relativePath;

            if ($operation->copyFile($sourceFile->absolutePath, $destPath)) {
                $copied[] = $sourceFile;
                $operation->notifyCopied($sourceFile);
            }
        }

        if ($this->shouldDelete()) {
            $deleted = $this->deleteFiles($sourceFiles, $destinationFiles, $operation);
        }

        $this->cleanupEmptyDirectories();

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
    private function deleteFiles(array $sourceFiles, array $destinationFiles, SyncOperationInterface $operation): array
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

            if ($operation->deleteFile($destinationFile->absolutePath)) {
                $deleted[] = $destinationFile;
                $operation->notifyDeleted($destinationFile);
            }
        }

        return $deleted;
    }

    /**
     * Check if deletion should be performed.
     */
    private function shouldDelete(): bool
    {
        if ($this->flags->contains(FlagType::DELETE)) {
            return true;
        }

        if ($this->flags->contains(FlagType::DELETE_BEFORE)) {
            return true;
        }

        if ($this->flags->contains(FlagType::DELETE_AFTER)) {
            return true;
        }

        return $this->flags->contains(FlagType::DELETE_EXCLUDED);
    }

    /**
     * Check if empty directory cleanup should be performed.
     */
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
}
