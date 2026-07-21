<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use Closure;
use DiegoVasconcelos\Rsync\Concerns\ByteFormatter;

/**
 * File metadata captured during a scan.
 *
 * The checksum is resolved lazily (and memoized) through a property hook,
 * so the expensive xxh128 hashing only runs when checksum comparison is
 * actually required (--checksum mode), instead of for every scanned file.
 */
final class FileInfo
{
    use ByteFormatter;

    /** @var string|null Memoized checksum, null until first resolution. */
    private ?string $resolvedChecksum = null;

    /** @param  (Closure(): string)|null  $checksumProvider  Deferred checksum computation; null resolves to ''. */
    public function __construct(
        public readonly string $relativePath,
        public readonly string $absolutePath,
        public readonly int $size,
        public readonly int $mtime,
        private readonly ?Closure $checksumProvider = null,
    ) {}

    /** Content checksum (xxh128), resolved on first access and cached. */
    public string $checksum {
        get => $this->resolvedChecksum ??= $this->checksumProvider instanceof Closure
            ? ($this->checksumProvider)()
            : '';
    }

    /** Format file size in human readable format. */
    public function formattedSize(): string
    {
        return self::formatBytes($this->size);
    }

    /** Format modification time as ISO 8601. */
    public function formattedMtime(): string
    {
        return date('c', $this->mtime);
    }
}
