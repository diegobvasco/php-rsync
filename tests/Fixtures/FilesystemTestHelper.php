<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DiegoVasconcelos\Rsync\Concerns\HasFilesystem;

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
}
