<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Rsync;
use Tests\Fixtures\FilesystemTestHelper;

beforeEach(function (): void {
    $this->helper = new FilesystemTestHelper();
    $this->sourceDir = base_tests_dir('/deploy_sync_fs_test_source_'.uniqid());
    $this->destDir = base_tests_dir('/deploy_sync_fs_test_dest_'.uniqid());
});

afterEach(function (): void {
    deleteFilesystemTestDirectory($this->sourceDir);
    deleteFilesystemTestDirectory($this->destDir);
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
    deleteFilesystemTestDirectory($nonexistentDest);
});

it('globToRegex handles slash inside character class', function (): void {
    $result = $this->helper->doGlobToRegex('[a/b].txt');
    expect($result)->toBe('/^[a\\/b]\\.txt$/');
});

it('globToRegex handles negated character class with slash', function (): void {
    $result = $this->helper->doGlobToRegex('[^a/b].txt');
    expect($result)->toBe('/^[^a\\/b]\\.txt$/');
});
