<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Rsync;
use Tests\Fixtures\FilesystemTestHelper;

beforeEach(function (): void {
    $this->helper = new FilesystemTestHelper();
    $this->sourceDir = base_tests_dir('/deploy_sync_fs_test_source_'.uniqid());
    $this->destDir = base_tests_dir('/deploy_sync_fs_test_dest_'.uniqid());
});

afterEach(function (): void {
    deleteTestDirectory($this->sourceDir);
    deleteTestDirectory($this->destDir);
});

it('scanAllFiles returns empty array for non-existent directory', function (): void {
    $result = $this->helper->doScanAllFiles('/nonexistent/path/that/does/not/exist');
    expect($result)->toBe([]);
});

it('scanAllFiles returns files for existing directory', function (): void {
    mkdir($this->sourceDir, recursive: true);
    file_put_contents($this->sourceDir.'/file.txt', 'content');

    $result = $this->helper->doScanAllFiles($this->sourceDir);
    expect($result)->toHaveKeys(['file.txt']);
});

it('deleteFile returns false for directory path', function (): void {
    mkdir($this->destDir.'/a_dir', recursive: true);

    $result = $this->helper->doDeleteFile($this->destDir.'/a_dir');
    expect($result)->toBeFalse();
    expect(is_dir($this->destDir.'/a_dir'))->toBeTrue();
});

it('deleteFile returns true for existing file', function (): void {
    mkdir($this->destDir, recursive: true);
    file_put_contents($this->destDir.'/file.txt', 'content');

    $result = $this->helper->doDeleteFile($this->destDir.'/file.txt');
    expect($result)->toBeTrue();
    expect(file_exists($this->destDir.'/file.txt'))->toBeFalse();
});

it('deleteFile returns false for non-existent path', function (): void {
    $result = $this->helper->doDeleteFile('/nonexistent/file.txt');
    expect($result)->toBeFalse();
});

it('copyFile copies file to destination', function (): void {
    mkdir($this->sourceDir, recursive: true);
    mkdir($this->destDir, recursive: true);
    file_put_contents($this->sourceDir.'/file.txt', 'content');

    $dest = $this->destDir.'/file.txt';

    $result = $this->helper->doCopyFile($this->sourceDir.'/file.txt', $dest);

    expect($result)->toBeTrue()
        ->and(file_get_contents($dest))->toBe('content');
});

it('copyFile creates missing parent directory', function (): void {
    mkdir($this->sourceDir, recursive: true);
    file_put_contents($this->sourceDir.'/file.txt', 'content');

    $dest = $this->destDir.'/sub/deep/file.txt';

    $result = $this->helper->doCopyFile($this->sourceDir.'/file.txt', $dest);

    expect($result)->toBeTrue()
        ->and(file_get_contents($dest))->toBe('content');
});

it('handles sync when destination directory does not exist yet', function (): void {
    mkdir($this->sourceDir, recursive: true);
    file_put_contents($this->sourceDir.'/file.txt', 'content');

    $nonexistentDest = $this->destDir.'/nonexistent';

    $result = new Rsync()
        ->copy($this->sourceDir, $nonexistentDest)
        ->run();

    expect($result->copiedCount())->toBe(1)
        ->and(file_get_contents($nonexistentDest.'/file.txt'))->toBe('content');

    // Cleanup
    deleteTestDirectory($nonexistentDest);
});

it('globToRegex handles slash inside character class', function (): void {
    $result = $this->helper->doGlobToRegex('[a/b].txt');
    expect($result)->toBe('/^[a\\/b]\\.txt$/');
});

it('globToRegex handles negated character class with slash', function (): void {
    $result = $this->helper->doGlobToRegex('[^a/b].txt');
    expect($result)->toBe('/^[^a\\/b]\\.txt$/');
});

it('shouldSync detects different content via checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 1000, 'hash_a');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 1000, 'hash_b');

    expect($this->helper->doShouldSync($source, $dest, useChecksum: true))->toBeTrue();
});

it('shouldSync skips identical content via checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 1000, 'same_hash');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 2000, 'same_hash');

    expect($this->helper->doShouldSync($source, $dest, useChecksum: true))->toBeFalse();
});

it('shouldSync ignores mtime difference when using checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 1000, 'same_hash');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 9999, 'same_hash');

    expect($this->helper->doShouldSync($source, $dest, useChecksum: true))->toBeFalse();
});

it('shouldSync detects size difference via checksum even with same mtime', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 1000, 'hash_a');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 1000, 'hash_b');

    expect($this->helper->doShouldSync($source, $dest, useChecksum: true))->toBeTrue();
});

it('shouldSync uses mtime and size by default without checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 1000, 'same_hash');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 1000, 'same_hash');

    expect($this->helper->doShouldSync($source, $dest))->toBeFalse();
});

it('shouldSync detects mtime change without checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 10, 2000, 'same_hash');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 1000, 'same_hash');

    expect($this->helper->doShouldSync($source, $dest))->toBeTrue();
});

it('shouldSync detects size change without checksum', function (): void {
    $source = new FileInfo('file.txt', '/src/file.txt', 20, 1000, 'same_hash');
    $dest = new FileInfo('file.txt', '/dest/file.txt', 10, 1000, 'same_hash');

    expect($this->helper->doShouldSync($source, $dest))->toBeTrue();
});
