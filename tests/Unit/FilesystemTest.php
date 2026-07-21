<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\InMemoryFilesystem;
use DiegoVasconcelos\Rsync\LocalFilesystem;

beforeEach(function (): void {
    $this->root = str_replace('\\', '/', base_tests_dir('/filesystem_test_'.uniqid()));
    mkdir($this->root, recursive: true);
});

afterEach(function (): void {
    deleteTestDirectory($this->root);
});

// ─── LocalFilesystem ─────────────────────────────────────────────

it('LocalFilesystem: reports existence for files and missing paths', function (): void {
    $fs = new LocalFilesystem();
    $file = $this->root.'/a.txt';
    file_put_contents($file, 'x');

    expect($fs->exists($file))->toBeTrue()
        ->and($fs->exists($this->root.'/missing'))->toBeFalse();
});

it('LocalFilesystem: classifies files and directories', function (): void {
    $fs = new LocalFilesystem();
    $file = $this->root.'/a.txt';
    $dir = $this->root.'/sub';
    file_put_contents($file, 'x');
    mkdir($dir);

    expect($fs->isFile($file))->toBeTrue()
        ->and($fs->isFile($dir))->toBeFalse()
        ->and($fs->isDir($dir))->toBeTrue()
        ->and($fs->isDir($file))->toBeFalse();
});

it('LocalFilesystem: reports readability', function (): void {
    $fs = new LocalFilesystem();
    $file = $this->root.'/a.txt';
    file_put_contents($file, 'x');

    expect($fs->isReadable($file))->toBeTrue()
        ->and($fs->isReadable($this->root.'/missing'))->toBeFalse();
});

it('LocalFilesystem: creates directories, copies, deletes', function (): void {
    $fs = new LocalFilesystem();
    $nested = $this->root.'/a/b';
    $src = $this->root.'/src.txt';
    file_put_contents($src, 'content');

    $fs->mkdir($nested);
    expect(is_dir($nested))->toBeTrue();

    $dest = $nested.'/copy.txt';
    expect($fs->copy($src, $dest))->toBeTrue()
        ->and(file_get_contents($dest))->toBe('content');

    expect($fs->deleteFile($dest))->toBeTrue()
        ->and(file_exists($dest))->toBeFalse()
        ->and($fs->deleteFile($dest))->toBeFalse();

    expect($fs->removeDir($nested))->toBeTrue()
        ->and(is_dir($nested))->toBeFalse();
});

it('LocalFilesystem: reads size, mtime and hash', function (): void {
    $fs = new LocalFilesystem();
    $file = $this->root.'/a.txt';
    file_put_contents($file, 'hello');

    expect($fs->size($file))->toBe(5)
        ->and($fs->mtime($file))->toBeInt()
        ->and($fs->hash($file))->toBe(hash('xxh128', 'hello'))
        ->and($fs->size($this->root.'/missing'))->toBe(0)
        ->and($fs->mtime($this->root.'/missing'))->toBe(0)
        ->and($fs->hash($this->root.'/missing'))->toBe('');
});

it('LocalFilesystem: detects empty vs non-empty directories', function (): void {
    $fs = new LocalFilesystem();
    $empty = $this->root.'/empty';
    $full = $this->root.'/full';
    mkdir($empty);
    mkdir($full);
    file_put_contents($full.'/a.txt', 'x');

    expect($fs->isEmptyDirectory($empty))->toBeTrue()
        ->and($fs->isEmptyDirectory($full))->toBeFalse()
        ->and($fs->isEmptyDirectory($this->root.'/missing'))->toBeFalse();
});

it('LocalFilesystem: scans files recursively', function (): void {
    $fs = new LocalFilesystem();
    file_put_contents($this->root.'/a.txt', 'a');
    mkdir($this->root.'/sub');
    file_put_contents($this->root.'/sub/b.txt', 'b');

    $files = iterator_to_array($fs->scanFiles($this->root), false);

    expect($files)->toContain($this->root.'/a.txt')
        ->and($files)->toContain($this->root.'/sub/b.txt');

    expect(iterator_to_array($fs->scanFiles($this->root.'/missing'), false))->toBe([]);
});

it('LocalFilesystem: scans entries deep-first', function (): void {
    $fs = new LocalFilesystem();
    mkdir($this->root.'/outer/inner', recursive: true);
    file_put_contents($this->root.'/outer/inner/file.txt', 'x');

    $entries = iterator_to_array($fs->scanEntriesDeepFirst($this->root), false);

    // Inner entries must appear before their parents.
    expect($entries)->toContain($this->root.'/outer/inner')
        ->and($entries)->toContain($this->root.'/outer/inner/file.txt');

    $innerPos = array_search($this->root.'/outer/inner', $entries, true);
    $outerPos = array_search($this->root.'/outer', $entries, true);
    expect($innerPos)->toBeLessThan($outerPos);

    expect(iterator_to_array($fs->scanEntriesDeepFirst($this->root.'/missing'), false))->toBe([]);
});

// ─── InMemoryFilesystem ──────────────────────────────────────────

it('InMemoryFilesystem: tracks files and implicit directories', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/root/a.txt', 'x');
    $fs->put('/root/sub/b.txt', 'y');

    expect($fs->exists('/root/a.txt'))->toBeTrue()
        ->and($fs->exists('/root/missing'))->toBeFalse()
        ->and($fs->isFile('/root/a.txt'))->toBeTrue()
        ->and($fs->isFile('/root/sub'))->toBeFalse()
        ->and($fs->isDir('/root/sub'))->toBeTrue()
        ->and($fs->isDir('/root/missing'))->toBeFalse()
        ->and($fs->isReadable('/root/a.txt'))->toBeTrue();
});

it('InMemoryFilesystem: isDir returns false for empty path', function (): void {
    $fs = new InMemoryFilesystem();

    expect($fs->isDir(''))->toBeFalse();
});

it('InMemoryFilesystem: copies, deletes and removes directories', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/a.txt', 'content');
    $fs->put('/src/sub/b.txt', 'b');

    expect($fs->copy('/src/a.txt', '/dst/a.txt'))->toBeTrue()
        ->and($fs->copy('/src/missing', '/dst/x'))->toBeFalse()
        ->and($fs->isFile('/dst/a.txt'))->toBeTrue();

    expect($fs->deleteFile('/dst/a.txt'))->toBeTrue()
        ->and($fs->deleteFile('/dst/a.txt'))->toBeFalse();

    expect($fs->removeDir('/src/sub'))->toBeTrue()
        ->and($fs->exists('/src/sub/b.txt'))->toBeFalse()
        ->and($fs->removeDir('/src/sub'))->toBeFalse();
});

it('InMemoryFilesystem: reads size, mtime and hash', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/a.txt', 'hello', 1000);

    expect($fs->size('/a.txt'))->toBe(5)
        ->and($fs->mtime('/a.txt'))->toBe(1000)
        ->and($fs->hash('/a.txt'))->toBe(hash('xxh128', 'hello'))
        ->and($fs->size('/missing'))->toBe(0)
        ->and($fs->mtime('/missing'))->toBe(0)
        ->and($fs->hash('/missing'))->toBe('');

    $fs->setMtime('/a.txt', 2000);
    expect($fs->mtime('/a.txt'))->toBe(2000);
});

it('InMemoryFilesystem: mkdir is a no-op and there are no empty directories', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/a/b.txt', 'x');

    $fs->mkdir('/a/new'); // no-op, must not create an empty directory

    expect($fs->isEmptyDirectory('/a/new'))->toBeFalse();
});

it('InMemoryFilesystem: scans files and entries deep-first', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/root/a.txt', 'a');
    $fs->put('/root/sub/b.txt', 'b');
    $fs->put('/root/sub/deep/c.txt', 'c');
    $fs->put('/elsewhere/ignored.txt', 'z'); // outside the scanned root

    $files = iterator_to_array($fs->scanFiles('/root'), false);
    expect($files)->toBe(['/root/a.txt', '/root/sub/b.txt', '/root/sub/deep/c.txt']);

    expect(iterator_to_array($fs->scanFiles('/empty'), false))->toBe([]);

    $entries = iterator_to_array($fs->scanEntriesDeepFirst('/root'), false);

    $deepPos = array_search('/root/sub/deep', $entries, true);
    $subPos = array_search('/root/sub', $entries, true);
    expect($deepPos)->toBeLessThan($subPos);
});
