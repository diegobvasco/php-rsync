<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\FileScanner;
use DiegoVasconcelos\Rsync\GlobMatcher;
use DiegoVasconcelos\Rsync\InMemoryFilesystem;
use DiegoVasconcelos\Rsync\LocalFilesystem;

it('scan returns an empty map for a missing directory', function (): void {
    $scanner = new FileScanner(new LocalFilesystem(), new GlobMatcher());

    expect($scanner->scan('/nonexistent/path'))->toBe([]);
});

it('scan reads files recursively via LocalFilesystem', function (): void {
    $root = base_tests_dir('/scanner_test_'.uniqid());
    mkdir($root.'/sub', recursive: true);
    file_put_contents($root.'/a.txt', 'a');
    file_put_contents($root.'/sub/b.txt', 'bb');

    $scanner = new FileScanner(new LocalFilesystem(), new GlobMatcher());
    $files = $scanner->scan($root);

    expect($files)->toHaveKeys(['a.txt', 'sub/b.txt'])
        ->and($files['a.txt'])->toBeInstanceOf(FileInfo::class)
        ->and($files['a.txt']->size)->toBe(1)
        ->and($files['sub/b.txt']->size)->toBe(2);

    deleteTestDirectory($root);
});

it('scan reads files from InMemoryFilesystem', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/a.txt', 'hello');
    $fs->put('/src/sub/b.txt', 'world!');

    $scanner = new FileScanner($fs, new GlobMatcher());
    $files = $scanner->scan('/src');

    expect($files)->toHaveKeys(['a.txt', 'sub/b.txt'])
        ->and($files['a.txt']->size)->toBe(5);
});

it('partition splits files by exclusion patterns', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/keep.php', 'x');
    $fs->put('/src/error.log', 'y');
    $fs->put('/src/sub/debug.log', 'z');

    $scanner = new FileScanner($fs, new GlobMatcher());
    $files = $scanner->scan('/src');

    ['included' => $included, 'excluded' => $excluded] = $scanner->partition($files, ['*.log']);

    expect(array_keys($included))->toBe(['keep.php'])
        ->and(array_keys($excluded))->toBe(['error.log', 'sub/debug.log']);
});

it('partition returns everything as included when no patterns given', function (): void {
    $scanner = new FileScanner(new InMemoryFilesystem(), new GlobMatcher());

    $files = ['a.txt' => new FileInfo('a.txt', '/a.txt', 1, 1)];

    ['included' => $included, 'excluded' => $excluded] = $scanner->partition($files, []);

    expect(array_keys($included))->toBe(['a.txt'])
        ->and($excluded)->toBe([]);
});
