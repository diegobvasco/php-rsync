<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\Rsync;

it('can create Rsync instance', function (): void {
    $sync = new Rsync();

    expect($sync)->toBeInstanceOf(Rsync::class);
});

it('can configure copy with fluent API', function (): void {
    $sync = new Rsync();

    $result = $sync
        ->copy('/tmp/source', '/tmp/dest')
        ->skip('*.log');

    expect($result)->toBeInstanceOf(Rsync::class);
});
