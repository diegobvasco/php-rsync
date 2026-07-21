<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use InvalidArgumentException;

final class Rsync
{
    private ?string $source = null;

    private ?string $destination = null;

    /** @var list<string> */
    private array $excludes = [];

    private FlagCollection $flags;

    private OptionCollection $options;

    private readonly Filesystem $fs;

    private readonly GlobMatcher $matcher;

    private readonly FileScanner $scanner;

    private readonly DirectoryCleaner $cleaner;

    private readonly Comparator $comparator;

    public function __construct(private readonly ?Output $output = null, ?Filesystem $filesystem = null)
    {
        $this->flags = new FlagCollection();
        $this->options = new OptionCollection();
        $this->fs = $filesystem ?? new LocalFilesystem();
        $this->matcher = new GlobMatcher();
        $this->scanner = new FileScanner($this->fs, $this->matcher);
        $this->cleaner = new DirectoryCleaner($this->fs);
        $this->comparator = new Comparator();
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
        foreach ((array) $patterns as $pattern) {
            $this->excludes = [...$this->excludes, $pattern];
        }

        return $this;
    }

    /**
     * Enable a flag and return $this for chaining.
     */
    private function setFlag(FlagType $flag): self
    {
        $this->flags = $this->flags->addFlag($flag);

        return $this;
    }

    /**
     * Append values to a (possibly existing) option and return $this.
     *
     * @param  string|array<string>  $values
     */
    private function addOptionValues(string $key, string|array $values): self
    {
        $option = $this->options->has($key)
            ? $this->options->get($key)
            : new Option($key);

        foreach ((array) $values as $value) {
            $option = $option->addValue($value);
        }

        $this->options = $this->options->remove($key)->add($option);

        return $this;
    }

    // ─── Core Flags ───────────────────────────────────────────────

    /**
     * Enable delete mode (--delete).
     * Removes files from destination that don't exist in source.
     */
    public function delete(): self
    {
        return $this->setFlag(FlagType::DELETE);
    }

    /**
     * Enable recursive mode (--recursive).
     * Process directories recursively.
     */
    public function recursive(): self
    {
        return $this->setFlag(FlagType::RECURSIVE);
    }

    /**
     * Enable archive mode (-a / --archive).
     * Equivalent to -rlptgoD (recursive, links, perms, times, group, owner, devices).
     */
    public function archive(): self
    {
        return $this->setFlag(FlagType::ARCHIVE);
    }

    // ─── Metadata Preservation ────────────────────────────────────

    /**
     * Preserve modification times (--times / -t).
     */
    public function times(): self
    {
        return $this->setFlag(FlagType::TIMES);
    }

    /**
     * Preserve permissions (--perms / -p).
     */
    public function perms(): self
    {
        return $this->setFlag(FlagType::PERMS);
    }

    /**
     * Preserve owner (--owner / -o).
     */
    public function owner(): self
    {
        return $this->setFlag(FlagType::OWNER);
    }

    /**
     * Preserve group (--group / -g).
     */
    public function group(): self
    {
        return $this->setFlag(FlagType::GROUP);
    }

    /**
     * Preserve ACLs (--acls / -A).
     */
    public function acls(): self
    {
        return $this->setFlag(FlagType::ACLS);
    }

    /**
     * Preserve extended attributes (--xattrs / -X).
     */
    public function xattrs(): self
    {
        return $this->setFlag(FlagType::XATTRS);
    }

    /**
     * Preserve device files (--devices / -D).
     */
    public function devices(): self
    {
        return $this->setFlag(FlagType::DEVICES);
    }

    /**
     * Preserve special files (--specials / -S).
     */
    public function specials(): self
    {
        return $this->setFlag(FlagType::SPECIALS);
    }

    /**
     * Don't map uid/gid values (--numeric-ids).
     */
    public function numericIds(): self
    {
        return $this->setFlag(FlagType::NUMERIC_IDS);
    }

    // ─── Comparison ───────────────────────────────────────────────

    /**
     * Checksum based comparison (--checksum / -c).
     * Skip based on checksum, not mod-time & size.
     */
    public function checksum(): self
    {
        return $this->setFlag(FlagType::CHECKSUM);
    }

    /**
     * Don't skip files that match size and time (--ignore-times / -I).
     */
    public function ignoreTimes(): self
    {
        return $this->setFlag(FlagType::IGNORE_TIMES);
    }

    /**
     * Skip files that match size only (--size-only).
     */
    public function sizeOnly(): self
    {
        return $this->setFlag(FlagType::SIZE_ONLY);
    }

    /**
     * Skip files that are newer on the receiver (--update / -u).
     */
    public function update(): self
    {
        return $this->setFlag(FlagType::UPDATE);
    }

    // ─── Excludes / Includes ──────────────────────────────────────

    /**
     * Add file or directory patterns to exclude (--exclude).
     *
     * @param  string|array<string>  $patterns
     */
    public function exclude(string|array $patterns): self
    {
        return $this->addOptionValues('exclude', $patterns);
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
        return $this->addOptionValues('exclude-dir', $patterns);
    }

    /**
     * Add file or directory patterns to include (--include).
     *
     * @param  string|array<string>  $patterns
     */
    public function include(string|array $patterns): self
    {
        return $this->addOptionValues('include', $patterns);
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
        return $this->setFlag(FlagType::PRUNE_EMPTY_DIRS);
    }

    // ─── Backup ───────────────────────────────────────────────────

    /**
     * Make backups of changed files (--backup).
     */
    public function backup(): self
    {
        return $this->setFlag(FlagType::BACKUP);
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
        return $this->setFlag(FlagType::LINKS);
    }

    /**
     * Transform symlinks to referent files (--copy-links / -L).
     */
    public function copyLinks(): self
    {
        return $this->setFlag(FlagType::COPY_LINKS);
    }

    /**
     * Transform unsafe symlinks to referent files only in directories (--copy-unsafe-links).
     */
    public function copyUnsafeLinks(): self
    {
        return $this->setFlag(FlagType::COPY_UNSAFE_LINKS);
    }

    /**
     * Ignore symlinks that go outside tree (--safe-links).
     */
    public function safeLinks(): self
    {
        return $this->setFlag(FlagType::SAFE_LINKS);
    }

    /**
     * Preserve hard links (--hard-links / -H).
     */
    public function hardLinks(): self
    {
        return $this->setFlag(FlagType::HARD_LINKS);
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
        return $this->setFlag(FlagType::DRY_RUN);
    }

    /**
     * Force deletion of non-empty directories (--force / -f).
     */
    public function force(): self
    {
        return $this->setFlag(FlagType::FORCE);
    }

    /**
     * Remove source files after successful transfer (--remove-source-files).
     */
    public function removeSourceFiles(): self
    {
        return $this->setFlag(FlagType::REMOVE_SOURCE_FILES);
    }

    // ─── Output ───────────────────────────────────────────────────

    /**
     * Verbose output (--verbose / -v).
     */
    public function verbose(): self
    {
        return $this->setFlag(FlagType::VERBOSE);
    }

    /**
     * Suppress output (--quiet / -q).
     */
    public function quiet(): self
    {
        return $this->setFlag(FlagType::QUIET);
    }

    /**
     * Show progress (--progress).
     */
    public function progress(): self
    {
        return $this->setFlag(FlagType::PROGRESS);
    }

    /**
     * Show statistics (--stats).
     */
    public function stats(): self
    {
        return $this->setFlag(FlagType::STATS);
    }

    /**
     * Show itemized changes (--itemize-changes / -i).
     */
    public function itemizeChanges(): self
    {
        return $this->setFlag(FlagType::ITEMIZE_CHANGES);
    }

    /**
     * Human-readable numbers (--human-readable / -h).
     */
    public function humanReadable(): self
    {
        return $this->setFlag(FlagType::HUMAN_READABLE);
    }

    // ─── Delete Modes ─────────────────────────────────────────────

    /**
     * Delete before transfer (--delete-before).
     */
    public function deleteBefore(): self
    {
        return $this->setFlag(FlagType::DELETE_BEFORE);
    }

    /**
     * Delete after transfer (--delete-after).
     */
    public function deleteAfter(): self
    {
        return $this->setFlag(FlagType::DELETE_AFTER);
    }

    /**
     * Delete excluded files from destination (--delete-excluded).
     */
    public function deleteExcluded(): self
    {
        return $this->setFlag(FlagType::DELETE_EXCLUDED);
    }

    // ─── Command Generation ───────────────────────────────────────

    /**
     * Generate equivalent rsync shell command for debugging.
     */
    public function toCommand(): string
    {
        $parts = ['rsync'];

        foreach ($this->flags as $flag) {
            $parts[] = $flag->value;
        }

        foreach ($this->options as $option) {
            $parts[] = $option->toCommandString();
        }

        if ($this->source !== null) {
            $parts[] = $this->escapeShellPath($this->source);
        }

        if ($this->destination !== null) {
            $parts[] = $this->escapeShellPath($this->destination);
        }

        return implode(' ', $parts);
    }

    /**
     * Escape a path for use in a single-quoted shell argument (POSIX style).
     */
    private function escapeShellPath(string $path): string
    {
        return "'".str_replace("'", "'\\''", $path)."'";
    }

    /**
     * Reset all flags and options.
     */
    public function reset(): self
    {
        $this->flags = new FlagCollection();
        $this->options = new OptionCollection();
        $this->excludes = [];
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
     * Get the exclude patterns added via skip().
     *
     * @return list<string>
     */
    public function getExcludes(): array
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

        $destination = $this->destination ?? '';

        $operation = $this->flags->contains(FlagType::DRY_RUN)
            ? new DryRunSyncOperation()
            : new RealSyncOperation($this->output, $this->fs);

        ['sourceFiles' => $sourceFiles, 'excludedFiles' => $excludedFiles] = $this->scanSource();
        $destinationFiles = $this->shouldDelete() ? $this->scanner->scan($destination) : [];

        $useChecksum = $this->flags->contains(FlagType::CHECKSUM);

        $copied = [];
        $skipped = [];
        $deleted = [];

        foreach ($sourceFiles as $relativePath => $sourceFile) {
            $destinationFile = $destinationFiles[$relativePath]
                ?? $this->scanner->fileAt($destination, $relativePath);

            if ($destinationFile !== null && ! $this->comparator->shouldSync($sourceFile, $destinationFile, $useChecksum)) {
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

        // Dry-run must never mutate the filesystem, so skip directory cleanup entirely.
        if (! $this->flags->contains(FlagType::DRY_RUN)) {
            $this->cleaner->cleanup($destination);
        }

        return new Result(
            copied: $copied,
            deleted: $deleted,
            skipped: [...$skipped, ...array_values($excludedFiles)],
        );
    }

    /**
     * Scan source files, splitting them into included/excluded sets.
     *
     * @return array{sourceFiles: array<string, FileInfo>, excludedFiles: array<string, FileInfo>}
     */
    private function scanSource(): array
    {
        $source = $this->source ?? '';
        $excludes = $this->getEffectiveExcludes();

        ['included' => $sourceFiles, 'excluded' => $excludedFiles] = $this->scanner->partition(
            $this->scanner->scan($source),
            $excludes,
        );

        return [
            'sourceFiles' => $sourceFiles,
            'excludedFiles' => $excludedFiles,
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

            if ($excludes !== [] && $this->matcher->matches($relativePath, $excludes)) {
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
     * Get effective excludes combining skip() patterns and exclude() patterns.
     *
     * @return list<string>
     */
    private function getEffectiveExcludes(): array
    {
        $excludes = $this->excludes;

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

        if (! $this->fs->isDir($this->source)) {
            throw new InvalidArgumentException('Source directory does not exist: '.$this->source);
        }

        if (! $this->fs->isReadable($this->source)) {
            throw new InvalidArgumentException('Source directory is not readable: '.$this->source);
        }
    }
}
