<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * In-memory Filesystem implementation for tests and dry-run scenarios.
 *
 * Directories are implicit: a directory "exists" iff at least one file is
 * located beneath it. Consequently there are no empty directories in this
 * implementation, which is consistent with the sync semantics (cleanup of
 * empty directories only has observable effects on a real filesystem).
 */
final class InMemoryFilesystem implements Filesystem
{
    /** @var array<string, array{content: string, mtime: int}> */
    private array $files = [];

    /**
     * Seed a file with contents and (optionally) a modification time.
     */
    public function put(string $path, string $content, ?int $mtime = null): void
    {
        $path = $this->normalize($path);
        $this->files[$path] = ['content' => $content, 'mtime' => $mtime ?? time()];
    }

    /**
     * Override the modification time of an existing file.
     */
    public function setMtime(string $path, int $mtime): void
    {
        $path = $this->normalize($path);

        if (isset($this->files[$path])) {
            $this->files[$path]['mtime'] = $mtime;
        }
    }

    private function normalize(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }

    #[\Override]
    public function exists(string $path): bool
    {
        $path = $this->normalize($path);

        return isset($this->files[$path]) || $this->isDir($path);
    }

    #[\Override]
    public function isFile(string $path): bool
    {
        return isset($this->files[$this->normalize($path)]);
    }

    #[\Override]
    public function isDir(string $path): bool
    {
        $path = $this->normalize($path);

        if ($path === '') {
            return false;
        }

        $prefix = $path.'/';

        return array_any(array_keys($this->files), fn (string $file): bool => str_starts_with($file, $prefix));
    }

    #[\Override]
    public function isReadable(string $path): bool
    {
        return $this->exists($path);
    }

    #[\Override]
    public function mkdir(string $path): void
    {
        // Directories are implicit; nothing to do.
    }

    #[\Override]
    public function copy(string $from, string $to): bool
    {
        $from = $this->normalize($from);
        $to = $this->normalize($to);

        if (! isset($this->files[$from])) {
            return false;
        }

        $this->files[$to] = $this->files[$from];

        return true;
    }

    #[\Override]
    public function deleteFile(string $path): bool
    {
        $path = $this->normalize($path);

        if (! isset($this->files[$path])) {
            return false;
        }

        unset($this->files[$path]);

        return true;
    }

    #[\Override]
    public function removeDir(string $path): bool
    {
        $path = $this->normalize($path);

        if (! $this->isDir($path)) {
            return false;
        }

        $prefix = $path.'/';

        foreach (array_keys($this->files) as $file) {
            if (str_starts_with($file, $prefix)) {
                unset($this->files[$file]);
            }
        }

        return true;
    }

    #[\Override]
    public function size(string $path): int
    {
        $path = $this->normalize($path);

        return isset($this->files[$path]) ? strlen($this->files[$path]['content']) : 0;
    }

    #[\Override]
    public function mtime(string $path): int
    {
        $path = $this->normalize($path);

        return $this->files[$path]['mtime'] ?? 0;
    }

    #[\Override]
    public function hash(string $path): string
    {
        $path = $this->normalize($path);

        return isset($this->files[$path]) ? hash('xxh128', $this->files[$path]['content']) : '';
    }

    #[\Override]
    public function isEmptyDirectory(string $path): bool
    {
        // Directories are implicit, so a directory only exists while it has
        // files beneath it. By definition there are no empty directories.
        return false;
    }

    #[\Override]
    public function scanFiles(string $path): iterable
    {
        $path = $this->normalize($path);
        $prefix = $path.'/';

        $matches = [];

        foreach (array_keys($this->files) as $file) {
            if (str_starts_with($file, $prefix)) {
                $matches[] = $file;
            }
        }

        sort($matches);

        foreach ($matches as $file) {
            yield $file;
        }
    }

    #[\Override]
    public function scanEntriesDeepFirst(string $path): iterable
    {
        $path = $this->normalize($path);
        $prefix = $path.'/';

        $entries = [];
        $dirs = [];

        foreach (array_keys($this->files) as $file) {
            if (! str_starts_with($file, $prefix)) {
                continue;
            }

            $entries[] = $file;

            $relative = substr($file, strlen($prefix));
            $accumulator = $path;

            $segments = array_filter(
                explode('/', dirname($relative)),
                static fn (string $segment): bool => $segment !== '' && $segment !== '.',
            );

            foreach ($segments as $segment) {
                $accumulator .= '/'.$segment;
                $dirs[$accumulator] = true;
            }
        }

        $all = [...$entries, ...array_keys($dirs)];

        usort($all, static fn (string $a, string $b): int => substr_count($b, '/') <=> substr_count($a, '/'));

        foreach ($all as $entry) {
            yield $entry;
        }
    }
}
