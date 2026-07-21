<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

use Override;

final readonly class TerminalOutput implements Output
{
    /** @var resource */
    private mixed $stream;

    /** @param  resource|null  $stream */
    public function __construct(mixed $stream = null)
    {
        $this->stream = $stream ?? STDOUT;
    }

    #[Override]
    public function copied(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("COPY %s (%s)\n", $file->relativePath, $file->formattedSize()));
    }

    #[Override]
    public function deleted(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("DELETE %s\n", $file->relativePath));
    }

    #[Override]
    public function skipped(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("SKIP %s\n", $file->relativePath));
    }
}
