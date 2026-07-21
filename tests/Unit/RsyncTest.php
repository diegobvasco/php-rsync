<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\LocalFilesystem;
use DiegoVasconcelos\Rsync\Rsync;
use Tests\Support\FilesystemDecorator;

beforeEach(function (): void {
    $this->sourceDir = base_tests_dir('/deploy_sync_test_source_'.uniqid());
    $this->destDir = base_tests_dir('/deploy_sync_test_dest_'.uniqid());

    mkdir($this->sourceDir, recursive: true);
    mkdir($this->destDir, recursive: true);
});

afterEach(function (): void {
    deleteTestDirectory($this->sourceDir);
    deleteTestDirectory($this->destDir);
});

it('copies files from source to destination', function (): void {
    file_put_contents($this->sourceDir.'/file1.txt', 'Hello World');
    file_put_contents($this->sourceDir.'/file2.txt', 'Foo Bar');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(2)
        ->and($result->deletedCount())->toBe(0)
        ->and(file_get_contents($this->destDir.'/file1.txt'))->toBe('Hello World')
        ->and(file_get_contents($this->destDir.'/file2.txt'))->toBe('Foo Bar');
});

it('copies files in nested directories', function (): void {
    mkdir($this->sourceDir.'/subdir/nested', recursive: true);
    file_put_contents($this->sourceDir.'/subdir/nested/file.txt', 'Nested content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_get_contents($this->destDir.'/subdir/nested/file.txt'))->toBe('Nested content');
});

it('skips files matching exact name pattern', function (): void {
    file_put_contents($this->sourceDir.'/keep.txt', 'Keep this');
    file_put_contents($this->sourceDir.'/skip.log', 'Skip this');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('skip.log')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/skip.log'))->toBeFalse();
});

it('skips files matching wildcard pattern', function (): void {
    file_put_contents($this->sourceDir.'/code.php', 'PHP code');
    file_put_contents($this->sourceDir.'/error.log', 'Error log');
    file_put_contents($this->sourceDir.'/debug.log', 'Debug log');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('*.log')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(2)
        ->and(file_exists($this->destDir.'/error.log'))->toBeFalse()
        ->and(file_exists($this->destDir.'/debug.log'))->toBeFalse();
});

it('skips directories matching pattern', function (): void {
    mkdir($this->sourceDir.'/vendor/package', recursive: true);
    mkdir($this->sourceDir.'/src', recursive: true);
    file_put_contents($this->sourceDir.'/vendor/package/file.php', 'Vendor code');
    file_put_contents($this->sourceDir.'/src/app.php', 'App code');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip(['vendor/*', '.git'])
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/src/app.php'))->toBeTrue()
        ->and(file_exists($this->destDir.'/vendor/package/file.php'))->toBeFalse();
});

it('deletes files in destination not present in source', function (): void {
    file_put_contents($this->destDir.'/old_file.txt', 'Old content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/old_file.txt'))->toBeFalse();
});

it('does not delete files that match skip pattern', function (): void {
    file_put_contents($this->destDir.'/important.log', 'Important log');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('*.log')
        ->run();

    expect($result->deletedCount())->toBe(0)
        ->and(file_exists($this->destDir.'/important.log'))->toBeTrue();
});

it('only copies changed files based on mtime and size', function (): void {
    file_put_contents($this->sourceDir.'/unchanged.txt', 'Same content');
    file_put_contents($this->sourceDir.'/changed.txt', 'Old content');

    // First sync
    new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    // Modify one file
    sleep(1); // Ensure different mtime
    file_put_contents($this->sourceDir.'/changed.txt', 'New content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(1)
        ->and(file_get_contents($this->destDir.'/changed.txt'))->toBe('New content');
});

it('returns correct file sizes in result', function (): void {
    file_put_contents($this->sourceDir.'/small.txt', 'Small');
    file_put_contents($this->sourceDir.'/large.txt', str_repeat('A', 1024));

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->totalBytesCopied())->toBe(5 + 1024);
});

it('returns file paths in result', function (): void {
    file_put_contents($this->sourceDir.'/file1.txt', 'Content 1');
    file_put_contents($this->sourceDir.'/file2.log', 'Content 2');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('*.log')
        ->run();

    expect($result->copiedPaths())->toBe(['file1.txt'])
        ->and($result->skippedPaths())->toBe(['file2.log']);
});

it('generates human readable summary', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    $summary = $result->summary();

    expect($summary)->toContain('Copied: 1 file')
        ->and($summary)->toContain('Deleted: 0 files')
        ->and($summary)->toContain('Skipped: 0 files');
});

it('throws exception when source not set', function (): void {
    new Rsync()->run();
})->throws(InvalidArgumentException::class, 'Source and destination must be set');

it('throws exception when source directory does not exist', function (): void {
    new Rsync()
        ->copy('/nonexistent/path', $this->destDir)
        ->run();
})->throws(InvalidArgumentException::class, 'Source directory does not exist');

it('handles empty source directory', function (): void {
    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->deletedCount())->toBe(0);
});

it('handles multiple skip patterns', function (): void {
    file_put_contents($this->sourceDir.'/app.php', 'App');
    file_put_contents($this->sourceDir.'/debug.log', 'Log');
    file_put_contents($this->sourceDir.'/config.env', 'Config');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip(['*.log', '*.env'])
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(2);
});

it('removes empty directories after sync', function (): void {
    mkdir($this->destDir.'/old_empty_dir', recursive: true);
    file_put_contents($this->destDir.'/old_empty_dir/file.txt', 'Old content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect(is_dir($this->destDir.'/old_empty_dir'))->toBeFalse();
});

it('does not remove empty directories in dry-run mode', function (): void {
    mkdir($this->destDir.'/already_empty_dir', recursive: true);

    new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->dryRun()
        ->run();

    // Dry-run must not mutate the filesystem: a pre-existing empty directory must remain.
    expect(is_dir($this->destDir.'/already_empty_dir'))->toBeTrue();
});

it('skips files matching ? wildcard pattern', function (): void {
    file_put_contents($this->sourceDir.'/a.txt', 'File A');
    file_put_contents($this->sourceDir.'/b.txt', 'File B');
    file_put_contents($this->sourceDir.'/cc.txt', 'File CC');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('??.txt')
        ->run();

    expect($result->copiedCount())->toBe(2)
        ->and($result->skippedCount())->toBe(1);
});

it('skips files matching character class pattern', function (): void {
    file_put_contents($this->sourceDir.'/a.txt', 'File A');
    file_put_contents($this->sourceDir.'/b.txt', 'File B');
    file_put_contents($this->sourceDir.'/z.txt', 'File Z');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('[ab].txt')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(2);
});

it('skips directories matching trailing slash pattern', function (): void {
    mkdir($this->sourceDir.'/logs', recursive: true);
    file_put_contents($this->sourceDir.'/logs/app.log', 'Log content');
    file_put_contents($this->sourceDir.'/code.php', 'PHP code');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('logs/')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/code.php'))->toBeTrue()
        ->and(file_exists($this->destDir.'/logs/app.log'))->toBeFalse();
});

it('skips files matching double star pattern with trailing slash', function (): void {
    mkdir($this->sourceDir.'/deep/nested/path', recursive: true);
    file_put_contents($this->sourceDir.'/deep/nested/path/file.txt', 'Deep file');
    file_put_contents($this->sourceDir.'/shallow.txt', 'Shallow file');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('deep/**/')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/shallow.txt'))->toBeTrue()
        ->and(file_exists($this->destDir.'/deep/nested/path/file.txt'))->toBeFalse();
});

it('returns empty array for non-existent source directory', function (): void {
    mkdir($this->sourceDir.'/nonexistent', recursive: true);

    $result = new Rsync()
        ->copy($this->sourceDir.'/nonexistent', $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(0);
});

it('preserves existing destination files not in source', function (): void {
    file_put_contents($this->destDir.'/existing.txt', 'Existing content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect(file_exists($this->destDir.'/existing.txt'))->toBeFalse();
});

it('skips files matching negated character class pattern', function (): void {
    file_put_contents($this->sourceDir.'/a.log', 'Log A');
    file_put_contents($this->sourceDir.'/b.log', 'Log B');
    file_put_contents($this->sourceDir.'/c.log', 'Log C');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('[^ab].log')
        ->run();

    expect($result->copiedCount())->toBe(2)
        ->and($result->skippedCount())->toBe(1);
});

it('handles unreadable source directory', function (): void {
    $unreadableDir = $this->sourceDir.'/unreadable';
    mkdir($unreadableDir);

    $fs = new class(new LocalFilesystem()) extends FilesystemDecorator
    {
        public function isReadable(string $path): bool
        {
            return false;
        }
    };

    $rsync = new Rsync(null, $fs);

    try {
        $result = $rsync
            ->copy($unreadableDir, $this->destDir)
            ->run();

        expect($result->copiedCount())->toBe(0);
    } catch (InvalidArgumentException $invalidArgumentException) {
        expect($invalidArgumentException->getMessage())->toContain('not readable');
    }
});

it('handles non-file path in delete operation', function (): void {
    // Create a directory in destination that doesn't exist in source
    mkdir($this->destDir.'/orphan_dir', recursive: true);

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    // Directory should be cleaned up by empty directory removal
    expect(is_dir($this->destDir.'/orphan_dir'))->toBeFalse();
});

// ─── toCommand() Tests ───────────────────────────────────────────

it('generates basic rsync command', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->toCommand();

    expect($command)->toBe("rsync '/src' '/dest'");
});

it('generates command with delete flag', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->delete()
        ->toCommand();

    expect($command)->toContain('--delete')
        ->and($command)->toContain("'/src' '/dest'");
});

it('generates command with archive flag', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->archive()
        ->toCommand();

    expect($command)->toContain('--archive');
});

it('generates command with multiple flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->delete()
        ->verbose()
        ->recursive()
        ->toCommand();

    expect($command)->toContain('--delete')
        ->and($command)->toContain('--verbose')
        ->and($command)->toContain('--recursive');
});

it('generates command with exclude option', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->exclude('*.log')
        ->toCommand();

    expect($command)->toContain("--exclude='*.log'");
});

it('generates command with multiple excludes', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->exclude(['*.log', '.git'])
        ->toCommand();

    expect($command)->toContain("--exclude='*.log'")
        ->and($command)->toContain("--exclude='.git'");
});

it('generates command with exclude-from option', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->excludeFrom('/patterns.txt')
        ->toCommand();

    expect($command)->toContain("--exclude-from='/patterns.txt'");
});

it('generates command with include option', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->include('*.php')
        ->toCommand();

    expect($command)->toContain("--include='*.php'");
});

it('generates command with backup options', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->backup()
        ->backupDir('/backup')
        ->suffix('.bak')
        ->toCommand();

    expect($command)->toContain('--backup')
        ->and($command)->toContain("--backup-dir='/backup'")
        ->and($command)->toContain("--suffix='.bak'");
});

it('generates command with size options', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->maxSize('10M')
        ->minSize('1K')
        ->toCommand();

    expect($command)->toContain("--max-size='10M'")
        ->and($command)->toContain("--min-size='1K'");
});

it('generates command with integer size options', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->maxSize(1048576)
        ->toCommand();

    expect($command)->toContain("--max-size='1048576'");
});

it('generates command with all metadata flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->times()
        ->perms()
        ->owner()
        ->group()
        ->acls()
        ->xattrs()
        ->devices()
        ->specials()
        ->numericIds()
        ->toCommand();

    expect($command)->toContain('--times')
        ->and($command)->toContain('--perms')
        ->and($command)->toContain('--owner')
        ->and($command)->toContain('--group')
        ->and($command)->toContain('--acls')
        ->and($command)->toContain('--xattrs')
        ->and($command)->toContain('--devices')
        ->and($command)->toContain('--specials')
        ->and($command)->toContain('--numeric-ids');
});

it('generates command with comparison flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->checksum()
        ->ignoreTimes()
        ->sizeOnly()
        ->update()
        ->toCommand();

    expect($command)->toContain('--checksum')
        ->and($command)->toContain('--ignore-times')
        ->and($command)->toContain('--size-only')
        ->and($command)->toContain('--update');
});

it('generates command with symlink flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->links()
        ->copyLinks()
        ->copyUnsafeLinks()
        ->safeLinks()
        ->hardLinks()
        ->toCommand();

    expect($command)->toContain('--links')
        ->and($command)->toContain('--copy-links')
        ->and($command)->toContain('--copy-unsafe-links')
        ->and($command)->toContain('--safe-links')
        ->and($command)->toContain('--hard-links');
});

it('generates command with behavior flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->dryRun()
        ->force()
        ->removeSourceFiles()
        ->toCommand();

    expect($command)->toContain('--dry-run')
        ->and($command)->toContain('--force')
        ->and($command)->toContain('--remove-source-files');
});

it('generates command with output flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->verbose()
        ->quiet()
        ->progress()
        ->stats()
        ->itemizeChanges()
        ->humanReadable()
        ->toCommand();

    expect($command)->toContain('--verbose')
        ->and($command)->toContain('--quiet')
        ->and($command)->toContain('--progress')
        ->and($command)->toContain('--stats')
        ->and($command)->toContain('--itemize-changes')
        ->and($command)->toContain('--human-readable');
});

it('generates command with delete mode flags', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->deleteBefore()
        ->deleteAfter()
        ->deleteExcluded()
        ->toCommand();

    expect($command)->toContain('--delete-before')
        ->and($command)->toContain('--delete-after')
        ->and($command)->toContain('--delete-excluded');
});

it('generates command with prune-empty-dirs flag', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->pruneEmptyDirs()
        ->toCommand();

    expect($command)->toContain('--prune-empty-dirs');
});

it('generates command with exclude-dir option', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->excludeDir('node_modules')
        ->toCommand();

    expect($command)->toContain("--exclude-dir='node_modules'");
});

it('generates command with include-from option', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->includeFrom('/includes.txt')
        ->toCommand();

    expect($command)->toContain("--include-from='/includes.txt'");
});

it('deduplicates flags in command', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->delete()
        ->delete()
        ->verbose()
        ->verbose()
        ->toCommand();

    $deleteCount = substr_count($command, '--delete');
    $verboseCount = substr_count($command, '--verbose');

    expect($deleteCount)->toBe(1)
        ->and($verboseCount)->toBe(1);
});

it('resets all flags and options', function (): void {
    $rsync = new Rsync();
    $rsync->copy('/src', '/dest')
        ->delete()
        ->verbose()
        ->exclude('*.log');

    $rsync->reset();

    $command = $rsync->copy('/src2', '/dest2')->toCommand();

    expect($command)->not->toContain('--delete')
        ->and($command)->not->toContain('--verbose')
        ->and($command)->not->toContain('--exclude')
        ->and($command)->toContain("'/src2' '/dest2'");
});

it('escapes single quotes in command paths', function (): void {
    $command = new Rsync()
        ->copy("/var/it's/src", "/var/it's/dest")
        ->toCommand();

    // Embedded single quotes must be escaped POSIX-style (' -> '\'') so the command stays valid.
    expect($command)->toBe("rsync '/var/it'\\''s/src' '/var/it'\\''s/dest'");
});
// ─── dryRun() Tests ──────────────────────────────────────────────

it('does not copy files in dry-run mode', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->dryRun()
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/file.txt'))->toBeFalse();
});

it('does not delete files in dry-run mode', function (): void {
    file_put_contents($this->destDir.'/existing.txt', 'Existing');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->dryRun()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/existing.txt'))->toBeTrue();
});

it('reports what would be copied in dry-run mode', function (): void {
    file_put_contents($this->sourceDir.'/file1.txt', 'Content 1');
    file_put_contents($this->sourceDir.'/file2.txt', 'Content 2');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->dryRun()
        ->run();

    expect($result->copiedPaths())->toContain('file1.txt')
        ->and($result->copiedPaths())->toContain('file2.txt');
});

it('reports what would be deleted in dry-run mode', function (): void {
    file_put_contents($this->destDir.'/old_file.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->dryRun()
        ->run();

    expect($result->deletedPaths())->toBe(['old_file.txt']);
});

it('skips already synced files in dry-run mode', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');
    file_put_contents($this->destDir.'/file.txt', 'Content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->dryRun()
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(1);
});

it('skips excluded files in dry-run delete mode', function (): void {
    file_put_contents($this->destDir.'/keep.txt', 'Keep');
    file_put_contents($this->destDir.'/old.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->exclude('old.txt')
        ->dryRun()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and($result->deletedPaths())->not->toContain('old.txt')
        ->and(file_exists($this->destDir.'/old.txt'))->toBeTrue();
});

it('skips source files that also exist in destination during dry-run delete', function (): void {
    file_put_contents($this->sourceDir.'/shared.txt', 'Content');
    file_put_contents($this->destDir.'/shared.txt', 'Content');
    file_put_contents($this->destDir.'/extra.txt', 'Extra');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->dryRun()
        ->run();

    expect($result->deletedPaths())->toBe(['extra.txt']);
});

it('preserves destination files that exist in source during delete', function (): void {
    file_put_contents($this->sourceDir.'/shared.txt', 'Source content');
    file_put_contents($this->destDir.'/shared.txt', 'Dest content');
    file_put_contents($this->destDir.'/extra.txt', 'Extra');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect($result->deletedPaths())->toBe(['extra.txt'])
        ->and(file_exists($this->destDir.'/shared.txt'))->toBeTrue();
});

it('preserves excluded destination files during delete', function (): void {
    file_put_contents($this->destDir.'/keep.log', 'Log content');
    file_put_contents($this->destDir.'/old.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->exclude('*.log')
        ->run();

    expect($result->deletedPaths())->toBe(['old.txt'])
        ->and(file_exists($this->destDir.'/keep.log'))->toBeTrue();
});

// ─── Delete Behavior Tests ───────────────────────────────────────

it('does not delete files when delete flag is not set', function (): void {
    file_put_contents($this->destDir.'/existing.txt', 'Existing');
    file_put_contents($this->sourceDir.'/new.txt', 'New');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->deletedCount())->toBe(0)
        ->and(file_exists($this->destDir.'/existing.txt'))->toBeTrue();
});

it('deletes files when delete flag is explicitly set', function (): void {
    file_put_contents($this->destDir.'/old_file.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/old_file.txt'))->toBeFalse();
});

it('deletes files when delete-before flag is set', function (): void {
    file_put_contents($this->destDir.'/old_file.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->deleteBefore()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/old_file.txt'))->toBeFalse();
});

it('deletes files when delete-after flag is set', function (): void {
    file_put_contents($this->destDir.'/old_file.txt', 'Old');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->deleteAfter()
        ->run();

    expect($result->deletedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/old_file.txt'))->toBeFalse();
});

// ─── exclude() Method Tests ──────────────────────────────────────

it('excludes files using exclude method', function (): void {
    file_put_contents($this->sourceDir.'/keep.txt', 'Keep');
    file_put_contents($this->sourceDir.'/skip.log', 'Skip');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->exclude('*.log')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/skip.log'))->toBeFalse();
});

it('excludes multiple patterns with exclude method', function (): void {
    file_put_contents($this->sourceDir.'/app.php', 'App');
    file_put_contents($this->sourceDir.'/debug.log', 'Log');
    file_put_contents($this->sourceDir.'/config.env', 'Config');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->exclude(['*.log', '*.env'])
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(2);
});

it('combines skip and exclude methods', function (): void {
    file_put_contents($this->sourceDir.'/app.php', 'App');
    file_put_contents($this->sourceDir.'/debug.log', 'Log');
    file_put_contents($this->sourceDir.'/config.env', 'Config');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->skip('*.log')
        ->exclude('*.env')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and($result->skippedCount())->toBe(2);
});

// ─── excludeDir() Method Tests ───────────────────────────────────

it('excludes directories using excludeDir method', function (): void {
    mkdir($this->sourceDir.'/vendor/package', recursive: true);
    mkdir($this->sourceDir.'/src', recursive: true);
    file_put_contents($this->sourceDir.'/vendor/package/file.php', 'Vendor');
    file_put_contents($this->sourceDir.'/src/app.php', 'App');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->excludeDir('vendor')
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_exists($this->destDir.'/src/app.php'))->toBeTrue()
        ->and(file_exists($this->destDir.'/vendor/package/file.php'))->toBeFalse();
});

// ─── include() Method Tests ──────────────────────────────────────

it('includes files using include method', function (): void {
    file_put_contents($this->sourceDir.'/code.php', 'PHP');
    file_put_contents($this->sourceDir.'/readme.md', 'README');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->include('*.php')
        ->run();

    expect($result->copiedCount())->toBe(2);
});

// ─── backup() Method Tests ───────────────────────────────────────

it('handles backup flag in command generation', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->backup()
        ->toCommand();

    expect($command)->toContain('--backup');
});

it('handles backup-dir option in command generation', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->backupDir('/my/backup')
        ->toCommand();

    expect($command)->toContain("--backup-dir='/my/backup'");
});

it('handles suffix option in command generation', function (): void {
    $command = new Rsync()
        ->copy('/src', '/dest')
        ->suffix('.backup')
        ->toCommand();

    expect($command)->toContain("--suffix='.backup'");
});

// ─── Complex Command Generation Tests ────────────────────────────

it('generates complex command with all options', function (): void {
    $command = new Rsync()
        ->copy('/var/www/html', '/backup/www')
        ->archive()
        ->delete()
        ->deleteExcluded()
        ->verbose()
        ->progress()
        ->stats()
        ->humanReadable()
        ->exclude(['*.log', '.git', 'vendor/node_modules'])
        ->backup()
        ->backupDir('/backup/old')
        ->suffix('.bak')
        ->checksum()
        ->hardLinks()
        ->acls()
        ->xattrs()
        ->pruneEmptyDirs()
        ->toCommand();

    expect($command)->toContain('rsync')
        ->and($command)->toContain('--archive')
        ->and($command)->toContain('--delete')
        ->and($command)->toContain('--delete-excluded')
        ->and($command)->toContain('--verbose')
        ->and($command)->toContain('--progress')
        ->and($command)->toContain('--stats')
        ->and($command)->toContain('--human-readable')
        ->and($command)->toContain("--exclude='*.log'")
        ->and($command)->toContain("--exclude='.git'")
        ->and($command)->toContain("--exclude='vendor/node_modules'")
        ->and($command)->toContain('--backup')
        ->and($command)->toContain("--backup-dir='/backup/old'")
        ->and($command)->toContain("--suffix='.bak'")
        ->and($command)->toContain('--checksum')
        ->and($command)->toContain('--hard-links')
        ->and($command)->toContain('--acls')
        ->and($command)->toContain('--xattrs')
        ->and($command)->toContain('--prune-empty-dirs')
        ->and($command)->toContain("'/var/www/html' '/backup/www'");
});

// ─── Fluent Interface Tests ──────────────────────────────────────

it('returns self from all flag methods', function (): void {
    $rsync = new Rsync();

    expect($rsync->delete())->toBe($rsync)
        ->and($rsync->recursive())->toBe($rsync)
        ->and($rsync->archive())->toBe($rsync)
        ->and($rsync->times())->toBe($rsync)
        ->and($rsync->perms())->toBe($rsync)
        ->and($rsync->owner())->toBe($rsync)
        ->and($rsync->group())->toBe($rsync)
        ->and($rsync->acls())->toBe($rsync)
        ->and($rsync->xattrs())->toBe($rsync)
        ->and($rsync->devices())->toBe($rsync)
        ->and($rsync->specials())->toBe($rsync)
        ->and($rsync->numericIds())->toBe($rsync)
        ->and($rsync->checksum())->toBe($rsync)
        ->and($rsync->ignoreTimes())->toBe($rsync)
        ->and($rsync->sizeOnly())->toBe($rsync)
        ->and($rsync->update())->toBe($rsync)
        ->and($rsync->pruneEmptyDirs())->toBe($rsync)
        ->and($rsync->backup())->toBe($rsync)
        ->and($rsync->links())->toBe($rsync)
        ->and($rsync->copyLinks())->toBe($rsync)
        ->and($rsync->copyUnsafeLinks())->toBe($rsync)
        ->and($rsync->safeLinks())->toBe($rsync)
        ->and($rsync->hardLinks())->toBe($rsync)
        ->and($rsync->dryRun())->toBe($rsync)
        ->and($rsync->force())->toBe($rsync)
        ->and($rsync->removeSourceFiles())->toBe($rsync)
        ->and($rsync->verbose())->toBe($rsync)
        ->and($rsync->quiet())->toBe($rsync)
        ->and($rsync->progress())->toBe($rsync)
        ->and($rsync->stats())->toBe($rsync)
        ->and($rsync->itemizeChanges())->toBe($rsync)
        ->and($rsync->humanReadable())->toBe($rsync)
        ->and($rsync->deleteBefore())->toBe($rsync)
        ->and($rsync->deleteAfter())->toBe($rsync)
        ->and($rsync->deleteExcluded())->toBe($rsync);
});

it('returns self from option methods', function (): void {
    $rsync = new Rsync();

    expect($rsync->exclude('*.log'))->toBe($rsync)
        ->and($rsync->excludeFrom('/file'))->toBe($rsync)
        ->and($rsync->excludeDir('dir'))->toBe($rsync)
        ->and($rsync->include('*.php'))->toBe($rsync)
        ->and($rsync->includeFrom('/file'))->toBe($rsync)
        ->and($rsync->backupDir('/dir'))->toBe($rsync)
        ->and($rsync->suffix('.bak'))->toBe($rsync)
        ->and($rsync->maxSize('10M'))->toBe($rsync)
        ->and($rsync->minSize('1K'))->toBe($rsync);
});

// ─── Checksum Behavior Tests ─────────────────────────────────────

it('syncs files with different content but same mtime and size when checksum is enabled', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Source content');

    // Create dest file with same size and touch to same mtime
    file_put_contents($this->destDir.'/file.txt', 'Dest content!');
    touch($this->destDir.'/file.txt', filemtime($this->sourceDir.'/file.txt'));

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_get_contents($this->destDir.'/file.txt'))->toBe('Source content');
});

it('skips files with same content but different mtime when checksum is enabled', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Same content');

    // Create dest file with same content but different mtime and size
    file_put_contents($this->destDir.'/file.txt', 'Same content');
    touch($this->destDir.'/file.txt', filemtime($this->sourceDir.'/file.txt') + 100);

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(1);
});

it('syncs files with different content when checksum is enabled regardless of matching size', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'AA');

    // Create dest file with same size but different content
    file_put_contents($this->destDir.'/file.txt', 'BB');
    touch($this->destDir.'/file.txt', filemtime($this->sourceDir.'/file.txt'));

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_get_contents($this->destDir.'/file.txt'))->toBe('AA');
});

it('skips files with same content but different mtime and size when checksum is enabled', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Same');

    // Create dest file with different size and mtime but same content
    file_put_contents($this->destDir.'/file.txt', 'Same');
    clearstatcache();
    $sourceMtime = filemtime($this->sourceDir.'/file.txt');
    touch($this->destDir.'/file.txt', $sourceMtime + 100);

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(1);
});

it('reports checksum-based sync in dry-run mode', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Source content');

    // Create dest file with same size but different content
    file_put_contents($this->destDir.'/file.txt', 'Dest content!');
    touch($this->destDir.'/file.txt', filemtime($this->sourceDir.'/file.txt'));

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->dryRun()
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_get_contents($this->destDir.'/file.txt'))->toBe('Dest content!');
});

it('does not sync files with same content in dry-run when checksum is enabled', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Same content');

    // Create dest file with different mtime but same content
    file_put_contents($this->destDir.'/file.txt', 'Same content');
    touch($this->destDir.'/file.txt', filemtime($this->sourceDir.'/file.txt') + 100);

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->checksum()
        ->dryRun()
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(1);
});

it('getFlags returns the flags collection', function (): void {
    $rsync = new Rsync()
        ->delete()
        ->recursive();

    $flags = $rsync->getFlags();

    expect($flags->count())->toBe(2)
        ->and($flags->contains('--delete'))->toBeTrue()
        ->and($flags->contains('--recursive'))->toBeTrue();
});

it('getOptions returns the options collection', function (): void {
    $rsync = new Rsync()
        ->exclude('*.log');

    $options = $rsync->getOptions();

    expect($options->count())->toBe(1)
        ->and($options->has('exclude'))->toBeTrue();
});

it('getExcludes returns the excludes collection', function (): void {
    $rsync = new Rsync();
    $excludes = $rsync->getExcludes();

    expect($excludes->count())->toBe(0);
});

it('exclude called twice merges patterns', function (): void {
    $rsync = new Rsync()
        ->exclude('*.log')
        ->exclude('*.tmp');

    $options = $rsync->getOptions();
    $exclude = $options->get('exclude');

    expect($exclude->values)->toBe(['*.log', '*.tmp']);
});

it('excludeDir called twice merges patterns', function (): void {
    $rsync = new Rsync()
        ->excludeDir('vendor')
        ->excludeDir('node_modules');

    $options = $rsync->getOptions();
    $excludeDir = $options->get('exclude-dir');

    expect($excludeDir->values)->toBe(['vendor', 'node_modules']);
});

it('include called twice merges patterns', function (): void {
    $rsync = new Rsync()
        ->include('*.php')
        ->include('*.json');

    $options = $rsync->getOptions();
    $include = $options->get('include');

    expect($include->values)->toBe(['*.php', '*.json']);
});
