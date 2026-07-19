<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

use DiegoVasconcelos\Rsync\FileInfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait HasFilesystem
{
    /**
     * Recursively scan a directory and return all FileInfo objects (unfiltered).
     *
     * @return array<string, FileInfo>
     */
    protected function scanAllFiles(string $path): array
    {
        $files = [];

        if (! is_dir($path)) {
            return $files;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relativePath = ltrim(substr($file->getPathname(), strlen($path)), DIRECTORY_SEPARATOR);

            $files[$relativePath] = new FileInfo(
                relativePath: $relativePath,
                absolutePath: $file->getPathname(),
                size: $file->getSize(),
                mtime: $file->getMTime(),
                checksum: hash_file('xxh128', $file->getPathname()) ?: '',
            );
        }

        return $files;
    }

    /**
     * Filter files, separating included from excluded based on patterns.
     *
     * @param  array<string, FileInfo>  $files
     * @param  array<string>  $excludes
     * @return array{included: array<string, FileInfo>, excluded: array<string, FileInfo>}
     */
    protected function filterByExclusions(array $files, array $excludes): array
    {
        $included = [];
        $excluded = [];

        foreach ($files as $relativePath => $file) {
            if ($this->matchesExclusion($relativePath, $excludes)) {
                $excluded[$relativePath] = $file;
            } else {
                $included[$relativePath] = $file;
            }
        }

        return ['included' => $included, 'excluded' => $excluded];
    }

    /**
     * Copy a single file from source to destination.
     */
    protected function copyFile(string $from, string $to): bool
    {
        $directory = dirname($to);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        return copy($from, $to);
    }

    /**
     * Check if a file should be synced based on modification time, size, or checksum.
     */
    protected function shouldSync(FileInfo $source, FileInfo $destination, bool $useChecksum = false): bool
    {
        if ($useChecksum) {
            return $source->checksum !== $destination->checksum;
        }

        return $source->mtime !== $destination->mtime
            || $source->size !== $destination->size;
    }

    /**
     * Delete a single file.
     */
    protected function deleteFile(string $path): bool
    {
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Check if a path matches any of the exclusion patterns.
     *
     * @param  array<string>  $patterns
     */
    protected function matchesExclusion(string $path, array $patterns): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        foreach ($patterns as $pattern) {
            $normalizedPattern = str_replace('\\', '/', $pattern);

            // Exact match
            if ($normalizedPath === $normalizedPattern) {
                return true;
            }

            // Directory pattern match (pattern ends with /)
            if (str_ends_with($normalizedPattern, '/')) {
                $dirPattern = rtrim($normalizedPattern, '/');
                if (str_starts_with($normalizedPath, $dirPattern.'/') || $normalizedPath === $dirPattern) {
                    return true;
                }
            }

            // Glob pattern match
            if ($this->globMatch($normalizedPattern, $normalizedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a string against a glob pattern using regex.
     *
     * Supports: * (recursive), ** (recursive), ? (single char), [class] (character class)
     */
    protected function globMatch(string $pattern, string $string): bool
    {
        $regex = $this->globToRegex($pattern);

        return (bool) preg_match($regex, $string);
    }

    /**
     * Convert a glob pattern to a regex pattern.
     */
    protected function globToRegex(string $pattern): string
    {
        $regex = '';
        $length = strlen($pattern);
        $i = 0;

        while ($i < $length) {
            $char = $pattern[$i];

            if ($char === '*' && isset($pattern[$i + 1]) && $pattern[$i + 1] === '*') {
                // ** matches anything including /
                $regex .= '.*';
                $i += 2;

                // Handle trailing **/ (matches any directory prefix)
                if ($i < $length && $pattern[$i] === '/') {
                    $regex .= '(?:\/|$)';
                    $i++;
                }
            } elseif ($char === '*') {
                // * matches anything including / for recursive directory matching
                $regex .= '.*';
                $i++;
            } elseif ($char === '?') {
                // ? matches a single char
                $regex .= '.';
                $i++;
            } elseif ($char === '/') {
                // Escape / for use with / delimiter
                $regex .= '\\/';
                $i++;
            } elseif ($char === '.') {
                $regex .= '\\.';
                $i++;
            } elseif ($char === '[') {
                // Character class - copy until ]
                $regex .= '[';
                $i++;
                if ($i < $length && $pattern[$i] === '^') {
                    $regex .= '^';
                    $i++;
                }

                while ($i < $length && $pattern[$i] !== ']') {
                    // Escape / inside character classes for use with / delimiter
                    if ($pattern[$i] === '/') {
                        $regex .= '\\/';
                    } else {
                        $regex .= $pattern[$i];
                    }

                    $i++;
                }

                if ($i < $length) {
                    $regex .= ']';
                    $i++;
                }
            } else {
                $regex .= preg_quote($char, '/');
                $i++;
            }
        }

        return '/^'.$regex.'$/';
    }
}
