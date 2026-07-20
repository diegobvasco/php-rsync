<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

final class TerminalOutput implements Output
{
    /** @var resource */
    private mixed $stream;

    /**
     * @param  resource|null  $stream
     */
    public function __construct(mixed $stream = null)
    {
        $this->stream = $stream ?? STDOUT;
    }

    public function copied(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("COPY %s (%s)\n", $file->relativePath, $file->formattedSize()));
    }

    public function deleted(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("DELETE %s\n", $file->relativePath));
    }

    public function skipped(FileInfo $file): void
    {
        fwrite($this->stream, sprintf("SKIP %s\n", $file->relativePath));
    }
}
