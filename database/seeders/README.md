# Database Seeders Organization

This directory contains database seeders organized into logical subdirectories for better maintainability and clarity.

## Directory Structure

### 📦 Core/
Essential system seeders that should run first:
- **FilamentPermissionSeeder** - Sets up permissions for Filament admin panel
- **RoleSeeder** - Creates user roles (admin, manager, user)
- **CustomerRoleSeeder** - Sets up customer-specific roles and permissions
- **FilamentAdminUserSeeder** - Creates default admin users

### 🔍 Lookup/
Reference and lookup data seeders:
- **ConsumableTypeSeeder** & **ConsumableUnitSeeder** - Consumable reference data
- **CropPlanStatusSeeder** - Crop plan workflow statuses (draft, active, completed, cancelled)
- **OrderStatusSeeder** & **OrderTypeSeeder** - Order management statuses and types
- **PaymentStatusSeeder** & **PaymentMethodSeeder** - Payment-related reference data
- **TaskTypeSeeder** - Task classification types
- **SupplierTypeSeeder** - Supplier categorization
- And other status/type reference tables

### 📊 Data/
Product data and large import seeders:
- **MasterSeedCatalogTableSeeder** - Main seed catalog data
- **CurrentSeedEntryDataSeeder** - Current seed inventory entries
- **CurrentSeedConsumableDataSeeder** - Current consumable inventory
- **PackagingSeeder** & **PackagingTypesTableSeeder** - Packaging data
- **PriceVariationsTableSeeder** - Product pricing data
- **ProductsTableSeeder** - Product catalog
- **RealWorldRecipesSeeder** - Recipe data

### 🛠️ Development/
Development and testing data:
- **DevelopmentSeeder** - Development environment test data

### 🗄️ Legacy/
Old and deprecated seeders (kept for reference):
- Historical table seeders that may no longer be in active use
- Legacy user and permission seeders
- Old data import seeders

## Usage

### Running Individual Seeders
```bash
# Run a specific seeder
php artisan db:seed --class=Database\\Seeders\\Core\\RoleSeeder

# Run all seeders (uses DatabaseSeeder.php)
php artisan db:seed
```

### Seeder Dependencies
Seeders should generally be run in this order:
1. **Core** - Sets up permissions and roles
2. **Lookup** - Populates reference tables
3. **Data** - Imports main application data
4. **Development** - Adds test data (dev environments only)

## Adding New Seeders

When creating new seeders, place them in the appropriate directory:

1. **Core**: System-critical seeders (roles, permissions, admin users)
2. **Lookup**: Reference/status/type tables
3. **Data**: Business data and large imports
4. **Development**: Test/development data only
5. **Legacy**: Deprecated seeders (avoid adding new ones here)

Remember to:
- Use the correct namespace: `Database\Seeders\{Directory}\`
- Update `DatabaseSeeder.php` if the seeder should run by default
- Add appropriate use statements for the new namespace