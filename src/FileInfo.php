<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use DiegoVasconcelos\Rsync\Concerns\ByteFormatter;

final readonly class FileInfo
{
    use ByteFormatter;

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
        return self::formatBytes($this->size);
    }

    /**
     * Format modification time as ISO 8601.
     */
    public function formattedMtime(): string
    {
        return date('c', $this->mtime);
    }
}
