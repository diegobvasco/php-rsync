<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Concerns\DirectoryCleanup;
use DiegoVasconcelos\Rsync\Filesystem;
use DiegoVasconcelos\Rsync\LocalFilesystem;

function make_cleanup_fixture(Filesystem $fs): object
{
    return new class($fs)
    {
        use DirectoryCleanup;

        public ?string $destination = null;

        public function __construct(private readonly Filesystem $filesystem) {}

        public function exposeCleanup(): void
        {
            $this->cleanupEmptyDirectories();
        }

        protected function filesystem(): Filesystem
        {
            return $this->filesystem;
        }
    };
}

it('cleanup is a no-op when destination is unset', function (): void {
    $fixture = make_cleanup_fixture(new LocalFilesystem());
    $fixture->destination = null;

    $fixture->exposeCleanup(); // Expect no exception.

    expect(true)->toBeTrue();
});

it('cleanup is a no-op when destination does not exist', function (): void {
    $fixture = make_cleanup_fixture(new LocalFilesystem());
    $fixture->destination = base_tests_dir('/does_not_exist_'.uniqid());

    $fixture->exposeCleanup();

    expect(is_dir($fixture->destination))->toBeFalse();
});

it('removes empty directories through the filesystem', function (): void {
    $fs = new LocalFilesystem();
    $fixture = make_cleanup_fixture($fs);

    $root = base_tests_dir('/dc_cleanup_'.uniqid());
    $emptySubdir = $root.'/empty';
    $fs->mkdir($emptySubdir);

    // The destination is $root; the empty subdir beneath it should be removed.
    $fixture->destination = $root;
    $fixture->exposeCleanup();

    expect(is_dir($emptySubdir))->toBeFalse();

    deleteTestDirectory($root);
});
