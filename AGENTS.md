# AGENTS.md

Guidance for AI agents (and humans) working on this repository.

## Project

`diegobvasco/php-rsync` — a pure-PHP directory synchronization library with a
fluent rsync-inspired API. Requires PHP 8.5+. No production dependencies.

## Commands

Run from the repository root. All quality gates must pass before a commit.

```bash
composer test          # Full pipeline (lint, type-coverage, unit, types, refactor)
composer test:lint     # Laravel Pint — code style (auto-fix with `composer lint`)
composer test:types    # PHPStan at max level (analyzes src/)
composer test:unit     # Pest unit + feature tests with 100% coverage gate
composer test:type-coverage  # 100% type coverage gate
composer test:refactor      # Rector dry-run (auto-apply with `composer refactor`)
```

If `composer test` shows a transient crash in the type-coverage plugin, re-run
the failing script directly (`composer test:type-coverage`) — the plugin is
occasionally unstable but the underlying metric is 100%.

## Architecture notes

- `Rsync` is the public entry point and **must keep its documented fluent API**
  (`copy`, `skip`, `delete`, `archive`, the flag/option methods, `run`,
  `toCommand`, `reset`, and the `Rsync(?Output $output = null, ?Filesystem $filesystem = null)`
  constructor).
- Internal collaborators live as `final` classes: `FileScanner`, `GlobMatcher`
  (memoized regex cache), `DirectoryCleaner`, `Comparator`, plus the
  `Filesystem` interface with `LocalFilesystem` and `InMemoryFilesystem`.
- `FlagCollection` is **enum-only** — it stores `FlagType` cases, not strings.
- Exclusion patterns are plain `list<string>` on `Rsync` (via `skip()`), not flags.
- `FileInfo::checksum` is **lazy** (PHP 8.4 property hook + memoized closure);
  never hash during a scan unless `--checksum` is active.
- Every method that implements an interface or overrides a parent carries
  `#[\Override]` (enforced by Rector).

## Testing

- 100% line coverage and 100% type coverage are **enforced** — keep them.
- Real-filesystem tests use `base_tests_dir()` under `tmp/` (gitignored) and
  clean up with `deleteTestDirectory()`.
- Prefer `InMemoryFilesystem` for new unit tests of sync logic (fast, disk-less).
- Override single `Filesystem` methods in tests by extending
  `Tests\Support\FilesystemDecorator` (the concrete implementations are `final`).
