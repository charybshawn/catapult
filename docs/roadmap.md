# Microgreens Farm Backend Roadmap

## Overview
A Laravel-based backend for my microgreens farm, optimized for tablet use with a Filament v3 dashboard. Prioritizes core operations: Recipes (growth procedures), Grow tracking (trays/stages), and Seed Inventory (stock levels). Supports Wednesday harvests and Thursday deliveries with 9-12 day cycles (14-21 for grow-to-order). Includes Square-inspired orders/payments (Stripe for retail, invoices for wholesale) and OAuth auth. Built in Cursor IDE with Claude 3.7.

## Goals
- Enable core operations: Track recipes, grow cycles (planting → harvest), and seed inventory.
- Align with physical process: Planting → Germination → Blackout → Light → Harvest (Wednesday) → Delivery (Thursday).
- Add enhancements (notifications, tasks) after core is stable.
- Deploy by June 2025.

## Timeline
### Phase 1: Setup & Core Models (March 15 - March 31, 2025)
- [x] Install Laravel, Filament v3, Socialite, Spatie Permission, Cashier
- [x] Install Laravel Breeze for authentication (required for Laravel 12)
- [x] Define schema:
  - `suppliers` (id, name, type [soil/seed/consumable], contact)
  - `seed_varieties` (id, name, crop_type, brand, supplier_id, germination_rate, notes)
  - `recipes` (id, name, supplier_soil_id, seed_variety_id, germination_days, blackout_days, light_days, expected_yield_grams)
  - `recipe_stages` (id, recipe_id, stage [planting/germination/blackout/light], notes)
  - `recipe_watering_schedule` (id, recipe_id, day_number, water_amount_ml, needs_liquid_fertilizer, notes)
  - `recipe_mixes` (recipe_id, component_recipe_id, percentage) *for mixes*
  - `crops` (id, recipe_id, order_id, tray_number, planted_at, current_stage [planting/germination/blackout/light/harvested], stage_updated_at, harvest_weight_grams, watering_suspended_at)
  - `inventory` (id, supplier_id, seed_variety_id, item_type [soil/seed/consumable], quantity, restock_threshold, restock_quantity)
  - `consumables` (id, name, type [packaging/label/other], current_stock, restock_threshold, restock_quantity)
  - `items` (id, recipe_id, name, expected_yield_grams, price)
  - `users` (id, name, email, phone) *extends for customers*
  - `orders` (id, user_id, harvest_date, delivery_date, status, customer_type [retail/wholesale])
  - `order_items` (id, order_id, item_id, quantity)
  - `payments` (id, order_id, amount, method [stripe/e-transfer/cash/invoice], status, paid_at)
  - `invoices` (id, order_id, amount, status, sent_at)
  - `settings` (id, key [liquid_fertilizer_ratio/packaging_types/label_types/etc], value, description)
  - `activity_log` (id, log_name, description, subject_type, subject_id, causer_type, causer_id, properties, created_at) *for auditing*
- [x] Create required models
- [x] Set up relationships
- [x] Install and configure Spatie Activity Log for auditing
- [x] Create database factories and seeders:
  - [x] Create persistent admin user seeder (survives migrate:fresh)
  - [x] Create persistent Filament admin user seeder
  - [x] Create factories for all models with realistic test data
  - [x] Create seeders for development environment testing
  - [x] Fix factory compatibility issues between models and migrations
  - [x] Successfully seed database with test data (users, suppliers, seed varieties, recipes, crops, etc.)
- [x] Use Claude 3.7 for migrations/models (Filament v3)

### Phase 2: Core Features - Recipes, Grow & Inventory (April 1 - April 15, 2025)
- [ ] Create `Dashboard` page with tabs: Grow, Inventory (others added later)
- [x] Build `RecipeResource`:
  - [x] Form: Basic fields plus stage-specific notes
  - [x] Detailed watering schedule with intuitive 3-column layout for different growth phases
  - [x] Added light_days field to database for accurate growth phase tracking
  - [x] Improved grow plan layout with better organization of inputs
  - [x] Enhanced watering schedule reactivity to grow plan changes
  - [ ] Support mixes via `recipe_mixes`
  - [ ] Seed variety selection and tracking
- [ ] Build Grow tab:
  - [ ] Show trays in stages with watering suspension status
  - [ ] Action: Advance stage, toggle watering suspension
- [ ] Create `CropResource`
- [ ] Build Inventory tab:
  - [ ] Track seeds by variety, soil, and consumables
  - [ ] Automatic deduction on use
  - [ ] Reorder alerts based on thresholds
  - [ ] Seed variety performance metrics

### Phase 3: Orders & Planning (April 16 - April 30, 2025)
- [ ] Create resources for items, users, orders
- [ ] Build weekly planning features:
  - [ ] Order aggregation
  - [ ] Mix calculations
  - [ ] Harvest checklist generation
  - [ ] Packing checklist generation
- [ ] Implement consumables tracking
  - [ ] Deduct from inventory on order completion
  - [ ] Alert on reorder thresholds

### Phase 4: Packaging & Payments (May 1 - May 15, 2025)
- [x] Add Packaging tab:
  - Schema: `packaging_types` (id, name, capacity_volume, volume_unit, cost_per_unit), `order_packaging` (order_id, packaging_type_id, quantity)
  - Delivery list for Thursday: Variety, qty, packaging type
- [x] Create `InvoiceResource`: Email invoices for wholesale (PDF, Square-like UX)
- [x] Enhance `PaymentResource`: Track all methods

### Phase 5: Enhancements & Deployment (May 16 - June 15, 2025)
- [ ] Create `SettingsResource`:
  - Manage fertilizer ratios
  - Configure consumable types and thresholds
  - Set default parameters
- [ ] Add daily tasks/notifications:
  - Schema: `tasks` (id, description, due_date, type, tray_id, completed_at)
  - `TaskGenerator` command: Stage moves, watering, soaking
  - Widget: "Today's Tasks"
- [ ] Add customizable notifications:
  - Schema: `notification_settings` (id, notification_type, threshold, message)
  - `NotificationSettingsResource`: Admin defines alerts (e.g., "Suspend watering 48h pre-harvest")
- [ ] Build Statistics tab: Trays/week, yield accuracy
- [ ] Configure Socialite (Google, Facebook) for OAuth authentication
- [x] Create User Management:
  - `UserResource` with role management
  - Admin can assign/change roles (admin, employee, customer)
  - Permission-based access control
- [x] Implement Activity Logging:
  - `ActivityLogResource` for admins to view all system activities
  - Track all CRUD operations on critical models
  - Filter by user, action type, and date range
- [ ] Test tablet UX (50 trays), deploy (Laravel Forge)

### Future Enhancements (Post-Launch)
- [ ] Home Assistant Integration
  - Environment monitoring
  - Data logging
  - Automated alerts

## Milestones
- [x] Setup and core models complete - March 31, 2025
- [ ] Core features (Recipes, Grow, Inventory) live - April 15, 2025
- [ ] Orders and Planting Calendar ready - April 30, 2025
- [x] Packaging and payments done - May 15, 2025
- [ ] Enhancements and deployment complete - June 15, 2025

## Current Status
- Planning refined as of March 15, 2025
- User management and activity logging implemented
- All models created with relationships and activity logging
- Laravel, Filament v3, Spatie packages, and Cashier installed
- Database factories and seeders created and tested
- Development environment fully seeded with test data (users, recipes, crops, etc.)
- Packaging and payments system implemented (Phase 4 complete)
  - Added PackagingType and OrderPackaging models
  - Created InvoiceResource with PDF generation
  - Enhanced payment tracking with multiple methods
  - Updated PackagingType to use volumetric measurements (ml, l, oz)
  - Removed capacity_grams field in favor of volumetric measurements
  - Implemented display_name accessor for clear identification of packaging sizes
  - Connected ConsumableResource with PackagingType for better inventory management
- Recipe resource partially implemented with improved features:
  - Intuitive 3-column layout for watering schedule management
  - Clear separation of growth phases in UI
  - Improved user experience for recipe creation workflow

## Tools
- **Cursor IDE**: Development environment
- **Claude 3.7**: Code generation (Filament v3)
- **Laravel**: Framework
- **Laravel Breeze**: Authentication scaffolding
- **Filament v3**: Dashboard/resources
- **Laravel Cashier**: Stripe
- **Socialite**: OAuth
- **Spatie Permission**: Roles and permissions
- **Spatie Activity Log**: Auditing system actions
- **Tailwind CSS**: Styling

## Notes
- Core priorities: Recipes (stage timings, mixes), Grow (tray tracking), Seed Inventory (stock)
- Orders drive planting for Wednesday harvests
- Enhancements (settings, tasks, notifications) deferred to Phase 5
- Laravel 12 requires Breeze for authentication scaffolding
- Packaging system now uses a two-tier approach:
  - `PackagingTypeResource`: Defines packaging specifications (e.g., "Clamshell - 24oz")
  - `ConsumableResource`: Tracks inventory of packaging items with reference to specifications

## Upcoming Features

### Short-term (Next 1-2 Sprints)
- **Crop Resource Implementation**: Create interface for tracking crops through growth stages
- **Dashboard with Grow Tab**: Implement main dashboard with grow tracking functionality
- **Inventory Management**: Build inventory tracking system for seeds, soil, and consumables
- **Price Variations Management Interface**: Implement UI for managing multiple price variations per product
  - Create a price variations table in the product edit form
  - Add bulk actions for price variations
  - Add filtering and sorting for price variations
- **Integrate Price Variations with Online Store**: Update the store front-end to use the new pricing system
  - Show appropriate prices based on customer type and quantity
  - Support quantity-based discounts at checkout