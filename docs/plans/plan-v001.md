# Plan v001

## Automatic Consumable Name Synchronization Plan

**Goal**: Make consumable names automatically stay up-to-date with master catalog and cultivar data

### Phase 1: Model Updates
1. **Modify Consumable model** (`app/Models/Consumable.php`):
   - Override `getNameAttribute()` to compute names for seed consumables from master catalog + cultivar
   - Keep stored names for non-seed consumables
   - Add relationship integrity checks

2. **Add Model Observers** (`app/Observers/`):
   - Create `MasterSeedCatalogObserver` to handle common_name changes
   - Create `MasterCultivarObserver` to handle cultivar_name changes
   - Register observers to automatically trigger consumable name updates

### Phase 2: Database Migration
1. **Create migration** to:
   - Make `name` field nullable for seed consumables (will be computed)
   - Clear existing seed consumable names so they use computed values
   - Keep names required for non-seed types

### Phase 3: UI Updates
1. **Update ConsumableTable** (`app/Filament/Resources/ConsumableResource/Tables/ConsumableTable.php`):
   - Ensure name column uses the accessor (should work automatically)
   - Verify search functionality works with computed names
   - Add `masterCultivar` to eager loading (minimal performance impact)

2. **Update ConsumableForm** (already mostly implemented):
   - Keep readonly name field for seeds showing computed value
   - Ensure form validation works with computed names

### Phase 4: Testing & Data Integrity
1. **Create artisan command** to verify and fix any data inconsistencies
2. **Add tests** for the name computation logic
3. **Test search and filtering** with computed names

**Performance Impact**: Minimal - just 1 additional eager-loaded relationship query.

Created: 2025-01-24T23:21:34Z