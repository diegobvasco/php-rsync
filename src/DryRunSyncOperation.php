<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use Override;

final class DryRunSyncOperation implements SyncOperationInterface
{
    #[Override]
    public function copyFile(string $from, string $to): bool
    {
        return true;
    }

    #[Override]
    public function deleteFile(string $path): bool
    {
        return true;
    }

    #[Override]
    public function notifyCopied(FileInfo $file): void {}

    #[Override]
    public function notifyDeleted(FileInfo $file): void {}

    #[Override]
    public function notifySkipped(FileInfo $file): void {}
}
