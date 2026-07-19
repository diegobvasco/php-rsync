<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\Output;
use DiegoVasconcelos\Rsync\Rsync;

beforeEach(function (): void {
    $this->sourceDir = base_tests_dir('/deploy_sync_test_source_'.uniqid());
    $this->destDir = base_tests_dir('/deploy_sync_test_dest_'.uniqid());

    mkdir($this->sourceDir, recursive: true);
    mkdir($this->destDir, recursive: true);
});

afterEach(function (): void {
    deleteTestDirectory($this->sourceDir);
    deleteTestDirectory($this->destDir);
});

it('calls output copied when files are synced', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');

    $output = new class() implements Output
    {
        /** @var list<string> */
        public array $actions = [];

        public function copied(FileInfo $file): void
        {
            $this->actions[] = 'copied:'.$file->relativePath;
        }

        public function deleted(FileInfo $file): void
        {
            $this->actions[] = 'deleted:'.$file->relativePath;
        }

        public function skipped(FileInfo $file): void
        {
            $this->actions[] = 'skipped:'.$file->relativePath;
        }
    };

    new Rsync($output)
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($output->actions)->toBe(['copied:file.txt']);
});

it('calls output deleted when files are removed', function (): void {
    file_put_contents($this->destDir.'/old.txt', 'Old');

    $output = new class() implements Output
    {
        /** @var list<string> */
        public array $actions = [];

        public function copied(FileInfo $file): void
        {
            $this->actions[] = 'copied:'.$file->relativePath;
        }

        public function deleted(FileInfo $file): void
        {
            $this->actions[] = 'deleted:'.$file->relativePath;
        }

        public function skipped(FileInfo $file): void
        {
            $this->actions[] = 'skipped:'.$file->relativePath;
        }
    };

    new Rsync($output)
        ->copy($this->sourceDir, $this->destDir)
        ->delete()
        ->run();

    expect($output->actions)->toBe(['deleted:old.txt']);
});

it('calls output skipped when files already exist and are unchanged', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');

    new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    $output = new class() implements Output
    {
        /** @var list<string> */
        public array $actions = [];

        public function copied(FileInfo $file): void
        {
            $this->actions[] = 'copied:'.$file->relativePath;
        }

        public function deleted(FileInfo $file): void
        {
            $this->actions[] = 'deleted:'.$file->relativePath;
        }

        public function skipped(FileInfo $file): void
        {
            $this->actions[] = 'skipped:'.$file->relativePath;
        }
    };

    new Rsync($output)
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($output->actions)->toBe(['skipped:file.txt']);
});

it('works without output interface', function (): void {
    file_put_contents($this->sourceDir.'/file.txt', 'Content');

    $result = new Rsync()
        ->copy($this->sourceDir, $this->destDir)
        ->run();

    expect($result->copiedCount())->toBe(1);
});
