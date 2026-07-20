<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DiegoVasconcelos\Rsync\Concerns\FileOperations;
use DiegoVasconcelos\Rsync\Concerns\FileScanner;
use DiegoVasconcelos\Rsync\Concerns\GlobMatcher;
use DiegoVasconcelos\Rsync\FileInfo;

class FilesystemTestHelper
{
    use FileOperations;
    use FileScanner;
    use GlobMatcher;

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

    public function doCopyFile(string $from, string $to): bool
    {
        return $this->copyFile($from, $to);
    }

    public function doShouldSync(FileInfo $source, FileInfo $destination, bool $useChecksum = false): bool
    {
        return $this->shouldSync($source, $destination, $useChecksum);
    }
}
