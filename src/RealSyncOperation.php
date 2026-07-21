<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

final readonly class RealSyncOperation implements SyncOperationInterface
{
    public function __construct(
        private ?Output $output = null,
        private Filesystem $filesystem = new LocalFilesystem(),
    ) {}

    public function copyFile(string $from, string $to): bool
    {
        $fs = $this->filesystem;
        $directory = dirname($to);

        if (! $fs->isDir($directory)) {
            $fs->mkdir($directory);
        }

        return $fs->copy($from, $to);
    }

    public function deleteFile(string $path): bool
    {
        $fs = $this->filesystem;

        if (! $fs->isFile($path)) {
            return false;
        }

        return $fs->deleteFile($path);
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
