<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\FileInfo;
use DiegoVasconcelos\Rsync\TerminalOutput;

it('outputs copied file action', function (): void {
    $stream = fopen('php://memory', 'r+');
    $output = new TerminalOutput($stream);
    $file = new FileInfo(relativePath: 'src/app.php', absolutePath: '/tmp/src/app.php', size: 1024, mtime: time());

    $output->copied($file);

    rewind($stream);
    $result = stream_get_contents($stream);
    fclose($stream);

    expect($result)->toContain('COPY src/app.php')
        ->and($result)->toContain('1 KB');
});

it('outputs deleted file action', function (): void {
    $stream = fopen('php://memory', 'r+');
    $output = new TerminalOutput($stream);
    $file = new FileInfo(relativePath: 'old_file.txt', absolutePath: '/tmp/old_file.txt', size: 512, mtime: time());

    $output->deleted($file);

    rewind($stream);
    $result = stream_get_contents($stream);
    fclose($stream);

    expect($result)->toBe("DELETE old_file.txt\n");
});

it('outputs skipped file action', function (): void {
    $stream = fopen('php://memory', 'r+');
    $output = new TerminalOutput($stream);
    $file = new FileInfo(relativePath: 'debug.log', absolutePath: '/tmp/debug.log', size: 256, mtime: time());

    $output->skipped($file);

    rewind($stream);
    $result = stream_get_contents($stream);
    fclose($stream);

    expect($result)->toBe("SKIP debug.log\n");
});
