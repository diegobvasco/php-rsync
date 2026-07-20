<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

trait ByteFormatter
{
    /**
     * Format bytes to human readable size.
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
