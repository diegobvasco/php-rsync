<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Engine;

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Filesystem\Filesystem;
use DiegoVasconcelos\Rsync\Filesystem\LocalFilesystem;
use DiegoVasconcelos\Rsync\Output\Output;
use Override;

final readonly class RealSyncOperation implements SyncOperationInterface
{
    public function __construct(
        private ?Output $output = null,
        private Filesystem $filesystem = new LocalFilesystem(),
    ) {}

    #[Override]
    public function copyFile(string $from, string $to): bool
    {
        $fs = $this->filesystem;
        $directory = dirname($to);

        if (! $fs->isDir($directory)) {
            $fs->mkdir($directory);
        }

        return $fs->copy($from, $to);
    }

    #[Override]
    public function deleteFile(string $path): bool
    {
        $fs = $this->filesystem;

        if (! $fs->isFile($path)) {
            return false;
        }

        return $fs->deleteFile($path);
    }

    #[Override]
    public function notifyCopied(FileInfo $file): void
    {
        $this->output?->copied($file);
    }

    #[Override]
    public function notifyDeleted(FileInfo $file): void
    {
        $this->output?->deleted($file);
    }

    #[Override]
    public function notifySkipped(FileInfo $file): void
    {
        $this->output?->skipped($file);
    }
}
