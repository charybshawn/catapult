# Catapult v2 Changelog

This document tracks all significant changes to the Catapult v2 project.

## [Unreleased]

### Added
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
