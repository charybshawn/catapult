# Recipe Variety Migration Plan

## Overview
This document outlines the comprehensive migration from the deprecated seedEntry system to the new master_seed_catalog and master_cultivar relationships.

## Database Structure

### New Relationships
- `recipes.master_seed_catalog_id` → `master_seed_catalog.id`
- `recipes.master_cultivar_id` → `master_cultivars.id`

### Migration Strategy
1. ✅ Created migration to add foreign key relationships
2. ✅ Migrated existing data to populate relationships
3. ✅ Created RecipeVarietyService to handle variety information consistently

## Code Updates Required

### 1. Dashboard Updates
Replace all instances of:
- `->with(['recipe.seedEntry'])` → `->with(['recipe.masterSeedCatalog', 'recipe.masterCultivar'])`
- `$crop->recipe->seedEntry->cultivar_name` → `$this->varietyService->getCultivarName($crop->recipe)`
- `$crop->recipe->seedEntry->common_name` → `$this->varietyService->getCommonName($crop->recipe)`
- `$recipe->seedEntry->common_name` → `$this->varietyService->getCommonName($recipe)`

### 2. Resource Updates
- ✅ RecipeResource: Updated to use master_seed_catalog_id and master_cultivar_id selects
- ✅ CropPlanResource: Removed seedEntry eager loading
- TODO: CropResource, ConsumableResource, OrderResource, etc.

### 3. Service Updates
- ✅ HarvestYieldCalculator: Updated to match by common_name/cultivar_name
- ✅ CropPlanningService: Updated to use common_name/cultivar_name
- ✅ Created RecipeVarietyService for consistent variety information access

### 4. Model Updates
- ✅ Recipe: Added masterSeedCatalog() and masterCultivar() relationships
- ✅ Recipe: Removed seedEntry() and soilSupplier() relationships
- ✅ Crop: Updated getVarietyNameAttribute() to use recipe's cultivar_name

## Implementation Steps

1. **Phase 1: Core Infrastructure** ✅
   - Database migration
   - Model relationships
   - Service layer

2. **Phase 2: Update All References** (In Progress)
   - Dashboard
   - All Filament Resources
   - All Services
   - All Views/Blade files

3. **Phase 3: Cleanup**
   - Remove SeedEntry model and related files
   - Remove seed_entries table
   - Update tests
   - Update documentation

## Usage Examples

### Old Way:
```php
$varietyName = $crop->recipe->seedEntry->cultivar_name;
$commonName = $crop->recipe->seedEntry->common_name;
```

### New Way:
```php
// Using the service
$varietyName = $this->varietyService->getCultivarName($crop->recipe);
$commonName = $this->varietyService->getCommonName($crop->recipe);
$fullName = $this->varietyService->getFullVarietyName($crop->recipe);

// Or using relationships directly
$varietyName = $crop->recipe->masterCultivar->cultivar_name;
$commonName = $crop->recipe->masterSeedCatalog->common_name;
```

## Files to Update

### High Priority (Breaking the system):
1. `/app/Filament/Pages/Dashboard.php` - Multiple seedEntry references
2. `/app/Filament/Resources/CropResource.php` - seedEntry in queries
3. `/app/Filament/Resources/ConsumableResource.php` - seedEntry references

### Medium Priority:
4. All other Filament Resources
5. Services that reference seedEntry
6. Console commands

### Low Priority:
7. Tests
8. Documentation
9. Cleanup deprecated files