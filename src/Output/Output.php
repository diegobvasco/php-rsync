<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync\Output;

use DiegoVasconcelos\Rsync\FileInfo;

interface Output
{
    public function copied(FileInfo $file): void;

    public function deleted(FileInfo $file): void;

    public function skipped(FileInfo $file): void;
}
