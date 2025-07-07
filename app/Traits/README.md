# Laravel Traits for Common Model Behaviors

This directory contains reusable traits that provide common functionality across multiple models in the Catapult application.

## Available Traits

### 1. HasActiveStatus

Provides functionality for models with active/inactive status fields.

**Features:**
- Automatic detection of `is_active` or `active` field names
- Scopes: `active()`, `inactive()`
- Methods: `isActive()`, `isInactive()`, `activate()`, `deactivate()`, `toggleActive()`
- Attributes: `status`, `status_badge`

**Usage:**
```php
use App\Traits\HasActiveStatus;

class MyModel extends Model
{
    use HasActiveStatus;
}

// Query active records
$activeItems = MyModel::active()->get();

// Toggle status
$model->toggleActive();
```

### 2. HasTimestamps

Enhances Laravel's built-in timestamp functionality with additional methods and scopes.

**Features:**
- Scopes: `createdToday()`, `createdYesterday()`, `createdInLastDays()`, `updatedInLastDays()`, `createdBetween()`, `updatedBetween()`, `recentlyUpdated()`
- Attributes: `age_in_days`, `time_since_update`, `time_since_creation`
- Methods: `wasCreatedToday()`, `wasUpdatedToday()`, `wasCreatedWithinDays()`, `wasUpdatedWithinDays()`

**Usage:**
```php
use App\Traits\HasTimestamps;

class MyModel extends Model
{
    use HasTimestamps;
}

// Get records created in the last 7 days
$recentRecords = MyModel::createdInLastDays(7)->get();

// Check if record was created today
if ($model->wasCreatedToday()) {
    // ...
}
```

### 3. HasSupplier

Provides supplier relationship functionality for models with a `supplier_id` field.

**Features:**
- Automatic supplier relationship
- Scopes: `fromSupplier()`, `fromActiveSuppliers()`, `fromSupplierType()`, `notFromSupplier()`
- Methods: `hasSupplier()`, `isFromSupplier()`, `hasActiveSupplier()`, `setSupplierByName()`
- Attributes: `supplier_name`, `supplier_type_code`, `supplier_type_name`

**Usage:**
```php
use App\Traits\HasSupplier;

class MyModel extends Model
{
    use HasSupplier;
}

// Query items from a specific supplier
$items = MyModel::fromSupplier($supplier)->get();

// Create with supplier by name
$model->setSupplierByName('New Supplier Name');
```

### 4. HasCostInformation

Manages cost and price fields with calculations and formatting.

**Features:**
- Automatic detection of cost/price fields
- Scopes: `withCost()`, `withoutCost()`, `orderByCost()`, `orderByCostDesc()`, `costBetween()`
- Methods: `getCost()`, `getPrice()`, `setCost()`, `setPrice()`, `hasCost()`, `hasPrice()`, `isProfitable()`
- Attributes: `formatted_cost`, `formatted_price`, `profit_margin`, `markup_percentage`, `profit_amount`
- Calculations: `calculateTotalValue()`, `getPriceWithDiscount()`, `getCostWithMarkup()`

**Usage:**
```php
use App\Traits\HasCostInformation;

class MyModel extends Model
{
    use HasCostInformation;
}

// Query items with cost information
$itemsWithCost = MyModel::withCost()->get();

// Get formatted price
echo $model->formatted_cost; // $10.50

// Calculate profit margin
echo $model->profit_margin . '%'; // 35.5%
```

## Models Using These Traits

The following models have been configured to use these traits:

1. **Consumable**: HasActiveStatus, HasSupplier, HasCostInformation, HasTimestamps
2. **Supplier**: HasActiveStatus, HasTimestamps
3. **Recipe**: HasActiveStatus, HasTimestamps
4. **Product**: HasActiveStatus, HasCostInformation, HasTimestamps (overrides `getActiveFieldName()` to use 'active')
5. **SeedEntry**: HasActiveStatus, HasSupplier, HasTimestamps
6. **Crop**: HasTimestamps

## Implementation Notes

1. **Field Name Detection**: Traits automatically detect the appropriate field names (e.g., `is_active` vs `active`)
2. **Method Conflicts**: Some methods have been renamed to avoid conflicts with Laravel's built-in methods (e.g., `touchTimestampsQuietly()` instead of `touchQuietly()`)
3. **Initialization**: Traits use the `initializeTraitName()` pattern to automatically add fields to `$fillable` and `$casts` arrays
4. **Override Support**: Models can override trait methods when needed (e.g., Product overrides `getActiveFieldName()`)

## Testing

Unit tests are available in `tests/Unit/Traits/TraitTest.php` to verify trait functionality.

Run tests with:
```bash
php artisan test tests/Unit/Traits/TraitTest.php
```

## Future Enhancements

Consider adding these additional traits:
- `HasSlug`: For URL-friendly identifiers
- `HasMetadata`: For JSON metadata fields
- `HasOwner`: For user ownership relationships
- `HasImages`: For models with image uploads
- `HasVersioning`: For models requiring version control