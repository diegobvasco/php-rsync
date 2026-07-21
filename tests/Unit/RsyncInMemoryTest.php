<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\InMemoryFilesystem;
use DiegoVasconcelos\Rsync\Rsync;

it('syncs entirely in memory without touching the real filesystem', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/app.php', '<?php echo 1;');
    $fs->put('/src/logs/error.log', 'error');
    $fs->put('/src/logs/debug.log', 'debug');

    $result = new Rsync(null, $fs)
        ->copy('/src', '/dest')
        ->skip('*.log')
        ->run();

    expect($result->copiedPaths())->toBe(['app.php'])
        ->and($result->skippedPaths())->toBe(['logs/debug.log', 'logs/error.log'])
        ->and($result->copiedCount())->toBe(1)
        ->and($fs->isFile('/dest/app.php'))->toBeTrue()
        ->and($fs->exists('/dest/logs/error.log'))->toBeFalse();
});

it('supports dry-run in memory without mutating destination', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/new.txt', 'x');
    $fs->put('/dest/old.txt', 'old');

    $result = new Rsync(null, $fs)
        ->copy('/src', '/dest')
        ->delete()
        ->dryRun()
        ->run();

    expect($result->copiedPaths())->toBe(['new.txt'])
        ->and($result->deletedPaths())->toBe(['old.txt'])
        ->and($fs->exists('/dest/old.txt'))->toBeTrue()  // Not actually deleted.
        ->and($fs->exists('/dest/new.txt'))->toBeFalse(); // Not actually copied.
});

it('skips unchanged in-memory files by mtime and size', function (): void {
    $fs = new InMemoryFilesystem();
    $fs->put('/src/file.txt', 'same', 1000);
    $fs->put('/dest/file.txt', 'same', 1000);

    $result = new Rsync(null, $fs)
        ->copy('/src', '/dest')
        ->run();

    expect($result->copiedCount())->toBe(0)
        ->and($result->skippedCount())->toBe(1);
});
