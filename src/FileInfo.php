<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

final readonly class FileInfo
{
    public function __construct(
        public string $relativePath,
        public string $absolutePath,
        public int $size,
        public int $mtime,
        public string $checksum = '',
    ) {}

    /**
     * Format file size in human readable format.
     */
    public function formattedSize(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Format modification time as ISO 8601.
     */
    public function formattedMtime(): string
    {
        return date('c', $this->mtime);
    }
}
