<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\DirectoryCleaner;
use DiegoVasconcelos\Rsync\InMemoryFilesystem;
use DiegoVasconcelos\Rsync\LocalFilesystem;

it('cleanup is a no-op when destination is empty', function (): void {
    $cleaner = new DirectoryCleaner(new LocalFilesystem());

    $cleaner->cleanup(''); // Expect no exception.

    expect(true)->toBeTrue();
});

it('cleanup is a no-op when destination does not exist', function (): void {
    $cleaner = new DirectoryCleaner(new LocalFilesystem());
    $missing = base_tests_dir('/does_not_exist_'.uniqid());

    $cleaner->cleanup($missing);

    expect(is_dir($missing))->toBeFalse();
});

it('removes empty subdirectories from a real filesystem', function (): void {
    $fs = new LocalFilesystem();
    $cleaner = new DirectoryCleaner($fs);

    $root = base_tests_dir('/cleaner_test_'.uniqid());
    $emptySubdir = $root.'/empty';
    $nonEmpty = $root.'/full';
    $fs->mkdir($emptySubdir);
    $fs->mkdir($nonEmpty);
    file_put_contents($nonEmpty.'/keep.txt', 'x');

    $cleaner->cleanup($root);

    expect(is_dir($emptySubdir))->toBeFalse()
        ->and(is_dir($nonEmpty))->toBeTrue();

    deleteTestDirectory($root);
});

it('InMemoryFilesystem cleanup is a safe no-op', function (): void {
    $fs = new InMemoryFilesystem();
    $cleaner = new DirectoryCleaner($fs);

    $fs->put('/root/a.txt', 'x');

    $cleaner->cleanup('/root'); // No empty dirs exist in-memory.

    expect($fs->exists('/root/a.txt'))->toBeTrue();
});
