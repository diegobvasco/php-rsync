# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

### Changed
- Introduced a `Filesystem` interface (`LocalFilesystem` + `InMemoryFilesystem`) and injected it into `Rsync` and `RealSyncOperation` as an optional constructor argument.
- Decomposed `Rsync` into focused collaborators: `FileScanner`, `GlobMatcher` (with memoized regex cache), `DirectoryCleaner`, and `Comparator`.
- `FlagCollection` is now enum-only: it stores `FlagType` cases directly. The `Flag` wrapper class was removed.
- Exclusion patterns are stored as a plain `list<string>` instead of being wrapped in `Flag` objects.
- `FileInfo::checksum` is now resolved lazily through a PHP 8.4 property hook (memoized) instead of hashing every scanned file eagerly.
- The destination tree is no longer scanned unless a delete mode is active.
- Added `#[\Override]` to every method that implements an interface or overrides a parent (enforced by Rector).
- Marked `Rsync` `final`; adopted `array_find` and other PHP 8.4/8.5 idioms.

### Fixed
- `--dry-run` no longer mutates the filesystem (it previously removed empty destination directories).
- `Rsync::toCommand()` and `Option::toCommandString()` now POSIX-escape paths/values containing single quotes.
- Renamed the misspelled `FlagType::XTRAS` case to `XATTRS` (value `--xattrs` unchanged).
- `Result::summary()` now correctly pluralizes "file"/"files".
- Hardened `DirectoryCleaner` against missing/empty destinations and `scandir` failures.

### Tooling
- Consolidated and deduplicated `pint.json` (rules were split between the top level and the `rules` object, with duplicates).
- `rector.php` now enforces `#[\Override]` (including on interface implementations).
- Declared `ext-hash` in `composer.json`; refreshed the PHPUnit schema URL.
- Added `AGENTS.md`.

### Added
- `DryRunSyncOperationTest`, `RsyncInMemoryTest`, `GlobMatcherTest`, `FileScannerTest`, `DirectoryCleanerTest`, `ComparatorTest`, and `FilesystemTest`.

## [1.3.0](https://github.com/diegobvasco/php-rsync/compare/v1.2.0...v1.3.0) (2026-07-20)


### Features

* value objects, SRP traits, strategy pattern & full test suite ([#7](https://github.com/diegobvasco/php-rsync/issues/7)) ([de09572](https://github.com/diegobvasco/php-rsync/commit/de09572dffb50dc75e2e2788b9dc1bdad90564fc))

## [1.2.0](https://github.com/diegobvasco/php-rsync/compare/v1.1.0...v1.2.0) (2026-07-19)


### Features

* implement --checksum flag for file comparison ([#5](https://github.com/diegobvasco/php-rsync/issues/5)) ([320f1f9](https://github.com/diegobvasco/php-rsync/commit/320f1f9bb90f56986421280387d7232e094bd1e0))

## [1.1.0](https://github.com/diegobvasco/php-rsync/compare/v1.0.0...v1.1.0) (2026-07-19)


### Features

* add fluent methods for all rsync flags ([#3](https://github.com/diegobvasco/php-rsync/issues/3)) ([dc0527b](https://github.com/diegobvasco/php-rsync/commit/dc0527b7adde5d3381fc2f5382c8b917c54e5d8b))

## 1.0.0 (2026-07-19)


### Features

* add Output interface and TerminalOutput ([#1](https://github.com/diegobvasco/php-rsync/issues/1)) ([87c5e08](https://github.com/diegobvasco/php-rsync/commit/87c5e08a98f08370c9dd5d1f9a8acaf147dbffb3))
* initial release of diegobvasco/php-rsync ([bd6c763](https://github.com/diegobvasco/php-rsync/commit/bd6c763ac0d3995fddc29354d64586034eac2873))


### Miscellaneous Chores

* update image source in README to use webp format ([bcc61c1](https://github.com/diegobvasco/php-rsync/commit/bcc61c1b23b7155faba576c12fe7b034a25e3b5e2))
