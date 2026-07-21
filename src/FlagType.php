<?php

declare(strict_types=1);

namespace DiegoVasconcelos\Rsync;

/**
 * Native enum representing known rsync flags.
 */
enum FlagType: string
{
    case DELETE = '--delete';

    case RECURSIVE = '--recursive';

    case ARCHIVE = '--archive';

    case TIMES = '--times';

    case PERMS = '--perms';

    case OWNER = '--owner';

    case GROUP = '--group';

    case ACLS = '--acls';

    case XTRAS = '--xattrs';

    case DEVICES = '--devices';

    case SPECIALS = '--specials';

    case NUMERIC_IDS = '--numeric-ids';

    case CHECKSUM = '--checksum';

    case IGNORE_TIMES = '--ignore-times';

    case SIZE_ONLY = '--size-only';

    case UPDATE = '--update';

    case PRUNE_EMPTY_DIRS = '--prune-empty-dirs';

    case BACKUP = '--backup';

    case LINKS = '--links';

    case COPY_LINKS = '--copy-links';

    case COPY_UNSAFE_LINKS = '--copy-unsafe-links';

    case SAFE_LINKS = '--safe-links';

    case HARD_LINKS = '--hard-links';

    case DRY_RUN = '--dry-run';

    case FORCE = '--force';

    case REMOVE_SOURCE_FILES = '--remove-source-files';

    case VERBOSE = '--verbose';

    case QUIET = '--quiet';

    case PROGRESS = '--progress';

    case STATS = '--stats';

    case ITEMIZE_CHANGES = '--itemize-changes';

    case HUMAN_READABLE = '--human-readable';

    case DELETE_BEFORE = '--delete-before';

    case DELETE_AFTER = '--delete-after';

    case DELETE_EXCLUDED = '--delete-excluded';
}
