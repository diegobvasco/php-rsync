<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Concerns\DirectoryCleanup;

function make_cleanup_fixture(): object
{
    return new class()
    {
        use DirectoryCleanup;

        public ?string $destination = null;

        public function exposeCleanup(): void
        {
            $this->cleanupEmptyDirectories();
        }

        public function exposeIsEmptyDirectory(string $path): bool
        {
            return $this->isEmptyDirectory($path);
        }
    };
}

it('cleanup is a no-op when destination is unset', function (): void {
    $fixture = make_cleanup_fixture();
    $fixture->destination = null;

    $fixture->exposeCleanup(); // Expect no exception.

    expect(true)->toBeTrue();
});

it('cleanup is a no-op when destination does not exist', function (): void {
    $fixture = make_cleanup_fixture();
    $fixture->destination = base_tests_dir('/does_not_exist_'.uniqid());

    $fixture->exposeCleanup();

    expect(is_dir($fixture->destination))->toBeFalse();
});

it('isEmptyDirectory returns false for a non-directory path', function (): void {
    $fixture = make_cleanup_fixture();

    $file = base_tests_dir('/dc_file_'.uniqid().'.txt');
    file_put_contents($file, 'x');

    expect($fixture->exposeIsEmptyDirectory($file))->toBeFalse()
        ->and($fixture->exposeIsEmptyDirectory('/nonexistent/path'))->toBeFalse();

    unlink($file);
});

it('isEmptyDirectory returns true for an empty directory and false for a non-empty one', function (): void {
    $fixture = make_cleanup_fixture();

    $empty = base_tests_dir('/dc_empty_'.uniqid());
    $nonEmpty = base_tests_dir('/dc_nonempty_'.uniqid());
    mkdir($empty, recursive: true);
    mkdir($nonEmpty, recursive: true);
    file_put_contents($nonEmpty.'/file.txt', 'x');

    expect($fixture->exposeIsEmptyDirectory($empty))->toBeTrue()
        ->and($fixture->exposeIsEmptyDirectory($nonEmpty))->toBeFalse();

    rmdir($empty);
    deleteTestDirectory($nonEmpty);
});
