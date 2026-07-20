<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

final readonly class RealSyncOperation implements SyncOperationInterface
{
    public function __construct(
        private ?Output $output = null,
    ) {}

    public function copyFile(string $from, string $to): bool
    {
        $directory = dirname($to);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        return copy($from, $to);
    }

    public function deleteFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    public function notifyCopied(FileInfo $file): void
    {
        $this->output?->copied($file);
    }

    public function notifyDeleted(FileInfo $file): void
    {
        $this->output?->deleted($file);
    }

    public function notifySkipped(FileInfo $file): void
    {
        $this->output?->skipped($file);
    }
}
