<?php

declare(strict_types=1);

use DiegoVasconcelos\Rsync\GlobMatcher;

beforeEach(function (): void {
    $this->matcher = new GlobMatcher();
    GlobMatcher::clearCache();
});

it('returns false when there are no patterns', function (): void {
    expect($this->matcher->matches('a/b.txt', []))->toBeFalse();
});

it('matches an exact path', function (): void {
    expect($this->matcher->matches('skip.log', ['skip.log']))->toBeTrue()
        ->and($this->matcher->matches('keep.txt', ['skip.log']))->toBeFalse();
});

it('matches a simple wildcard pattern', function (): void {
    expect($this->matcher->matches('error.log', ['*.log']))->toBeTrue()
        ->and($this->matcher->matches('error.txt', ['*.log']))->toBeFalse();
});

it('matches a recursive directory pattern', function (): void {
    expect($this->matcher->matches('vendor/pkg/file.php', ['vendor/*']))->toBeTrue();
});

it('matches a double-star pattern with trailing slash', function (): void {
    expect($this->matcher->matches('logs/deep/nested/x.log', ['**/']))->toBeTrue();
});

it('matches a single-char wildcard', function (): void {
    expect($this->matcher->matches('ab.txt', ['??.txt']))->toBeTrue()
        ->and($this->matcher->matches('abc.txt', ['??.txt']))->toBeFalse();
});

it('matches a character class', function (): void {
    expect($this->matcher->matches('a.txt', ['[abc].txt']))->toBeTrue()
        ->and($this->matcher->matches('d.txt', ['[abc].txt']))->toBeFalse();
});

it('matches a negated character class', function (): void {
    expect($this->matcher->matches('c.log', ['[^ab].log']))->toBeTrue()
        ->and($this->matcher->matches('a.log', ['[^ab].log']))->toBeFalse();
});

it('matches a directory pattern (trailing slash)', function (): void {
    expect($this->matcher->matches('logs/x.txt', ['logs/']))->toBeTrue()
        ->and($this->matcher->matches('logsconfig/x.txt', ['logs/']))->toBeFalse();
});

it('normalizes backslashes before matching', function (): void {
    expect($this->matcher->matches('a\\b.log', ['*.log']))->toBeTrue();
});

it('caches compiled regexes', function (): void {
    $this->matcher->globToRegex('*.log');
    $reflection = new ReflectionClass(GlobMatcher::class);
    $cache = $reflection->getStaticProperties()['regexCache'];

    expect($cache)->toHaveKey('*.log');
});

it('globMatch wraps globToRegex', function (): void {
    expect($this->matcher->globMatch('*.log', 'error.log'))->toBeTrue();
});

it('handles slashes inside a character class', function (): void {
    expect($this->matcher->globToRegex('[a/b].txt'))->toBe('/^[a\\/b]\\.txt$/');
});

it('handles negated class with a slash', function (): void {
    expect($this->matcher->globToRegex('[^a/b].txt'))->toBe('/^[^a\\/b]\\.txt$/');
});

it('clearCache empties the cache', function (): void {
    $this->matcher->globToRegex('*.log');
    GlobMatcher::clearCache();

    $reflection = new ReflectionClass(GlobMatcher::class);
    $cache = $reflection->getStaticProperties()['regexCache'];

    expect($cache)->toBe([]);
});
