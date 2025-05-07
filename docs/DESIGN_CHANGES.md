# Design Changes

This document tracks important design changes made to the Catapult v2 project.

## Enhanced Grow Plan Layout and Watering Schedule Reactivity

**Date:** March 23, 2025

### Description

Improved the grow plan section in the recipe creation wizard with a more intuitive layout and enhanced the reactivity of the watering schedule to reflect changes made in the grow plan section.

### Changes Made

1. Grow Plan Layout:
   - Reorganized the grow plan details layout:
     - Moved Seed Soak input to its own line for emphasis
     - Arranged remaining fields (DTM, Germination Days, Blackout Days, Light Days) horizontally in a 4-column grid
     - Made Growth Phase Notes section collapsed by default to reduce visual clutter
   - Enhanced calculation of Light Days based on seed soak hours, germination days, and blackout days

2. Watering Schedule Improvements:
   - Added the `light_days` field to the database schema via migration
   - Fixed the dynamic column count in the watering schedule grid to adjust based on displayed sections
   - Enhanced the watering schedule to reflect the actual number of days in each phase
   - Added a display of current growth phase settings above the watering schedule
   - Implemented a "Refresh Watering Schedule" button to manually update when grow plan values change
   - Added success notification when watering schedule is refreshed

3. Data Processing Enhancements:
   - Updated `handleRecordCreation` method to properly calculate and store light_days value
   - Ensured light_days is properly saved to the database
   - Improved day type classification for watering schedule display

### Impact

- More intuitive organization of the grow plan form
- Clearer relationship between seed soak, growth phases, and total days to maturity
- More responsive watering schedule that accurately reflects changes to the grow plan
- Better user experience with collapsed sections reducing visual overwhelm
- Improved data integrity with light days properly calculated and stored in the database

## Improved Watering Schedule Implementation

**Date:** April 4, 2025

### Description

Enhanced the watering schedule section in the recipe creation workflow with a more intuitive, organized, and visually clear interface. This improvement makes it easier to manage day-by-day watering amounts across different growth phases.

### Changes Made

1. UI/Forms:
   - Restructured the watering schedule interface into a 3-column layout:
     - Column 1: Germination & Planting phase days
     - Column 2: Growing phase days (including blackout and light phases)
     - Column 3: Pre-harvest days
   - Implemented clear visual separation between growth phases
   - Added appropriate default values for each phase:
     - Germination days: 500ml (configurable)
     - Growing days: 500ml (configurable)
     - Pre-harvest days: 0ml (configurable)
   - Improved form reactivity to DTM (Days To Maturity) field changes
   - Enhanced visibility control based on data availability

2. Data Handling:
   - Improved the JSON structure for storing watering schedules
   - Enhanced the validation and processing of watering schedule data
   - Implemented proper day type categorization (germination, normal, pre-harvest)
   - Added afterValidation processing to ensure complete data collection

### Impact

- More intuitive management of watering schedules across the entire growth cycle
- Clearer visual distinction between different growth phases
- Better organization of watering data for easier management
- Improved user experience when creating or editing recipes
- More consistent and predictable watering schedule generation
- Enhanced documentation of watering requirements in recipe notes

## Product Store Visibility Addition

**Date:** March 21, 2025

### Description

Added a dedicated field to control product visibility in the store, separating it from the general "active" status. This allows products to be active in the system (for inventory management, internal orders, etc.) but not displayed in the public store.

### Changes Made

1. Database Schema:
   - Added a boolean `is_visible_in_store` column to the `items` table with a default value of `true`

2. Models:
   - Added `is_visible_in_store` to the `$fillable` and `$casts` arrays in the `Item` model
   - Added it to the activity log for tracking changes
   - Added accessors/mutators to handle the difference between the database column name (`active`) and the model attribute (`is_active`)

3. UI/Forms:
   - Added a toggle field for "Visible in Store" to the product form
   - Added an "In Store" column to the products table
   - Added a filter to show/hide products based on store visibility
   - Added bulk actions to quickly show/hide multiple products in the store

### Impact

- More granular control over product visibility
- Products can now be active in the system but hidden from the store
- Improved workflow for managing products with different visibility requirements

## Product-Recipe Relationship Removal

**Date:** March 21, 2025

### Description

The relationship between Products (Items) and Recipes was completely removed. Previously, all products were required to have an associated planting recipe, which was unnecessarily restrictive as products should have no relationship to planting recipes at all. Recipes should relate only to crops, not to final products.

### Changes Made

1. Database Schema:
   - Removed the `recipe_id` column from the `items` table
   - Removed the foreign key constraint to the recipes table

2. Models:
   - Removed the `recipe()` relationship method from the `Item` model
   - Removed `recipe_id` from the `$fillable` array in the `Item` model
   - Removed the `items()` relationship method from the `Recipe` model

3. UI/Forms:
   - Removed the Recipe selection field from the Product creation/edit form
   - Removed the Recipe column from the Products table
   - Removed the Recipe filter from the Products table

### Impact

- Products and Recipes are now completely decoupled
- This aligns the system with the correct business requirement that recipes relate only to crops, not to final products
- The data model now correctly represents that planting recipes are used for growing, not for product classification

## Pricing Variations System Addition

### Date
March 21, 2023

### Description
Implemented a flexible pricing variations system to replace hardcoded prices. This allows for multiple pricing tiers based on customer type, quantity, and other factors.

### Changes Made
- Removed hardcoded price fields (`price`, `retail_price`, `wholesale_price`) from the `items` table
- Created a new `price_variations` table with the following structure:
  - `id`: Primary key
  - `item_id`: Foreign key to the items table
  - `name`: Name of the price variation (e.g., "Retail", "Wholesale", "Bulk")
  - `customer_type`: Type of customer this price applies to
  - `price`: The price for this variation
  - `minimum_quantity`: Minimum quantity for this price to apply
  - `maximum_quantity`: Maximum quantity for this price to apply
  - `is_default`: Whether this is the default price variation
  - `is_active`: Whether this price variation is active
- Added a `PriceVariation` model with relationship to `Item`
- Updated the `Item` model to include relationship to price variations

### Impact
- Products can now have multiple price points based on customer type, quantity, and other factors
- Pricing can be more flexible and dynamic, allowing for promotional pricing, tiered pricing, and customer-specific pricing
- Store owners can manage different pricing strategies more effectively

## Product Field Removal

### Date
March 21, 2023

### Description
Removed the `expected_yield_grams` field from products to better separate product data from crop and recipe data.

### Changes Made
- Removed the `expected_yield_grams` column from the `items` table
- Updated the `Item` model to remove references to this field
- Removed this field from all related forms and views

### Impact
- Cleaner separation between product data and crop/recipe data
- Simplified product management interface
- Ensures yield data is managed solely in the appropriate context (recipes and crops)

## Price Variations System Enhancement

### Date
March 21, 2025

### Description
Enhanced the price variations system to focus on physical product characteristics (unit, SKU, weight) rather than customer type and quantity. This better supports inventory tracking and shipping calculations.

### Changes Made
- Updated the `price_variations` table structure:
  - Removed `customer_type`, `minimum_quantity`, and `maximum_quantity` fields
  - Added `unit` field with options: item, lbs, gram, kg, oz
  - Added `sku` field for inventory tracking
  - Added `weight` field with precise decimal (10,3) for accurate weight tracking
- Updated the `PriceVariation` model to reflect these changes
- Modified the UI to support the new fields:
  - Added unit selection with appropriate options
  - Added SKU/UPC Code field for each variation
  - Added weight field with unit-specific labeling
- Removed the SKU/UPC Code field from the product level (Item model and UI)
  - SKUs now exist exclusively at the variation level
  - This ensures each distinct sellable unit has its own unique identifier

### Impact
- Better alignment with physical inventory management
- Support for various units of measurement (per item, by weight)
- Unique SKUs per variation for precise inventory control
- More accurate shipping calculations based on weight
- Clearer distinction between the product concept and its sellable variations

## Multiple Product Photos Implementation

### Date
March 21, 2025

### Description
Implemented a system for managing multiple product photos with the ability to designate one as the default for store display. This enhances product presentation and allows for more detailed visual information.

### Changes Made
- Created a new `item_photos` table with:
  - `item_id`: Foreign key to the items table
  - `photo`: Image path/filename
  - `order`: Position for display ordering
  - `is_default`: Boolean flag for the default photo
- Added an `ItemPhoto` model with relationship to `Item`
- Updated the `Item` model with:
  - `photos()` relationship to access all photos
  - `defaultPhoto()` method to get the designated default photo
  - `getDefaultPhotoAttribute()` accessor that provides fallback behavior
- Added UI components:
  - Photo management section in the product edit form
  - Ability to upload multiple photos with drag-and-drop reordering
  - Toggle to set the default photo
- Added bulk upload interface for managing multiple product photos at once
- Maintained backward compatibility with the legacy `image` field
- Implemented safeguard to ensure only one photo can be set as default

### Impact
- Enhanced product presentation with multiple views
- Better visual communication of product features
- Improved organization with photo ordering
- Flexibility to choose which image represents the product in listings
- Consistent presentation with a single default photo

## UI Enhancement: Modal-Based Relations Management

### Date
March 25, 2025

### Description
Replaced repeater-based components with modal-based interfaces for managing related entities, starting with price variations. This approach provides a cleaner, more intuitive user interface and better organization of related data.

### Changes Made
- Replaced the price variations repeater with a table view of existing variations
- Implemented an "Add Price Variation" button that opens a modal for creating new variations
- Added a "Manage All Variations" button for quick access to the full relation manager
- Documented this approach as the preferred method for managing related entities

### Benefits
- Cleaner user interface with less visual clutter
- Better organization of data in tables for easy scanning
- More focused editing experience with modals
- Improved consistency across the application
- Enhanced user experience for managing complex related data
- Reduced form complexity and improved validation handling

### Impact
- More intuitive management of price variations
- Established a pattern for other relation management throughout the application
- Improved overall user experience for product management

### Implementation Notes
This modal-based approach is now the preferred method over repeater elements for managing relations in Catapult v2. Future relation management implementations should follow this pattern for consistency.

## Item to Product Model Migration

### Date
November 18, 2023

### Description
Migrated from the Item model to a dedicated Product model to better align the codebase with business terminology and improve semantic clarity. This change maintains database compatibility while providing a more intuitive API for product-related operations.

### Changes Made
- Created a new `Product` model that uses the existing `items` table:
  - Added proper table association via `protected $table = 'items'`
  - Ensured all necessary fields are marked as fillable
  - Added proper casting for boolean and decimal fields
  - Enhanced the `getPriceForCustomerType` method with intuitive fallbacks
- Created a `ProductPhoto` model that uses the existing `item_photos` table:
  - Maintained compatibility with existing database schema
  - Added proper relationship to the new Product model
  - Preserved the `setAsDefault` functionality
- Updated `ProductResource` to use the new Product model instead of Item
- Modified the product-price-calculator view to work with the new model
- Created a comprehensive `ProductFactory` for testing
- Updated all tests to use the new Product model:
  - Fixed Livewire test approach for Filament compatibility
  - Updated validation tests to use proper Laravel validation

### Impact
- More intuitive naming that aligns with business terminology
- Clearer separation of concerns in the codebase
- Improved developer experience with more semantic model naming
- Maintained backward compatibility with existing database structure
- Enhanced testing infrastructure with dedicated product factory
- All tests passing with the new model structure

## Enhanced Product Price Variations Integration

### Date
November 19, 2023

### Description
Enhanced the Product model to fully integrate with the existing price variations system, moving away from direct price fields to a more flexible, variation-based approach. This change maintains backward compatibility while encouraging better pricing structure for products.

### Changes Made
- Updated the Product model to deprecate direct price fields:
  - Added getters for base_price, wholesale_price, bulk_price, and special_price that first check for matching price variations
  - Implemented automatic creation of default price variations during product creation
  - Added helper methods for working with product price variations
- Modified the price variations relationship:
  - Updated PriceVariation model to use Product instead of Item
  - Maintained backward compatibility through interface consistency
  - Added proper foreign key relationships
- Enhanced the product interface:
  - Simplified product creation form with just base price entry
  - Added price variations panel to product view/edit pages
  - Created partial blade view for displaying price variations
  - Improved UX with clear pricing information
- Added data migration to support transition:
  - Created migration to generate price variations for existing products
  - Ensured products with existing price fields got proper variations
  - Maintained data integrity during the transition

### Impact
- More flexible pricing structure for products through variations
- Better organization of pricing data with support for multiple units
- Improved UX for creating and managing product prices
- Maintained backward compatibility with existing code
- Enhanced price variation management with better visibility
- Clearer path forward for using the more powerful price variations system 