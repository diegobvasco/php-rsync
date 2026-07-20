<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\RealSyncOperation;

beforeEach(function (): void {
    $this->sourceDir = base_tests_dir('/deploy_sync_real_test_source_'.uniqid());
    $this->destDir = base_tests_dir('/deploy_sync_real_test_dest_'.uniqid());

    mkdir($this->sourceDir, recursive: true);
    mkdir($this->destDir, recursive: true);
});

afterEach(function (): void {
    deleteTestDirectory($this->sourceDir);
    deleteTestDirectory($this->destDir);
});

it('copyFile creates destination directory and copies file', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'content');

    $dest = $this->destDir.'/nested/deep/file.txt';
    $operation = new RealSyncOperation();

    $result = $operation->copyFile($this->sourceDir.'/file.txt', $dest);

    expect($result)->toBeTrue()
        ->and(file_get_contents($dest))->toBe('content');
});

it('deleteFile returns false for directory path', function (): void {
    mkdir($this->destDir.'/a_dir', recursive: true);

    $operation = new RealSyncOperation();
    $result = $operation->deleteFile($this->destDir.'/a_dir');

    expect($result)->toBeFalse()
        ->and(is_dir($this->destDir.'/a_dir'))->toBeTrue();
});

it('deleteFile returns true for existing file', function (): void {
    file_put_contents($this->destDir.'/file.txt', 'content');

    $operation = new RealSyncOperation();
    $result = $operation->deleteFile($this->destDir.'/file.txt');

    expect($result)->toBeTrue()
        ->and(file_exists($this->destDir.'/file.txt'))->toBeFalse();
});

it('deleteFile returns false for non-existent path', function (): void {
    $operation = new RealSyncOperation();
    $result = $operation->deleteFile('/nonexistent/file.txt');

    expect($result)->toBeFalse();
});
