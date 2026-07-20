<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

final class DryRunSyncOperation implements SyncOperationInterface
{
    public function copyFile(string $from, string $to): bool
    {
        return true;
    }

    public function deleteFile(string $path): bool
    {
        return true;
    }

    public function notifyCopied(FileInfo $file): void {}

    public function notifyDeleted(FileInfo $file): void {}

    public function notifySkipped(FileInfo $file): void {}
}
