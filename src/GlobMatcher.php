<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * @internal Matches paths against glob-style exclusion patterns.
 *
 * Supports: `*` / `**` (any characters including `/`), `?` (single char),
 * `[class]` / `[^class]` (character classes), and trailing `/` (directory).
 */
final class GlobMatcher
{
    /** @var array<string, string> Cached compiled regexes, keyed by pattern. */
    private static array $regexCache = [];

    /**
     * Clear the compiled-pattern cache (primarily useful for tests).
     */
    public static function clearCache(): void
    {
        self::$regexCache = [];
    }

    /**
     * Check if a path matches any of the exclusion patterns.
     *
     * @param  list<string>  $patterns
     */
    public function matches(string $path, array $patterns): bool
    {
        $normalizedPath = str_replace('\\', '/', $path);

        foreach ($patterns as $pattern) {
            $normalizedPattern = str_replace('\\', '/', $pattern);

            // Exact match.
            if ($normalizedPath === $normalizedPattern) {
                return true;
            }

            // Directory pattern match (pattern ends with /).
            if (str_ends_with($normalizedPattern, '/')) {
                $dirPattern = rtrim($normalizedPattern, '/');

                if (str_starts_with($normalizedPath, $dirPattern.'/') || $normalizedPath === $dirPattern) {
                    return true;
                }
            }

            // Glob pattern match.
            if ($this->globMatch($normalizedPattern, $normalizedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match a string against a glob pattern using regex.
     */
    public function globMatch(string $pattern, string $subject): bool
    {
        return preg_match($this->globToRegex($pattern), $subject) === 1;
    }

    /**
     * Convert a glob pattern to a regex pattern (memoized).
     */
    public function globToRegex(string $pattern): string
    {
        return self::$regexCache[$pattern] ??= $this->compile($pattern);
    }

    private function compile(string $pattern): string
    {
        $regex = '';
        $length = strlen($pattern);
        $i = 0;

        while ($i < $length) {
            $char = $pattern[$i];

            if ($char === '*' && isset($pattern[$i + 1]) && $pattern[$i + 1] === '*') {
                // ** matches anything including /.
                $regex .= '.*';
                $i += 2;

                // Handle trailing **/ (matches any directory prefix).
                if ($i < $length && $pattern[$i] === '/') {
                    $regex .= '(?:\/|$)';
                    $i++;
                }
            } elseif ($char === '*') {
                // * matches anything including / for recursive directory matching.
                $regex .= '.*';
                $i++;
            } elseif ($char === '?') {
                $regex .= '.';
                $i++;
            } elseif ($char === '/') {
                $regex .= '\\/';
                $i++;
            } elseif ($char === '.') {
                $regex .= '\\.';
                $i++;
            } elseif ($char === '[') {
                $regex .= '[';
                $i++;

                if ($i < $length && $pattern[$i] === '^') {
                    $regex .= '^';
                    $i++;
                }

                while ($i < $length && $pattern[$i] !== ']') {
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
