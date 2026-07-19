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
5. Deletes destination files that no longer exist in the source
6. Removes empty directories from the destination
7. Returns a `Result` object with full sync details

## API

### `Rsync`

| Method | Description |
|---|---|
| `copy(string $source, string $destination): self` | Set source and destination directories |
| `skip(string\|array $patterns): self` | Add glob patterns to exclude from sync |
| `run(): Result` | Execute the sync and return results |

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
