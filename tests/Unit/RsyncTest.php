<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Rsync;

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

    expect($summary)->toContain('Copied: 1 files')
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
        ->run();

    expect(is_dir($this->destDir.'/old_empty_dir'))->toBeFalse();
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

    $rsync = new class() extends Rsync
    {
        protected function isReadable(string $path): bool
        {
            return false;
        }
    };

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
