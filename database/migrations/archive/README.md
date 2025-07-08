# Migration Archive Directory

This directory contains archived migration files that are excluded from `php artisan migrate:fresh` and other migration commands.

## Purpose

- **Historical preservation**: Keep old migrations for reference
- **Clean migration runs**: Prevent legacy migrations from running on fresh installations
- **Rollback safety**: Migrations can be restored if needed
- **Documentation**: Maintain history of database schema changes

## How It Works

Laravel's migration system only looks for `.php` files in the `database/migrations` directory. Files in subdirectories like `archive/` are ignored by default.

## Usage

### Archive Migrations

```bash
# Archive all migrations before a specific date
php artisan migrations:archive --before=2025-06-20

# Archive migrations matching a pattern
php artisan migrations:archive --pattern="old_feature"

# Dry run to see what would be archived
php artisan migrations:archive --before=2025-06-20 --dry-run
```

### Restore Migrations

```bash
# Restore all archived migrations
php artisan migrations:archive --restore

# Dry run to see what would be restored
php artisan migrations:archive --restore --dry-run
```

## Best Practices

1. **Always test first**: Use `--dry-run` to preview changes
2. **Backup before archiving**: Keep a copy of your migration files
3. **Test migrate:fresh**: Run on a copy of your database after archiving
4. **Document significant changes**: Update this README when archiving major features
5. **Keep consolidation marker**: Don't archive the consolidation marker migration

## Suggested Archiving Strategy

### Phase 1: Archive Pre-Consolidation Migrations
```bash
# Archive everything before the consolidation marker
php artisan migrations:archive --before=2025-06-20
```

### Phase 2: Archive Feature-Specific Migrations
```bash
# Archive specific feature migrations that are no longer needed
php artisan migrations:archive --pattern="old_feature_name"
```

### Phase 3: Use Consolidated Migrations
- Move to using the consolidated migrations in `database/migrations/consolidated/`
- Keep current migrations active for development
- Archive only when absolutely sure they're no longer needed

## What Gets Archived

Typically archive:
- ✅ Old feature migrations that are no longer relevant
- ✅ Experimental migrations that didn't work out
- ✅ Duplicate migrations that were consolidated
- ✅ Migrations before major refactors (like the consolidation marker)

Don't archive:
- ❌ Current active migrations
- ❌ Core system migrations that are still needed
- ❌ Recent migrations that might need rollback
- ❌ The consolidation marker migration

## Recovery

If you need to restore archived migrations:

1. Use the restore command: `php artisan migrations:archive --restore`
2. Or manually move files from `archive/` back to `migrations/`
3. Run `php artisan migrate:status` to check migration status

## File Structure

```
database/migrations/
├── archive/                    # Archived migrations (ignored by Laravel)
│   ├── 2025_01_01_old_feature.php
│   └── 2025_02_01_deprecated.php
├── consolidated/               # Clean consolidated migrations
│   ├── 2025_07_08_create_users_table.php
│   └── 2025_07_08_create_products_table.php
└── 2025_06_20_999999_consolidation_marker.php  # Current active migrations
```

## Notes

- Archived migrations are completely ignored by Laravel
- They won't appear in `php artisan migrate:status`
- They won't run with `php artisan migrate:fresh`
- You can still manually examine them for reference
- Git history is preserved for all migrations