# Catapult v2 Changelog

This document tracks all significant changes to the Catapult v2 project.

## [Unreleased]

### Added
- Enhanced "Ready to advance" display for crops with overdue time (2024-09-10)
  - Added red display of elapsed time past expected stage transition
  - Added logic to calculate and show how overdue a crop is for advancement
  - Improved visibility of crops that need attention
  - Better tracking of growth stage transition timeliness
  - Supports farm managers in prioritizing tasks
- Major redesign of Grows (formerly Grow Trays) system (2024-09-05)
  - Crops are now grouped by variety, planting date, and growth stage
  - Single entry in list view represents multiple trays
  - Edits apply to all trays in a grow batch
  - Added ability to add new trays to existing grow batches
  - Simplified workflow for farm operations
  - Improved UI with tray count display and clear tray number listing
  - Actions like stage advancement affect all trays in a batch
  - Better data organization for reporting and tracking
  - Fixed SQL GROUP BY issue with proper aggregation of columns (2024-09-05)
  - Added migrations for time calculation fields (time_to_next_stage, stage_age, total_age)
  - Created scheduled command to update time values every 15 minutes
- Added database view for crop batches (2024-09-10)
  - Created `crop_batches` view for improved performance and cleaner code
  - Replaced raw SQL queries with a structured database view
  - Optimized grouping of crops for batch operations
  - Fixed MySQL ONLY_FULL_GROUP_BY mode compatibility issues
  - Implemented proper aggregation functions for all columns
  - Supports efficient batch operation on multiple trays
- Seed varieties tracking system
  - New `seed_varieties` table to track different crop varieties and brands
  - Updated `recipes` table to reference specific seed varieties
  - Updated `inventory` table to track inventory by seed variety
  - Added relationships between models
- Complete set of models with relationships and activity logging:
  - `Supplier` model for managing seed and soil suppliers
  - `SeedVariety` model for tracking seed varieties and brands
  - `Recipe` model with relationships to stages and watering schedules
  - `RecipeStage` model for stage-specific notes
  - `RecipeWateringSchedule` model for detailed watering instructions
  - `RecipeMix` pivot model for recipe mixes
  - `Crop` model with helper methods for tracking growth stages
  - `Inventory` model with restocking indicators
  - `Consumable` model for packaging and other materials
  - `Item` model for products sold to customers
  - `Order` model with status tracking and payment calculations
  - `OrderItem` model for line items in orders
  - `Payment` model for tracking different payment methods
  - `Invoice` model for wholesale customers
  - `Setting` model for application configuration
- Enhanced User model with roles and permissions via Spatie
- Activity logging system using Spatie Activity Log
  - Added `activity_log` table to track all system activities
  - Created `Activity` model extending Spatie's Activity model
  - Implemented activity logging for all models
  - Added `ActivityResource` for admins to view and filter logs
- User management system
  - Created `UserResource` for managing users and their roles
  - Implemented policies to restrict access to admin-only resources
  - Added ability for admins to change user roles
  - Created `AuthServiceProvider` with policies and gates
- Database factories and seeders for development environment
  - Created factories for all models with realistic test data
  - Implemented `RoleSeeder` for creating default roles
  - Added `AdminUserSeeder` for creating persistent admin user
  - Added `FilamentAdminUserSeeder` for creating persistent Filament admin
  - Created `DevelopmentSeeder` for populating development environment
  - Fixed compatibility issues between models, migrations, and factories
  - Successfully seeded database with complete test dataset
- Authentication system with Laravel Breeze
  - Installed Laravel Breeze for authentication scaffolding (required for Laravel 12)
  - Configured Blade-based authentication views
  - Integrated with Filament admin panel for secure access
- Packaging management system
  - Created `PackagingType` model for different packaging options
  - Implemented `OrderPackaging` model to track packaging per order
  - Added relationship to Order model for easy access to packaging data
  - Created `PackagingTypeResource` for managing packaging types
  - Added Packaging relation manager to OrderResource
  - Implemented auto-assign packaging functionality based on order items
- Invoicing system for wholesale customers
  - Enhanced Invoice model with status tracking and PDF generation
  - Created `InvoiceResource` with comprehensive management features
  - Implemented PDF generation for invoices using DomPDF
  - Added download functionality for invoice PDFs
  - Created professional-looking PDF template for invoices
  - Added payment status tracking for invoices
- Enhanced payment tracking
  - Added support for multiple payment methods
  - Implemented payment status tracking
  - Created relation manager for tracking payments per order
- Enhanced Consumables inventory management
  - Updated consumable types to standardized categories: packaging, soil, seed, labels, other
  - Added weight tracking for consumable units with multiple measurement options (grams, kilograms, liters, ounces)
  - Implemented total weight calculation based on unit weight and quantity
  - Improved stock management with better tracking of weights and quantities
- Removed obsolete seed_variety_id form logic from RecipeResource.php (2025-04-28)
  - Simplified the seed_variety_id field to use the standard relationship selector
  - Removed complex createOptionForm and createOptionUsing functionality
  - Maintained the primary seed creation functionality in RecipeResource/Pages/CreateRecipe.php
  - Reduced code duplication and simplified the codebase

### Changed
- Updated PackagingType model to use volumetric measurements
  - Added capacity_volume (decimal) and volume_unit (string) fields to store volume data
  - Removed capacity_grams field in favor of volumetric measurements
  - Updated Filament forms to include volume fields with appropriate options (oz, ml, l, pt, qt, gal)
  - Updated OrderPackaging model to calculate and display total volume
  - Added support for displaying volume measurements in appropriate units
  - Added display_name accessor for better identification of packaging variants
  - Improved auto-assign packaging algorithm to select appropriate packaging types
- Improved Consumable model for better packaging inventory management
  - Added relationship between Consumable and PackagingType models
  - Enhanced ConsumableResource UI with packaging type selection for packaging consumables
  - Updated table display to show packaging specifications
- Modified recipe notes handling to improve organization
  - Removed automatic markdown generation for watering schedules
  - Refactored general notes section in recipe forms
  - Maintained the notes column for storing important growth phase information
  - Growth phase notes continue to be available in the RecipeStage model
  - Watering schedule details are stored in the RecipeWateringSchedule model
- Updated tray number validation to remove numerical constraints (2025-06-03)
  - Removed min and max value constraints on tray numbers in CropResource
  - Removed min and max value constraints on tray numbers in CropsRelationManager
  - Tray numbers are still validated as integers but can be any whole number
  - Improves flexibility for different tray numbering systems

### Fixed
- Fixed migration ordering issue with consumables and packaging types tables
  - Separated the packaging_type_id foreign key constraint into a separate migration
  - Ensures migrations can run in any environment without sequence errors
- Fixed duplicate lot_no column migration
  - Removed redundant add_lot_no_to_consumables_table migration
  - Consolidated lot_no field in the initial consumables table migration
- Fixed CropFactory to use new stage timestamp fields
  - Updated migration to safely handle missing stage_updated_at column
  - Completely refactored CropFactory to use individual stage timestamp fields
  - Fixed DateTime vs Carbon compatibility issue in date manipulation
  - Ensures seeders work correctly with the new database schema
- Fixed implicit float to int conversion warnings in CropResource
  - Removed explicit int return type hints in stage_age and total_age columns
  - Refactored time difference calculation to use Carbon's diff() method instead of diffInSeconds()
  - Eliminates all precision loss warnings by using native DateTime interval components
  - Improved code readability and accuracy when calculating time remaining to next stage
- Fixed seed consumable creation validation error (2025-04-19)
  - Completely redesigned the seed variety selection interface for clarity
  - Added a Debug Form tool to help diagnose form submission issues
  - Enhanced error notifications with detailed actionable information
  - Simplified the form schema to reduce potential reactive conflicts
  - Added comprehensive exception handling and error reporting
  - Improved user feedback when validation fails
  - Added guard clauses to prevent silent failures
  - Eliminated form submission issues with seed variety selection
- Fixed issue with seeded SeedVariety records (2025-04-19)
  - Added crop_type field to SeedVariety model (made nullable)
  - Simplified seed variety creation form to remove unnecessary fields
  - Fixed compatibility between seeded varieties and form requirements
  - Enabled proper selection of existing seed varieties in forms
- Fixed consumable creation error in RecipeResource (2024-06-20)
  - Fixed SQL error when creating new seed or soil consumables
  - Added default value for consumed_quantity field in createOptionUsing functions
  - Ensures compatibility with the updated Consumable model schema
- Fixed supplier selection in RecipeResource (2024-06-20)
  - Fixed supplier dropdown not showing options when creating soil consumables
  - Corrected query syntax in supplier_id field options callback
  - Now properly filters suppliers by type (soil, null, or other)

### Enhanced
- Improved crop stage duration display in the crops list view
  - Changed from showing only days to a more detailed format with days, hours, and minutes
  - Renamed column from "Days in Stage" to "Time in Stage" to reflect the more precise measurement
  - Uses the same human-readable format as the "Time to Next Stage" column for consistency
- Improved time display in the crops list view
  - Enhanced both "Time in Stage" and "Total Age" columns to show days, hours, and minutes
  - Renamed columns to better reflect the more precise measurements
  - Provides consistent human-readable time format across the crops interface
  - Helps farmers track crop progress with greater precision

## [0.1.0] - 2025-03-15

### Added
- Initial project setup with Laravel
- Installed Filament v3 admin panel
- Installed required packages: Socialite, Spatie Permission, Cashier
- Created comprehensive database schema with migrations:
  - Suppliers management
  - Recipe system with stages and watering schedules
  - Crop tracking
  - Inventory management
  - Order processing
  - Payment handling
- Created documentation:
  - Project roadmap
  - Development environment setup
  - Git procedures
  - Microgreens SOP

### Technical Details
- Using Laravel 12.x
- PHP 8.2+
- MySQL database
- Filament 3.x for admin panel

## 2024-03-19
- Implemented Dashboard with Crops and Inventory tabs
  - Created CropTraysWidget for tracking crops in different stages
  - Created InventoryWidget for monitoring stock levels and reorder alerts
  - Added stage advancement and watering suspension controls
  - Added inventory reordering functionality
  - Implemented color-coded status indicators for both crops and inventory items

## 2025-03-23
- Enhanced Recipe Creation Workflow:
  - Improved grow plan layout with reorganized fields for better usability
  - Added light_days field to database schema for proper growth phase tracking
  - Fixed dynamic column count in watering schedule grid
  - Enhanced watering schedule reactivity to grow plan changes
  - Added visual display of current growth phase settings
  - Implemented "Refresh Watering Schedule" button with success notification
  - Made Growth Phase Notes section collapsed by default to reduce visual clutter

## 2025-05-15
- Completed Phase 4: Packaging & Payments
  - Added packaging type management system
  - Implemented order packaging relationship
  - Created invoice system with PDF generation
  - Enhanced payment tracking capabilities

## 2025-05-26
- Enhanced packaging system with volumetric measurements
  - Updated PackagingType model to use capacity_volume and volume_unit fields
  - Removed the legacy capacity_grams field
  - Added support for various volume units (oz, ml, l, pt, qt, gal)
  - Modified UI to display volume appropriately in tables and forms
  - Created migration script to convert existing data to the new format
  - Implemented display_name accessor for clear identification of packaging sizes
  - Created relationship between Consumable and PackagingType models
  - Enhanced ConsumableResource to display packaging specifications
  - Improved auto-assign packaging logic to make smarter choices based on volume

## 2025-04-18 - Crop Resource UI Improvements
- Updated crop list view to show seed variety names with proper emphasis
- Enhanced variety display by showing recipe name below the variety
- Fixed issues with variety relationship display in crop table
- Made all columns toggleable in the crop table view 

## 2023-06-01 - Initial release
- First version of the farm management system
- Core functionality for tracking crops, recipes, and inventory

## 2023-06-15 - Added order management 
- Order tracking with customer details
- Generate order reports

## 2023-07-10 - Dashboard improvements
- Added real-time alerts for crop stages
- Improved visual design of dashboard

## 2023-08-17 - Task management
- Automated task scheduling for crop care
- Task completion tracking

## 2023-09-22 - Inventory tracking enhancements
- Low stock alerts
- Consumable usage tracking
- Reorder functionality

## 2023-10-30 - Recipe management updates
- Added ability to clone recipes
- Improved recipe editor interface

## 2023-12-05 - Reporting improvements
- Added financial reports
- Yield tracking by crop variety
- Export to CSV/PDF options

## 2024-01-18 - User management updates
- Role-based permissions
- Activity logging

## 2024-03-05 - Mobile interface improvements
- Responsive design for farm operations on mobile devices
- Barcode scanning for inventory

## 2024-04-22 - Crop stage transition improvements
- Visual indicators for crop stage progression
- Automated notifications for stage transitions

## 2024-06-01 - First anniversary update
- UI refresh with new theme options
- Performance improvements

## 2024-06-19 - Harvest date calculation fix
- Fixed inconsistent harvest date calculations where time would display as "23h 56m"
- Updated all stage transition calculations to use addDays() instead of addSeconds() for consistent date handling
- Standardized time calculations across germination, blackout and light stages 

## 2023-10-26 - Added real-world seed data
- Created RealWorldRecipesSeeder with realistic data for microgreens farming
- Added detailed suppliers, seed varieties, soil types, and recipes
- Includes complete growing data for Sunflower, Pea, Radish, and Broccoli microgreens
- Configured detailed watering schedules for each crop type

## 2024-06-25 - Enhanced crop alerts with precise time display
- Updated crop alerts to display time in days, hours, and minutes format
- Improved stage transition detection to use more precise hourly calculations
- Modified alerts for blackout stage to use hour-based thresholds instead of day-based
- Ensured consistent time formatting across all crop stages
- Improved visual representation of time in crop alerts widget

## 2024-06-27 - Upgraded crop alerts system and task management
- Completely overhauled the crop alerts resource to use more precise time calculations
- Updated the CropTaskService to use direct day calculations for better precision
- Improved task scheduling to align with the reformed grow resource
- Enhanced the ManageCropTasks page with better time display formatting
- Added badge indicators for task status and improved readability
- Standardized time display format across all crop-related interfaces

## 2023-10-05

### Added
- Added support for supplier types in the `Supplier` model: "soil", "seed", "packaging", or "other"
- Added initial implementation of Weekly Planning view
- Added crop task management system with automatic scheduling and notifications
- Added ability to track seed soaking time in recipes
- Added the ability to clone crops
- Added search capabilities to crop manager

### Changed
- Improved user interface for the crop management screens
- Enhanced dashboard with better metrics and visualization of upcoming harvests
- Reorganized navigation to better group related functionality

## 2023-11-12

### Added
- Added new CropGrowthSimulationTest to verify time-based crop lifecycle calculations
- Added comprehensive test for validating crop stage transitions and time calculations

### Fixed
- Removed duplicate `getTable` method from ManageCropTasks component to fix HasTable contract conflict
- Fixed SQL errors related to consumed_quantity field in consumables
- Fixed supplier filtering in consumable creation forms

### Changed 
- Enhanced dashboard statistics for better farm monitoring
- Modified recipe component to support all consumable types

## 2025-05-01 - Added database storage for time in stage
- Created database columns for storing time in current stage values
- Added `stage_age_minutes` column for efficient sorting of time in stage
- Added `stage_age_status` column for human-readable time display
- Updated the crops database schema for persistent, sortable fields
- Enhanced command to update both time to next stage and time in stage values
- Modified CropResource to use database columns for consistent sorting
- Fixed SQL query error when sorting on computed fields

## 2025-05-01 - Added database storage for time to next stage
- Created database columns for storing time to next stage values
- Added `time_to_next_stage_minutes` column for efficient sorting
- Added `time_to_next_stage_status` column for human-readable status display
- Implemented automatic calculation of values when saving crops
- Created scheduled command to update values every 15 minutes
- Modified CropResource to use database columns for sorting
- Ensured backward compatibility with existing code

## 2025-05-01 - Fixed column sorting in Filament tables
- Fixed critical bug preventing sorting on computed columns
- Implemented column mapping system for virtual columns
- Added proper defaults for time-based sorting
- Enhanced debugging tools for SQL query troubleshooting
- Improved developer experience with detailed log output

## 2025-05-01 - Fixed sorting issues with computed columns in Filament
- Added database columns for storing computed values: time_to_next_stage, stage_age, and total_age
- Updated the Crop model to calculate and store these values when saving records
- Created and scheduled a command to update these values on existing records
- Modified CropResource to use the stored database values for reliable sorting

## 2025-05-01 - Fixed duplicate seed varieties in dropdown menus
- Fixed issue where seed variety dropdown showed duplicates
- Modified ConsumableResource to filter unique varieties
- Added ability to create new seed varieties from dropdowns 
- Created cleanup script to resolve existing duplicates
