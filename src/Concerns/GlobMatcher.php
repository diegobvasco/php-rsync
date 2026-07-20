<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Concerns;

trait GlobMatcher
{
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
