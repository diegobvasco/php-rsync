<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DiegoVasconcelos\Rsync\Concerns\HasFilesystem;
use DiegoVasconcelos\Rsync\FileInfo;

class FilesystemTestHelper
{
    use HasFilesystem;

    public function doScanAllFiles(string $path): array
    {
        return $this->scanAllFiles($path);
    }

    public function doDeleteFile(string $path): bool
    {
        return $this->deleteFile($path);
    }

    public function doGlobToRegex(string $pattern): string
    {
        return $this->globToRegex($pattern);
    }

    public function doShouldSync(FileInfo $source, FileInfo $destination, bool $useChecksum = false): bool
    {
        return $this->shouldSync($source, $destination, $useChecksum);
    }
}
