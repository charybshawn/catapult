# Catapult v2 Changelog

This document tracks all significant changes to the Catapult v2 project.

## [Unreleased]

### Added
- Migrated from SeedCultivar to SeedEntry model system (2025-06-08)
  - Replaced deprecated SeedCultivar model references with SeedEntry throughout the codebase
  - Updated Recipe model relationship from seedCultivar() to seedEntry()
  - Modified Crop model to use seedEntry relationship and cultivar_name field
  - Updated ProductMix model to use seedEntries() relationship instead of seedCultivars()
  - Changed Supplier model relationship from seedCultivars() to seedEntries()
  - Updated all Filament resources to use SeedEntry model and field structure
  - Fixed internal server error caused by missing SeedCultivar model
  - Ensured backward compatibility with existing seed_cultivar_id foreign keys
- Removed redundant cataloged_at field from seed entries (2025-06-08)
  - Dropped cataloged_at column as it duplicated the functionality of created_at
  - Removed cataloged_at from SeedEntry model fillable fields and casts
  - Updated SeedEntryResource form to remove cataloged_at field
  - Removed cataloged_at column and filter from seed entries table
  - Simplified CreateSeedEntry logic by removing cataloged_at handling
  - Users can now use created_at for all date filtering and sorting needs
- Added comprehensive currency conversion and unit standardization (2025-06-08)
  - Created CurrencyConversionService with live exchange rates and fallback rates
  - Added USD/CAD conversion methods with caching for performance
  - Enhanced SeedVariation model with price conversion attributes (price_in_cad, price_in_usd)
  - Added price per kg conversion methods for both CAD and USD
  - Implemented Imperial to Metric weight conversion (lbs/oz to kg)
  - Updated SeedReorderAdvisor with currency toggle (CAD/USD display)
  - Enhanced SeedScrapeImporter with automatic weight parsing and conversion
  - Added regex patterns to extract weights from size descriptions
  - Updated SeedVariationResource to show both original and converted prices
  - Enabled fair comparison between Canadian and US seed products
  - All price comparisons now use consistent currency and weight units
- Enhanced Seed Scrape Uploader interface (2025-05-27)
  - Added auto-refresh functionality to display real-time upload status
  - Implemented visual indicator for page refreshes
  - Added collapsible sample JSON format display to guide users
  - Improved error handling and user feedback during uploads
  - Added direct links to view imported seed data
  - Enhanced page layout with workflow diagram and helpful tips
  - Fixed storage directory permissions for file uploads
- Enhanced Product model with improved Price Variations integration (2025-06-19)
  - Updated PriceVariation model to work with Product model instead of Item
  - Added methods to Product for creating and managing price variations 
  - Added accessor methods to maintain backward compatibility
  - Modified ProductResource to simplify pricing UI
  - Created dedicated price variations panel for product view/edit pages
  - Added data migration to generate price variations for existing products
  - Updated tests to work with the price variations system
- Added Germina as a seed supplier in Montreal, Canada (2025-06-20)
  - Updated MicrogreenSeeder with complete contact information
  - Expanded seed supplier options for Canadian customers
  - Added support for organic certified sprouting and microgreen seeds
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
- Added new CropAlert system (2024-08-15)
  - Created new CropAlert model that extends TaskSchedule
  - Created dedicated CropAlertResource and associated page classes
  - Created database migration for future dedicated crop_alerts table
  - Added global scope to CropAlert model to filter only crop-related alerts
  - Added relationship to Crop model
  - Added accessor methods for improved readability
  - Implemented parallel UI that will replace the TaskScheduleResource UI
  - Updated dashboard links and widgets to reference the new resource
- Added Days to Maturity (DTM) field to Recipe form (2024-08-15)
  - Added days_to_maturity field to the recipes table via migration
  - Implemented automatic calculation of Light Days based on DTM - (germination + blackout)
  - Light Days field is now calculated rather than manually entered
  - Updated totalDays() method to use DTM when available
  - Improved form reactivity to update calculations when related fields change
- Implemented lot number tracking for seed inventory (2024-08-15)
  - Added required lot number field when adding seed stock
  - Prevented mixing different lot numbers in the same inventory record
  - Created new inventory records automatically for different lot numbers
  - Improved seed inventory management with better traceability
  - Enhanced food safety tracking with lot-specific inventory
- Added debug tools for tracking and resolving errors (2024-09-18)
  - Created DebugService for detailed error logging and object inspection
  - Added error interception in AppServiceProvider to capture "isContained() on null" errors
  - Enhanced Product model and ProductResource with debugging capabilities
  - Implemented robust error handling in ViewProduct page with fallback display
- Added debug slideout panel to Grows resource (2025-06-21)
  - Implemented detailed crop data inspection tool for farm managers
  - Displays current crop timestamps, stage ages, and related recipe information
  - Shows precise time calculations for stage age and time to next stage
  - Helps diagnose issues with time display and stage transitions
  - Provides real-time calculation values for comparison with database stored values
  - Assists farmers with troubleshooting growth tracking anomalies
- Made "no grouping" the default view for Grows resource (2025-06-21)
  - Removed default grouping by recipe name
  - Provides a flatter view of all grow batches by default
  - Grouping options (by recipe, plant date, growth stage) remain available
  - Improves immediate visibility of all active grows without additional clicks
  - Better aligns with most farmers' preference for seeing all current grows at once
- Added PHP-based restore fallback when `mysql` CLI is unavailable, ensuring database restoration works in environments without the MySQL client.

### Changed
- Completely redesigned seed inventory management (2024-08-15)
  - Replaced complex unit/quantity calculations with direct total quantity tracking
  - Created a simplified UI with separate form layouts for seed vs. other consumables 
  - Added clear lot number tracking field in both create and edit forms
  - Modified restock settings to use appropriate units and defaults for seeds
  - Improved inventory adjustments to handle lot numbers correctly
  - Implemented automatic creation of new inventory records for different lot numbers
  - Updated inventory calculations to work directly with total quantity
  - Improved usability by eliminating conversion calculations
  - Simplified stock tracking for seed consumables
  - Updated display to show stock in appropriate units (g, kg, etc.)
- Removed Total Estimated Grow Days display from Recipe forms (2024-08-15)
  - Removed redundant placeholder field that duplicated DTM information
  - Simplified the Recipe creation and editing interfaces
  - DTM field now serves as the single source of truth for total growth time
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
- Improved Grows list page UI with icon buttons (2024-09-11)
  - Converted all action buttons to use icons with tooltips instead of text labels
  - Includes main row actions and bulk actions
  - Creates a cleaner, more modern interface
  - Maintains functionality while reducing visual clutter
- Removed deprecated crop tasks UI components (2025-06-12)
  - Removed CropTaskResource and all associated pages
  - Removed ManageCropTasks page and view
  - Updated Crop Alerts widget to point to TaskScheduleResource
  - Updated dashboard links to use TaskScheduleResource for alerts management
  - Consolidated crop stage management into TaskScheduleResource (Crop Alerts)
  - Streamlined the UI by removing duplicate functionality
  - Simplified navigation by focusing on a single interface for crop stage management
- Refactored dashboard UI for improved dark mode compatibility and layout (2025-06-12)
  - Replaced custom CSS with Tailwind utility classes and Filament components (`x-filament::section`, `x-filament::tabs`, etc.)
  - Implemented Livewire-based tab management in the `Dashboard` page class
  - Ensured consistent styling in both light and dark modes
  - Optimized grid layout for better screen real estate usage on various devices
  - Improved code readability and maintainability by removing the `<style>` block
  - Updated value calculation for Total Harvested Value and Weight display
- Simplified dashboard tabs to focus on key farm operations (2025-06-12)
  - Removed Stats tab and reorganized content into three focused tabs
  - Renamed "Inventory Alerts" to "Inventory/Consumable Alerts" for clarity
  - Added tab state validation to prevent unexpected user input
  - Implemented query string support for direct tab linking
  - Improved dashboard navigation with user-requested sections
- Moved "Today's Crop Alerts" to dedicated widget (2025-06-14)
  - Created new TodaysCropAlertsWidget component for better code organization
  - Moved alerts display from active-crops tab to crop-alerts tab
  - Fixed array to string conversion error in conditions field
  - Improved dashboard organization with logical tab grouping
  - Implemented Livewire component for real-time updates (15-minute refresh)
- Improved task scheduling with grow batch support (2025-06-15)
  - Modified CropTaskService to create alerts for entire grow batches instead of individual trays
  - Updated alerts to show consolidated information for multiple trays
  - Created batch identification system using recipe, planting date, and stage
  - Enhanced task processing to handle all trays in a batch simultaneously
  - Improved alerts UI to show tray counts and tray number lists
  - Reduced database load by eliminating duplicate alerts for the same batch
  - Added more realistic sample data in UpdateAlertsForToday command
- Simplified crop creation interface (2024-08-15)
  - Removed "Create Full Recipe" button from the crop creation form
  - Streamlined UI to avoid unnecessary navigation options
  - Recipe creation options remain available via the inline "Create" option
- Improved Products list UI with icon-based mix indicator (2025-06-20)
  - Changed product mix column from text to boolean icon for cleaner UI
  - Simplified product mix display to show presence/absence instead of name
  - Updated product mix filter to use ternary (yes/no/any) filtering
  - Better visual consistency with other boolean columns like "active" and "in store"
- Added auto-refresh to Grows list page (2025-06-20)
  - Configured 5-minute automatic refresh interval for the crops table
  - Ensures time-based fields (stage age, time to next stage, total age) are regularly updated
  - Provides farmers with accurate, real-time crop status without manual refreshing
  - Complements real-time calculated values with periodic UI updates
- Removed legacy debug code from Grows resource (2025-06-21)
  - Removed debug logging statements and temporary debug column from CropResource
  - Replaced with a cleaner implementation using the debug slideout panel
  - Improved code quality and readability by removing verbose logging
  - Reduced potential performance impact of excessive logging
  - Streamlined query structure for better maintainability
- Made "no grouping" the default view for Grows resource (2025-06-21)
  - Removed default grouping by recipe name
  - Provides a flatter view of all grow batches by default
  - Grouping options (by recipe, plant date, growth stage) remain available
  - Improves immediate visibility of all active grows without additional clicks
  - Better aligns with most farmers' preference for seeing all current grows at once

### Fixed
- Fixed tray batch advancement in Crops list (2024-10-01)
  - Completely redesigned all actions (advance stage, harvest, suspend watering) to operate on entire batches
  - Modified single-tray actions to automatically apply to all trays in the same batch
  - Added missing bulk action for advancing multiple selected trays at once
  - Fixed issue where advancing a batch of trays only moved one tray instead of all
  - Implemented proper notification showing how many trays were advanced in each operation
  - Added transaction support for safer batch operations
  - Improved farm efficiency by allowing batch operations on multiple trays
- Fixed crop stage age calculation to use current time (2024-09-18)
  - Modified Crop model's getStageAgeStatus method to always use the current time
  - Added detailed logging to track time calculations for debugging
  - Improved accuracy of stage duration display on Grows list page
  - Ensures proper real-time tracking of crop growth stages
- Fixed real-time display of crop timing fields in Grows list (2025-06-20)
  - Updated CropResource table columns to calculate values in real-time instead of using cached database values
  - Applied real-time calculations to Time in Stage, Time to Next Stage, and Total Age columns
  - Ensures accurate time display regardless of when the record was last updated
  - Eliminates confusion when stored database values appear outdated
- Fixed column sorting in Grows list for time-based fields (2025-06-20)
  - Implemented proper sorting for columns with real-time calculated values
  - Enhanced the update command to ensure sort values are accurately maintained
  - Added better logging of significant time value changes
  - Improved consistent time calculations by using a single timestamp for all calculations
- Fixed internal server error when sorting columns in Grows list (2025-06-20)
  - Fixed binding resolution error in column sorting closures
  - Added proper named parameter 'query' to sortable method calls
  - Ensures compatibility with Filament's latest API requirements
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
- Fixed TaskScheduleResource column toggle functionality (2025-06-12)
  - Replaced deprecated ToggleColumnsAction class with Filament 3's native column toggling
  - Added toggleable() to all columns in TaskSchedule table
  - Simplified implementation to use Filament's built-in column toggling feature
  - Ensures consistent UI experience with other resource tables
  - Resolves "Class not found" and "Too few arguments" errors when viewing task schedules
- Fixed dashboard not appearing as default landing page (2025-06-13)
  - Fixed type mismatch in Dashboard class with the $slug property (changed to ?string)
  - Configured AdminPanelProvider to use homeUrl('/admin/dashboard')
  - Ensured Dashboard extends Filament's Page class with correct configuration
  - Fixed routing configuration to make dashboard appear at the project URL root
  - Resolved white background and missing left menu issues
  - Added dark mode support in the AdminPanelProvider configuration
  - Set the proper Filament layout for the Dashboard class
  - Updated dashboard blade template to use correct Filament panels (x-filament-panels::page)
  - Completely restructured Dashboard class to extend BaseDashboard for proper Filament integration
  - Created a custom dashboard header view to maintain existing UI while ensuring full Filament compatibility
  - Optimized layout to use full screen width with proper responsive behavior
  - Adjusted grid spacing for better visual presentation
- Fixed crop stage advancement when executing tasks (2024-08-15)
  - Modified CropTaskService to actually advance crops when executing tasks
  - Previously tasks were marked as completed but crops weren't advancing to the next stage
  - Now crops properly advance to their next stage when alerts are executed
  - Fixed "Execute Selected" and "Execute Now" functionality in TaskScheduleResource
- Fixed title inconsistency in Crop Alerts pages (2024-08-15)
  - Updated page titles and breadcrumbs to consistently show "Crop Alerts" instead of "Task Schedules"
  - Fixed all related pages (list, edit, create) to use the proper title
  - Improved navigation consistency across the farm management interface
- Fixed overdue crop alerts execution (2024-08-15)
  - Modified CropTaskService to allow multi-stage advancement for overdue alerts
  - Previously alerts would fail with "not yet ready" message if crops were multiple stages behind
  - Now crops can advance through multiple stages in one execution
  - Added detailed messaging to show when intermediate stages are skipped
- Fixed Form field calculation in Recipe forms (2024-08-15)
  - Replaced incorrect `calculateDependantState` method with proper Filament live field updates
  - Updated Recipe form to use proper `live()` and `afterStateUpdated()` methods
  - Ensured Light Days value is correctly saved with the form
  - Added form save hook to guarantee correct calculation
- Fixed error in ProductResource.php with ViewProduct page (2024-09-18)
  - Fixed "Call to a member function isContained() on null" error when viewing product details
  - Improved error handling in form schema generation to prevent crashes
  - Implemented safer handling of form schema and panels in ViewProduct page
  - Simplified the ViewProduct page to avoid using potentially problematic panels
- Fixed error in ProductResource.php with Placeholder component (2024-09-18)
  - Replaced incompatible ->html() and ->markdown() methods with ->extraAttributes(['class' => 'prose'])
  - Fixed "Method Filament\Forms\Components\Placeholder::markdown does not exist" error
  - Ensures proper rendering of HTML content in product mix and price variations panels
  - Also fixed the same issue in RecipeResource/Pages/ViewRecipe.php and CropResource.php
- Fixed error in ProductResource's ViewProduct page with component compatibility (2024-09-18)
  - Replaced TextEntry components with Placeholder components in ViewProduct page
  - Fixed "Class Filament\Forms\Components\TextEntry not found" error
  - Implemented safer form rendering with proper component usage
  - Added additional error handling to prevent crashes when viewing products
- Fixed restore backup upload validation error for .sql files (2025-06-12)
  - Broadened accepted MIME types list in `DatabaseManagement` page FileUpload component to include `application/x-sql`, `application/octet-stream`, and other common variants.
  - Fixed path resolution for the uploaded file when using Livewire temporary uploads, preventing "Uploaded file not found" validation errors.
  - Allows uploaded SQL backup files to pass validation and be restored successfully regardless of browser-reported MIME type or upload mechanism.

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
- Implemented Seed Inventory & Pricing Management System
- Added Filament resources for seed cultivars, entries, variations, and price history
- Created custom pages for seed price trends and reorder advisors
- Implemented JSON upload system for importing seed data from suppliers
- Added dashboard widgets for monitoring seed prices and inventory levels

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

## 2024-08-15 - Fixed TaskSchedule bulk action type error
- Updated bulk action parameter to accept Eloquent Collection instead of array
- Fixed "Execute Selected" functionality in TaskScheduleResource

## 2023-11-18 - Migrated from Item model to Product model
- Created Product model that uses the existing items table
- Created ProductPhoto model that uses the existing item_photos table
- Updated ProductResource to use the Product model instead of Item model
- Fixed tests to work with the Product model
- Updated product-price-calculator view to use record properly

## 2023-11-19 - Enhanced Product with Price Variations Integration
- Deprecated direct price fields (base_price, wholesale_price, etc.) in favor of price variations
- Added automatic generation of price variations from legacy price fields
- Created price variations panel in product view/edit pages
- Updated price variation relationship to work with Product model
- Added migration to create price variations for existing products
- Simplified product creation form with just base price entry
- Enhanced price variations management with better UI

## 2025-05-07 - Global Price Variations Enhancement
- Made price_variations.item_id nullable to support truly global price variations
- Enhanced PriceVariation model to automatically set null item_id for global variations
- Updated CreatePriceVariation page to properly handle global variations
- Fixed internal server error when creating global price variations
- Added comprehensive tests for global price variations functionality
- Added documentation in DESIGN_CHANGES.md

## 2025-06-25 - Enhanced Product Creation with Price Variations
- Improved product creation process to automatically create price variations during initial creation
- Added fields for wholesale, bulk, and special pricing in the product creation form
- Enhanced Product model to better synchronize between legacy price fields and price variations
- Modified CreateProduct class to capture price data during form submission
- Added comprehensive tests for price variations functionality
- Eliminated the need to save a product before creating price variations
- Added detailed documentation in DESIGN_CHANGES.md

## 2023-07-15 - Seed Inventory & Pricing Management System
- Created database schema for tracking seed inventory and pricing data
- Implemented Filament PHP resources for Seed Cultivars and Seed Variations
- Built JSON import system for processing web scraper data with integration to existing consumables
- Added price history tracking with visualization tools
- Created Seed Reorder Advisor tool to help with purchasing decisions

## 2023-07-01 - Fixed seed price variations capture in data imports
- Improved the SeedScrapeImporter to properly handle and store price variations from uploaded JSON data
- Added better floating-point comparison for detecting price changes
- Created diagnostic tool for analyzing JSON seed data structure and processing

## 2025-05-27 - Fixed JSON field mapping for supplier seed scrape imports
- Enhanced the SeedScrapeImporter to handle different JSON structures from various suppliers
- Added support for "variations" array (Sprouting.com) in addition to "variants" array (DamSeeds)
- Added support for "size" field (Sprouting.com) in addition to "variant_title"/"title" fields
- Updated field mapping for stock status to handle "is_variation_in_stock" field in Sprouting.com data
- Improved field matching with flexible name mapping for more robust JSON importing

## 2025-05-27 - Fixed JSON field mapping for DamSeeds seed scrape imports
- Enhanced the SeedScrapeImporter to handle different field names in JSON from various suppliers
- Updated field mapping to recognize 'title' as 'variant_title' and 'is_in_stock' as 'is_variant_in_stock'
- Improved the TestSeedJsonStructure command to check for multiple field name variations
- Added better field matching with aliases for more flexible JSON importing
- Ensured proper capture of price variations from diverse JSON structures
