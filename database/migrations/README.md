# Consolidated Migrations

This directory contains consolidated migrations that replace the original 139 migration files with a more manageable set of migrations organized by table/feature.

## Migration Structure

1. **2025_06_20_000001_create_users_table.php** - User authentication and session tables
2. **2025_06_20_000002_create_cache_table.php** - Cache system tables
3. **2025_06_20_000003_create_jobs_table.php** - Queue and job management tables
4. **2025_06_20_000004_create_permission_tables.php** - Spatie permissions system
5. **2025_06_20_000005_create_suppliers_table.php** - Supplier management
6. **2025_06_20_000006_create_seed_catalog_tables.php** - Complete seed catalog system
7. **2025_06_20_000007_create_recipes_tables.php** - Recipe and growing configuration
8. **2025_06_20_000008_create_inventory_tables.php** - Consumables and packaging inventory
9. **2025_06_20_000009_create_orders_tables.php** - Order, invoice, and payment system
10. **2025_06_20_000010_create_products_tables.php** - Product catalog and pricing
11. **2025_06_20_000011_create_crops_tables.php** - Crop tracking and harvests
12. **2025_06_20_000012_create_system_tables.php** - System utilities and notifications
13. **2025_06_20_000013_create_product_inventory_tables.php** - Advanced inventory tracking
14. **2025_06_20_999999_consolidation_marker.php** - Marks old migrations as run

## Usage

### For New Installations

1. Delete or move the old migrations from `database/migrations/`
2. Move these consolidated migrations to `database/migrations/consolidated/`
3. Run `php artisan migrate`

### For Existing Installations

1. Ensure all migrations are up to date by running `php artisan migrate`
2. Back up your database
3. Move the consolidated migrations to a temporary location
4. Clear the migrations directory
5. Move the consolidated migrations to `database/migrations/`
6. The consolidation marker will ensure old migrations are marked as run

## Benefits

- Reduced migration count from 139 to 14 files
- Logical grouping of related tables
- Easier to understand database structure
- Faster migration runs on fresh installations
- Cleaner migration history

## Notes

- These migrations represent the final state after all 139 original migrations
- All indexes, foreign keys, and constraints are preserved
- Views and stored columns are included
- Data migrations are handled in the consolidation marker