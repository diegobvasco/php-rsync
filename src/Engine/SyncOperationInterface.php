<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Engine;

use DiegoVasconcelos\Rsync\FileInfo;

interface SyncOperationInterface
{
    public function copyFile(string $from, string $to): bool;

    public function deleteFile(string $path): bool;

    public function notifyCopied(FileInfo $file): void;

    public function notifyDeleted(FileInfo $file): void;

    public function notifySkipped(FileInfo $file): void;
}
