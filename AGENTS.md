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
composer test:types    # PHPStan at max level (analyzes src/ ONLY, not tests/)
composer test:unit     # Pest unit + feature tests with 100% coverage gate
composer test:type-coverage  # 100% type coverage gate
composer test:refactor      # Rector dry-run (auto-apply with `composer refactor`)
```

Run a single test: `vendor/bin/pest --filter='scans files recursively'`
or `vendor/bin/pest tests/Unit/FilesystemTest.php`.

If `composer test` shows a transient crash in the type-coverage plugin, re-run
the failing script directly (`composer test:type-coverage`) — the plugin is
occasionally unstable but the underlying metric is 100%.

## CI

- **Tests matrix**: ubuntu/macos/windows × prefer-lowest/prefer-stable, PHP 8.5,
  with `fail-fast: true`. A cancelled macOS/Windows job usually means an earlier
  job failed and cancelled the rest — check the actually failed job, not the
  cancelled ones.
- `composer.lock` is gitignored — CI resolves deps fresh via `composer update`.
- Formats workflow (lint + types + type-coverage + refactor) runs on ubuntu only.
- Releases are automated via **release-please** (conventional commits on `main`);
  do not manually tag or create releases.
- **`CHANGELOG.md` is auto-managed by release-please** — never hand-edit it or
  add an `[Unreleased]` section. Commit messages (`feat:`, `fix:`, `refactor:`,
  etc.) on `main` generate the changelog entry and the version bump automatically.
- **Conventional commits drive Semantic Versioning** — the prefix controls the
  bump:
  - `feat:` → **minor** (x.Y.0)
  - `fix:` → **patch** (x.y.Z)
  - `feat!:` / `fix!:` / `BREAKING CHANGE:` footer → **major** (X.0.0)
  - `refactor:`, `chore:`, `test:`, `docs:`, `style:`, `perf:`, `ci:` → **no
    release** (chores; they land on `main` without a version bump)

  Choose the prefix carefully — a wrong `feat:` on a refactor triggers an
  unintended minor release.

## Architecture notes

- **Namespace layout**: `src/` is split into five domain subnamespaces:
  - `DiegoVasconcelos\Rsync` (root): public API surface — `Rsync`, `Result`,
    `FileInfo`.
  - `DiegoVasconcelos\Rsync\Command`: CLI flag/option model — `FlagType`,
    `FlagCollection`, `Option`, `OptionCollection`.
  - `DiegoVasconcelos\Rsync\Engine`: internal sync collaborators —
    `FileScanner`, `GlobMatcher`, `DirectoryCleaner`, `Comparator`,
    `SyncOperationInterface`, `RealSyncOperation`, `DryRunSyncOperation`.
  - `DiegoVasconcelos\Rsync\Filesystem`: `Filesystem` interface with
    `LocalFilesystem` and `InMemoryFilesystem` implementations.
  - `DiegoVasconcelos\Rsync\Output`: `Output` interface + `TerminalOutput`.
  - `DiegoVasconcelos\Rsync\Support`: shared primitives —
    `AbstractCollection` (base of the immutable collections), `ByteFormatter`
    (trait used by `FileInfo` and `Result`).
- `Rsync` is the public entry point and **must keep its documented fluent API**
  (`copy`, `skip`, `delete`, `archive`, the flag/option methods, `run`,
  `toCommand`, `reset`, and the `Rsync(?Output $output = null, ?Filesystem $filesystem = null)`
  constructor).
- Internal collaborators live as `final` classes under `Engine\`:
  `FileScanner`, `GlobMatcher` (memoized regex cache), `DirectoryCleaner`,
  `Comparator`, plus the `SyncOperationInterface` with `RealSyncOperation`
  and `DryRunSyncOperation` implementations.
- `FlagCollection` is **enum-only** — it stores `FlagType` cases, not strings.
- Exclusion patterns are plain `list<string>` on `Rsync` (via `skip()`), not flags.
- `FileInfo::checksum` is **lazy** (PHP 8.4 property hook + memoized closure);
  never hash during a scan unless `--checksum` is active.
- Every method that implements an interface or overrides a parent carries
  `#[\Override]` (enforced by Rector).
- **Path separator convention**: `/` (forward slash) is the canonical separator
  throughout `src/`. Never use `DIRECTORY_SEPARATOR` (it is `\` on Windows while
  `InMemoryFilesystem` uses `/` internally). `LocalFilesystem` normalizes
  `getPathname()` to `/` via `str_replace('\\', '/', ...)`; any new filesystem
  implementation must do the same.

## Testing

- 100% line coverage and 100% type coverage are **enforced** — keep them.
- Real-filesystem tests use `base_tests_dir()` under `tmp/` (gitignored) and
  clean up with `deleteTestDirectory()`. On Windows, `base_tests_dir()` inherits
  native `\` from `__DIR__` — normalize expected paths to `/` when comparing
  against scan output (see `FilesystemTest::beforeEach`).
- Prefer `InMemoryFilesystem` for new unit tests of sync logic (fast, disk-less).
- Override single `Filesystem` methods in tests by extending
  `Tests\Support\FilesystemDecorator` (the concrete implementations are `final`).
