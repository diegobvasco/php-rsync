<p align="center">
    <img src="https://raw.githubusercontent.com/diegobvasco/php-rsync/main/docs/php-rsync.webp" height="300" alt="PHP Rsync">
    <p align="center">
        <a href="https://github.com/diegobvasco/php-rsync/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/diegobvasco/php-rsync/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/diegobvasco/php-rsync"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/diegobvasco/php-rsync"></a>
        <a href="https://packagist.org/packages/diegobvasco/php-rsync"><img alt="Latest Version" src="https://img.shields.io/packagist/v/diegobvasco/php-rsync"></a>
        <a href="https://packagist.org/packages/diegobvasco/php-rsync"><img alt="License" src="https://img.shields.io/packagist/l/diegobvasco/php-rsync"></a>
    </p>
</p>

------

A pure-PHP directory synchronization tool inspired by the `rsync` command. Sync directories by copying new and modified files, removing deleted files, and skipping exclusions — all with a clean fluent API.

> **Requires [PHP 8.5+](https://php.net/releases/)**

## Installation

```bash
composer require diegobvasco/php-rsync
```

## Usage

### Basic Sync

```php
use DiegoVasconcelos\Rsync\Rsync;

$result = (new Rsync())
    ->copy('/path/to/source', '/path/to/destination')
    ->run();

echo $result->summary();
// Copied: 5 files (12.5 KB)
// Deleted: 2 files (1.2 KB)
// Skipped: 3 files
```

### With Exclusions

```php
$result = (new Rsync())
    ->copy('/path/to/source', '/path/to/destination')
    ->skip(['*.log', 'vendor/*', '.git', 'node_modules/'])
    ->run();

foreach ($result->copied as $file) {
    echo $file->relativePath . ' - ' . $file->formattedSize() . "\n";
}
```

### With Delete Mode

```php
$result = (new Rsync())
    ->copy('/path/to/source', '/path/to/destination')
    ->delete()
    ->run();
```

### With Real-Time Output

```php
use DiegoVasconcelos\Rsync\Rsync;
use DiegoVasconcelos\Rsync\TerminalOutput;

// Print each file action to the terminal as it happens
$result = (new Rsync(new TerminalOutput()))
    ->copy('/path/to/source', '/path/to/destination')
    ->delete()
    ->run();

// Output:
// COPY new-file.txt (2.5 KB)
// DELETE old-file.txt
// SKIP unchanged.txt
```

### Dry Run

```php
$result = (new Rsync())
    ->copy('/path/to/source', '/path/to/destination')
    ->delete()
    ->dryRun()
    ->run();

// No files are actually copied or deleted
echo $result->summary();
```

### Accessing Results

```php
$result->copiedCount();    // Number of files copied
$result->deletedCount();   // Number of files deleted
$result->skippedCount();   // Number of files skipped
$result->totalBytesCopied(); // Total bytes copied
$result->copiedPaths();    // Array of copied file paths
$result->deletedPaths();   // Array of deleted file paths
$result->skippedPaths();   // Array of skipped file paths
$result->summary();        // Human-readable summary string
```

## Skip Pattern Syntax

| Pattern | Meaning |
|---|---|
| `*.log` | All `.log` files |
| `vendor/*` | Everything inside `vendor/` |
| `**/*.log` | Any `.log` file in any subdirectory |
| `logs/` | The `logs/` directory and all its contents |
| `??.txt` | Two-character `.txt` filenames |
| `[abc].txt` | Files named `a.txt`, `b.txt`, or `c.txt` |
| `[^ab].log` | Any single-character `.log` filename except `a` or `b` |

## How It Works

1. Scans the source directory for all files
2. Filters out files matching exclusion patterns
3. Compares each file's **modification time** and **size** against the destination
4. Copies only new or changed files
5. Deletes destination files that no longer exist in the source (when `delete()` is enabled)
6. Removes empty directories from the destination
7. Returns a `Result` object with full sync details

## API

### `Rsync`

```php
new Rsync(?Output $output = null)
```

The optional `Output` interface receives callbacks for each file action during sync. A `TerminalOutput` implementation is included that prints to STDOUT (or a custom stream).

#### Core

| Method | Description |
|---|---|
| `copy(string $source, string $destination): self` | Set source and destination directories |
| `skip(string\|array $patterns): self` | Add glob patterns to exclude from sync |
| `delete(): self` | Enable delete mode — removes destination files not in source |
| `recursive(): self` | Enable recursive mode |
| `archive(): self` | Enable archive mode (equivalent to `-rlptgoD`) |
| `run(): Result` | Execute the sync and return results |
| `toCommand(): string` | Generate equivalent rsync shell command for debugging |
| `reset(): self` | Reset all flags and options |

#### Metadata Preservation

| Method | Description |
|---|---|
| `times(): self` | Preserve modification times (`--times`) |
| `perms(): self` | Preserve permissions (`--perms`) |
| `owner(): self` | Preserve owner (`--owner`) |
| `group(): self` | Preserve group (`--group`) |
| `acls(): self` | Preserve ACLs (`--acls`) |
| `xattrs(): self` | Preserve extended attributes (`--xattrs`) |
| `devices(): self` | Preserve device files (`--devices`) |
| `specials(): self` | Preserve special files (`--specials`) |
| `numericIds(): self` | Don't map uid/gid values (`--numeric-ids`) |

#### Comparison

| Method | Description |
|---|---|
| `checksum(): self` | Use checksum instead of mod-time & size (`--checksum`) |
| `ignoreTimes(): self` | Don't skip files that match size and time (`--ignore-times`) |
| `sizeOnly(): self` | Skip files that match size only (`--size-only`) |
| `update(): self` | Skip files newer on the receiver (`--update`) |

#### Excludes / Includes

| Method | Description |
|---|---|
| `exclude(string\|array $patterns): self` | Add patterns to exclude (`--exclude`) |
| `excludeFrom(string $file): self` | Read exclude patterns from file (`--exclude-from`) |
| `excludeDir(string\|array $patterns): self` | Add directory exclusion patterns (`--exclude-dir`) |
| `include(string\|array $patterns): self` | Add patterns to include (`--include`) |
| `includeFrom(string $file): self` | Read include patterns from file (`--include-from`) |
| `pruneEmptyDirs(): self` | Remove empty directories from file list (`--prune-empty-dirs`) |

#### Backup

| Method | Description |
|---|---|
| `backup(): self` | Make backups of changed files (`--backup`) |
| `backupDir(string $dir): self` | Set backup directory (`--backup-dir`) |
| `suffix(string $suffix): self` | Set backup suffix (`--suffix`) |

#### Symlinks / Hardlinks

| Method | Description |
|---|---|
| `links(): self` | Copy symlinks as symlinks (`--links`) |
| `copyLinks(): self` | Transform symlinks to referent files (`--copy-links`) |
| `copyUnsafeLinks(): self` | Transform unsafe symlinks to referent files in directories (`--copy-unsafe-links`) |
| `safeLinks(): self` | Ignore symlinks that go outside tree (`--safe-links`) |
| `hardLinks(): self` | Preserve hard links (`--hard-links`) |

#### Size Limits

| Method | Description |
|---|---|
| `maxSize(int\|string $size): self` | Maximum file size to transfer (`--max-size`) |
| `minSize(int\|string $size): self` | Minimum file size to transfer (`--min-size`) |

#### Behavior

| Method | Description |
|---|---|
| `dryRun(): self` | Show what would be done without doing it (`--dry-run`) |
| `force(): self` | Force deletion of non-empty directories (`--force`) |
| `removeSourceFiles(): self` | Remove source files after successful transfer (`--remove-source-files`) |

#### Output

| Method | Description |
|---|---|
| `verbose(): self` | Verbose output (`--verbose`) |
| `quiet(): self` | Suppress output (`--quiet`) |
| `progress(): self` | Show progress (`--progress`) |
| `stats(): self` | Show statistics (`--stats`) |
| `itemizeChanges(): self` | Show itemized changes (`--itemize-changes`) |
| `humanReadable(): self` | Human-readable numbers (`--human-readable`) |

#### Delete Modes

| Method | Description |
|---|---|
| `delete(): self` | Delete files in destination not in source (`--delete`) |
| `deleteBefore(): self` | Delete before transfer (`--delete-before`) |
| `deleteAfter(): self` | Delete after transfer (`--delete-after`) |
| `deleteExcluded(): self` | Delete excluded files from destination (`--delete-excluded`) |

### `Result`

| Method | Description |
|---|---|
| `copiedCount(): int` | Number of files copied |
| `deletedCount(): int` | Number of files deleted |
| `skippedCount(): int` | Number of files skipped |
| `totalBytesCopied(): int` | Total bytes copied |
| `totalBytesDeleted(): int` | Total bytes deleted |
| `summary(): string` | Human-readable summary |
| `copiedPaths(): array` | List of copied file paths |
| `deletedPaths(): array` | List of deleted file paths |
| `skippedPaths(): array` | List of skipped file paths |

### `FileInfo`

| Method | Description |
|---|---|
| `formattedSize(): string` | Human-readable file size (e.g. "12.5 KB") |
| `formattedMtime(): string` | ISO 8601 formatted modification time |

### `Output` (interface)

Implement this to receive real-time file action callbacks during sync.

| Method | Description |
|---|---|
| `copied(FileInfo $file): void` | Called when a file is copied |
| `deleted(FileInfo $file): void` | Called when a file is deleted |
| `skipped(FileInfo $file): void` | Called when a file is skipped |

### `TerminalOutput`

Built-in implementation of `Output` that prints actions to a stream.

```php
new TerminalOutput(?resource $stream = null)
```

Defaults to `STDOUT`. Pass a custom stream to redirect output:

```php
$output = new TerminalOutput(fopen('php://memory', 'r+'));
```

## Quality

This package enforces high code quality standards:

- **100% code coverage** via Pest
- **100% type coverage** via `pestphp/pest-plugin-type-coverage`
- **PHPStan at max level** for static analysis
- **Laravel Pint** for code style
- **Rector** for automated refactoring

```bash
composer test          # Run entire test suite
composer test:unit     # Unit tests only
composer test:lint     # Code style check
composer test:types    # Static analysis
composer lint          # Auto-fix code style
composer refactor      # Auto-refactor code
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.
