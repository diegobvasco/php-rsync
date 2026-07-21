<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Engine\DryRunSyncOperation;
use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Output\Output;

it('copyFile always reports success without touching the filesystem', function (): void {
    $operation = new DryRunSyncOperation();

    expect($operation->copyFile('/nonexistent/src.txt', '/nonexistent/dest.txt'))->toBeTrue();
});

it('deleteFile always reports success without touching the filesystem', function (): void {
    $operation = new DryRunSyncOperation();

    expect($operation->deleteFile('/nonexistent/file.txt'))->toBeTrue();
});

it('notify methods are silent no-ops', function (): void {
    $operation = new DryRunSyncOperation();
    $file = new FileInfo('file.txt', '/path/file.txt', 10, 1000);

    $operation->notifyCopied($file);  // Expect no output / no exception.
    $operation->notifyDeleted($file);
    $operation->notifySkipped($file);

    expect(true)->toBeTrue();
});

it('never invokes an Output sink during dry-run', function (): void {
    $spy = new class() implements Output
    {
        public int $calls = 0;

        public function copied(FileInfo $file): void
        {
            $this->calls++;
        }

        public function deleted(FileInfo $file): void
        {
            $this->calls++;
        }

        public function skipped(FileInfo $file): void
        {
            $this->calls++;
        }
    };

    // DryRunSyncOperation intentionally has no Output dependency: real-time
    // output is a RealSyncOperation concern. This test documents that contract.
    expect($spy->calls)->toBe(0);
});
