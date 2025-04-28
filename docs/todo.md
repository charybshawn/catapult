# TODO: Link SeedVariety Model with Consumable Model

## Background
The application originally used a SeedVariety model to track seed varieties for recipes. While we've added the more flexible Consumable model (where type='seed'), the SeedVariety model contains valuable reference data that should be accessible when managing seed consumables.

## Revised Objective
Instead of removing the SeedVariety model, establish a proper relationship between Consumable (seed type) and SeedVariety to leverage existing variety data when adding stock.

## Implementation Plan

### 1. Database Schema Changes
- Add a seed_variety_id foreign key to the consumables table (for type='seed' records)
- Create migration to establish this relationship

### 2. Model Relationships
- Update Consumable model to add seedVariety relationship:
  ```php
  public function seedVariety(): BelongsTo
  {
      return $this->belongsTo(SeedVariety::class);
  }
  ```
- Add inverse relationship in SeedVariety model:
  ```php
  public function consumables(): HasMany
  {
      return $this->hasMany(Consumable::class);
  }
  ```

### 3. Filament Resource Updates
- Modify ConsumableResource to show SeedVariety dropdown when type='seed':
  - Add conditional field in form that shows SeedVariety options
  - Make it searchable and preload data
  - Display variety details (germination rate, days to maturity) as helper text

- Update AdjustStock action to reference variety information:
  - Show variety details when selecting a seed consumable
  - Use this information for better inventory management

### 4. UI Improvements
- Add SeedVariety information to seed consumable displays:
  - Show variety name, crop type, and germination rate in seed consumable views
  - Include variety-specific information in stock management screens
  - Display variety details in consumable lists for seeds

### 5. Testing
- Test seed consumable creation with variety selection
- Test stock adjustment with variety information displayed
- Verify that recipes connect properly through seed_consumable_id
- Ensure dashboard views show correct variety information

## Future Considerations
This approach maintains backward compatibility while enhancing the consumable management with variety-specific data. It allows us to:

1. Leverage existing seed variety data while using the consumable inventory system
2. Maintain data integrity between recipes, consumables, and varieties
3. Provide richer information when managing seed stock

In a future phase, we could consider migrating fully to consumables if needed, but this integration approach gives immediate benefits while preserving all existing functionality.

## Implementation Timeline
1. Schema changes - 1 day
2. Model relationships - 0.5 days
3. Resource updates - 1-2 days
4. UI improvements - 1-2 days
5. Testing - 1 day

Total estimated effort: 4-6 days 