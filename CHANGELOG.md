# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [2.0.0](https://github.com/diegobvasco/php-rsync/compare/v1.4.0...v2.0.0) (2026-07-21)


### ⚠ BREAKING CHANGES

* all class FQCNs except Rsync, Result, and FileInfo have moved namespaces. Update `use` statements accordingly:

### Bug Fixes

* reorganize src/ into domain subnamespaces ([#12](https://github.com/diegobvasco/php-rsync/issues/12)) ([7fc5a05](https://github.com/diegobvasco/php-rsync/commit/7fc5a059b068eccca1393adccc6b34bc7363469c))

## [1.4.0](https://github.com/diegobvasco/php-rsync/compare/v1.3.0...v1.4.0) (2026-07-21)


### Features

* introduce FlagType native enum and refactor app to use it ([#9](https://github.com/diegobvasco/php-rsync/issues/9)) ([8503ca5](https://github.com/diegobvasco/php-rsync/commit/8503ca5cc22ff0d52348eea494e375c743c2e357))


### Bug Fixes

* dry-run FS mutation, shell escaping, type clarity and docs ([a064e1f](https://github.com/diegobvasco/php-rsync/commit/a064e1f0815bf3aa6b719f3a6b9f84db73720dfe))
* **lint:** remove conflicting not_operator rules from pint.json ([0f5197a](https://github.com/diegobvasco/php-rsync/commit/0f5197ac72081d410379537f3a9856ad1bc98e43))
* **test:** normalize FilesystemTest root path to forward slashes for Windows ([b1084c7](https://github.com/diegobvasco/php-rsync/commit/b1084c7e4ccedb459256b9814d48170620334317))
* use forward-slash as canonical path separator for cross-platform support ([aee52b3](https://github.com/diegobvasco/php-rsync/commit/aee52b393dfcfacc013ed692ba7d2f7caf1df84d))


### Performance Improvements

* lazy checksum and conditional destination scan ([1fc8a62](https://github.com/diegobvasco/php-rsync/commit/1fc8a62100597e58e2acf071037b861724edc7a6))


### Miscellaneous Chores

* **tooling:** consolidate pint rules, refresh schema, declare ext-hash ([33d58e4](https://github.com/diegobvasco/php-rsync/commit/33d58e4493c12433e62367d8363d577ffc9fb440))

## [1.3.0](https://github.com/diegobvasco/php-rsync/compare/v1.2.0...v1.3.0) (2026-07-20)


### Features

* value objects, SRP traits, strategy pattern & full test suite ([#7](https://github.com/diegobvasco/php-rsync/issues/7)) ([de09572](https://github.com/diegobvasco/php-rsync/commit/de09572dffb50dc75e2e2788b9dc1bdad90564fc))

## [1.2.0](https://github.com/diegobvasco/php-rsync/compare/v1.1.0...v1.2.0) (2026-07-19)


### Features

* implement --checksum flag for file comparison ([#5](https://github.com/diegobvasco/php-rsync/issues/5)) ([320f1f9](https://github.com/diegobvasco/php-rsync/commit/320f1f9bb90f56986421280387d7232e094bd1e0))

## [1.1.0](https://github.com/diegobvasco/php-rsync/compare/v1.0.0...v1.1.0) (2026-07-19)


### Features

* add fluent methods for all rsync flags ([#3](https://github.com/diegobvasco/php-rsync/issues/3)) ([dc0527b](https://github.com/diegobvasco/php-rsync/commit/dc0527b7adde5d3381fc2f5382c8b917c54e5d8b))

## 1.0.0 (2026-07-19)


### Features

* add Output interface and TerminalOutput ([#1](https://github.com/diegobvasco/php-rsync/issues/1)) ([87c5e08](https://github.com/diegobvasco/php-rsync/commit/87c5e08a98f08370c9dd5d1f9a8acaf147dbffb3))
* initial release of diegobvasco/php-rsync ([bd6c763](https://github.com/diegobvasco/php-rsync/commit/bd6c763ac0d3995fddc29354d64586034eac2873))


### Miscellaneous Chores

* update image source in README to use webp format ([bcc61c1](https://github.com/diegobvasco/php-rsync/commit/bcc61c1b23b7155faba576c12fe7b034a25e3b5e2))
