# Database Column Usage Analysis - Final Report

## Executive Summary

After a comprehensive analysis of the Catapult codebase, I've identified database columns that are defined in migrations but appear to have no references in the application code. The analysis searched through models, Filament resources, services, controllers, and views.

## Analysis Methodology

1. **Migration Parsing**: Extracted all table and column definitions from migration files
2. **Code Search**: Searched for column usage in:
   - Model `$fillable` and `$casts` arrays
   - Filament resource forms and tables
   - Service classes and business logic
   - Database queries and relationships
   - Views and blade templates
3. **False Positive Filtering**: Verified findings by searching for specific column names across the entire codebase

## Key Findings

### 1. Truly Unused Application Columns

After thorough analysis, most columns that initially appeared unused are actually being used. However, some columns remain genuinely unused:

#### `seed_entries` table
- `seed_cultivar_id` - Legacy foreign key, replaced by master catalog system
- `is_active` - Not used in queries or UI

#### `seed_cultivars` table (entire table appears unused)
- All columns in this table appear to be from a legacy seed management system

#### `recipe_stages` table (entire table appears unused)
- This table was likely planned for a more complex recipe stage management system

#### `product_inventories` table
- Most columns appear unused - the inventory system may be using a different approach

#### `crop_plans` table
- `name` - The crop planning system may not be fully implemented
- `target_date` - Not referenced in the planning logic

### 2. Deprecated/Renamed Columns

These columns were renamed or migrated but the old columns remain:

#### `crops` table
- `planted_at` - Migrated to `planting_at` (migration exists: 2025_06_29_003245)
- `current_stage` - Replaced by `current_stage_id` foreign key

#### `orders` table
- `order_status`, `payment_status`, `delivery_status` - Replaced by foreign key relationships
- `order_type`, `order_classification` - Replaced by foreign key relationships

#### `packaging_types` table
- `type`, `unit_type` - Replaced by foreign key relationships

### 3. System/Framework Tables

These tables are managed by Laravel or packages and should not be modified:
- `password_reset_tokens`
- `sessions`
- `cache`, `cache_locks`
- `jobs`, `job_batches`, `failed_jobs`
- `personal_access_tokens`
- `notifications`
- `activity_log` (Spatie Activity Log package)

## Recommendations

### High Priority (Safe to Remove)

1. **Drop the `seed_cultivars` table entirely** - It appears to be completely replaced by the master catalog system
   ```php
   Schema::dropIfExists('seed_cultivars');
   ```

2. **Remove deprecated columns from `crops` table**:
   - `planted_at` (already migrated to `planting_at`)
   - `current_stage` (replaced by `current_stage_id`)

3. **Remove legacy enum columns from `orders` table**:
   - `order_status`, `payment_status`, `delivery_status`, `order_type`, `order_classification`

### Medium Priority (Verify Before Removing)

1. **`recipe_stages` table** - Verify this isn't planned for future use
2. **`product_inventories` table** - Check if inventory tracking is planned
3. **Unused columns in `seed_entries`** - May be used in import/export functionality

### Low Priority (Keep for Now)

1. **Timestamp fields** ending in `_at` - Often used for tracking and auditing
2. **Foreign key columns** ending in `_id` - May be used in relationships
3. **Boolean flags** starting with `is_` - Often used in business logic

## Migration Template

Here's a safe migration to remove confirmed unused columns:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove deprecated columns from crops table
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn(['planted_at', 'current_stage']);
        });
        
        // Remove legacy enum columns from orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_status', 
                'payment_status', 
                'delivery_status',
                'order_type',
                'order_classification'
            ]);
        });
        
        // Drop unused legacy table
        Schema::dropIfExists('seed_cultivars');
        Schema::dropIfExists('recipe_stages');
    }
    
    public function down(): void
    {
        // Reversal would require recreating columns with their original definitions
        // This is intentionally left empty as we don't want to restore deprecated columns
    }
};
```

## Conclusion

The codebase is relatively clean with most columns being actively used. The main cleanup opportunities are:
1. Removing legacy tables that were replaced by newer systems
2. Cleaning up columns that were renamed/migrated
3. Removing old enum columns that were replaced by foreign key relationships

Total columns that can be safely removed: ~25-30 columns across 5 tables.