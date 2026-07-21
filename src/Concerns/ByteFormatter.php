<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

trait ByteFormatter
{
    private const int KIB = 1024;

    /** @var list<string> */
    private const array UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * Format bytes to human readable size.
     */
    public static function formatBytes(int $bytes): string
    {
        $value = (float) $bytes;
        $i = 0;

        while ($value >= self::KIB && $i < count(self::UNITS) - 1) {
            $value /= self::KIB;
            $i++;
        }

        return round($value, 2).' '.self::UNITS[$i];
    }
}
