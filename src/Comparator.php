<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * @internal Decides whether a source file should be synced over its
 * destination counterpart based on mtime/size or checksum.
 */
final class Comparator
{
    /**
     * Return true when the source file must be (re)copied.
     */
    public function shouldSync(FileInfo $source, FileInfo $destination, bool $useChecksum = false): bool
    {
        if ($useChecksum) {
            return $source->checksum !== $destination->checksum;
        }

        return $source->mtime !== $destination->mtime
            || $source->size !== $destination->size;
    }
}
