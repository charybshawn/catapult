# Order System Testing Guide

This document provides comprehensive information about testing the order system, including orders, recurring orders, invoices, and related functionality.

## Test Structure

### Factory Files
```
database/factories/
├── OrderFactory.php           # Order model factory with states
├── OrderItemFactory.php       # Order items factory
├── PriceVariationFactory.php  # Price variations factory  
├── InvoiceFactory.php         # Invoice factory
└── UserFactory.php           # User factory (existing)
```

### Test Files
```
tests/
├── Unit/
│   ├── Models/
│   │   └── OrderTest.php                    # Order model unit tests
│   └── Services/
│       └── RecurringOrderServiceTest.php    # Service unit tests
└── Feature/
    ├── Orders/
    │   ├── RecurringOrderGenerationTest.php # Recurring order functionality
    │   ├── InvoiceConversionTest.php        # Invoice generation & billing
    │   ├── OrderWorkflowIntegrationTest.php # End-to-end workflows
    │   └── OrderEdgeCasesTest.php           # Edge cases & error handling
    └── Console/
        └── ProcessRecurringOrdersCommandTest.php # Command testing
```

### Test Helpers
```
tests/TestHelpers/
└── OrderTestHelpers.php       # Reusable test utilities
```

## Running Tests

### Run All Order Tests
```bash
# Run all order-related tests
php artisan test --testsuite=Feature --filter=Order

# Run specific test categories
php artisan test tests/Unit/Models/OrderTest.php
php artisan test tests/Feature/Orders/
php artisan test tests/Feature/Console/ProcessRecurringOrdersCommandTest.php
```

### Run Individual Test Files
```bash
# Order model tests
php artisan test tests/Unit/Models/OrderTest.php

# Recurring order functionality
php artisan test tests/Feature/Orders/RecurringOrderGenerationTest.php

# Invoice conversion tests
php artisan test tests/Feature/Orders/InvoiceConversionTest.php

# Integration workflows
php artisan test tests/Feature/Orders/OrderWorkflowIntegrationTest.php

# Edge cases
php artisan test tests/Feature/Orders/OrderEdgeCasesTest.php

# Service tests
php artisan test tests/Unit/Services/RecurringOrderServiceTest.php

# Command tests
php artisan test tests/Feature/Console/ProcessRecurringOrdersCommandTest.php
```

### Run Specific Test Methods
```bash
# Run specific test method
php artisan test --filter=it_generates_next_recurring_order_correctly

# Run tests with specific pattern
php artisan test --filter=recurring
php artisan test --filter=invoice
php artisan test --filter=b2b
```

## Test Coverage Areas

### 1. Order Model Tests (`OrderTest.php`)
- ✅ Order creation and attribute validation
- ✅ Status transitions and automatic status setting
- ✅ Total amount calculations
- ✅ Payment tracking and balance calculations
- ✅ Customer type inheritance and display
- ✅ Recurring order identification methods
- ✅ Next generation date calculations
- ✅ Billing requirement checks

### 2. Recurring Order Generation (`RecurringOrderGenerationTest.php`)
- ✅ Order generation from templates
- ✅ Price recalculation for different customer types
- ✅ Date calculation for different frequencies
- ✅ Duplicate prevention for same delivery dates
- ✅ Template deactivation when expired
- ✅ Order item and packaging copying
- ✅ B2B recurring order handling

### 3. Invoice Conversion (`InvoiceConversionTest.php`)
- ✅ Invoice creation for immediate orders
- ✅ Consolidated billing for B2B customers
- ✅ Farmers market invoice bypassing
- ✅ Billing period calculations
- ✅ Invoice status tracking
- ✅ Payment tracking and partial payments
- ✅ Overdue invoice handling

### 4. Integration Workflows (`OrderWorkflowIntegrationTest.php`)
- ✅ Complete order lifecycle (creation → completion)
- ✅ Recurring order workflow (template → generation → processing)
- ✅ B2B consolidated billing workflow
- ✅ Farmers market order workflow
- ✅ Status transitions and combined status display
- ✅ Command integration testing
- ✅ Price recalculation during generation

### 5. Edge Cases (`OrderEdgeCasesTest.php`)
- ✅ Zero and very large quantities
- ✅ Deleted products and users
- ✅ Concurrent generation prevention
- ✅ Extreme dates and leap years
- ✅ Negative prices and decimal quantities
- ✅ Unicode characters in notes
- ✅ Precision and rounding edge cases

### 6. Service Tests (`RecurringOrderServiceTest.php`)
- ✅ Active order filtering
- ✅ Generation timing logic
- ✅ Template deactivation logic
- ✅ Manual order generation
- ✅ Template pausing and resuming
- ✅ Statistics calculations
- ✅ Error handling

### 7. Command Tests (`ProcessRecurringOrdersCommandTest.php`)
- ✅ Successful processing output
- ✅ Dry-run functionality
- ✅ Error reporting
- ✅ Statistics display
- ✅ Multiple frequency handling

## Factory Usage Examples

### Creating Orders
```php
// Basic order
$order = Order::factory()->create();

// Recurring order template
$template = Order::factory()->recurring()->create();

// B2B recurring order
$b2bOrder = Order::factory()->b2bRecurring()->create();

// Farmers market order
$farmersOrder = Order::factory()->farmersMarket()->create();

// Order with specific status
$completedOrder = Order::factory()->withStatus('completed')->create();

// Wholesale customer order
$wholesaleOrder = Order::factory()->wholesale()->create();
```

### Creating Order Items
```php
// Basic order item
$item = OrderItem::factory()->create();

// Item with specific quantity and price
$item = OrderItem::factory()
    ->withQuantity(5)
    ->withPrice(12.50)
    ->create();

// Bulk item (for weight-based products)
$bulkItem = OrderItem::factory()->bulk()->create();

// Item for specific order and product
$item = OrderItem::factory()
    ->forOrder($order)
    ->forProduct($product)
    ->create();
```

### Creating Invoices
```php
// Basic invoice
$invoice = Invoice::factory()->create();

// Invoice for specific order
$invoice = Invoice::factory()->forOrder($order)->create();

// Paid invoice
$paidInvoice = Invoice::factory()->paid()->create();

// Consolidated invoice
$consolidatedInvoice = Invoice::factory()->consolidated()->create();

// Overdue invoice
$overdueInvoice = Invoice::factory()->overdue()->create();
```

## Test Helpers Usage

Include the test helpers trait in your test classes:

```php
use Tests\TestHelpers\OrderTestHelpers;

class MyOrderTest extends TestCase
{
    use RefreshDatabase, OrderTestHelpers;

    /** @test */
    public function it_creates_complete_order()
    {
        $order = $this->createCompleteOrder([
            'order_type' => 'website_immediate',
        ], [
            ['quantity' => 3, 'price' => 15.00],
            ['quantity' => 1, 'price' => 8.50],
        ]);

        $this->assertOrderFinancialState($order, 53.50);
    }
}
```

## Common Test Patterns

### Testing Recurring Order Generation
```php
/** @test */
public function it_generates_recurring_order()
{
    $template = $this->createRecurringTemplate([
        'recurring_frequency' => 'weekly',
    ]);

    $newOrder = $template->generateNextRecurringOrder();

    $this->assertGeneratedOrder($newOrder, $template);
    $this->assertOrderItemsCopied($template, $newOrder);
}
```

### Testing B2B Consolidated Billing
```php
/** @test */
public function it_handles_consolidated_billing()
{
    $scenario = $this->createConsolidatedBillingScenario(3);
    
    $consolidatedInvoice = Invoice::factory()->create([
        'user_id' => $scenario['customer']->id,
        'amount' => $scenario['total_amount'],
        'is_consolidated' => true,
        'consolidated_order_count' => 3,
    ]);

    $this->assertTrue($consolidatedInvoice->is_consolidated);
    $this->assertEquals(3, $consolidatedInvoice->consolidated_order_count);
}
```

### Testing Order Workflows
```php
/** @test */
public function it_completes_full_workflow()
{
    $order = $this->createCompleteOrder();
    $this->payOrder($order);
    $completedOrder = $this->progressOrderThroughWorkflow($order);

    $this->assertEquals('completed', $completedOrder->status);
    $this->assertTrue($completedOrder->isPaid());
}
```

## Database Considerations

### Using RefreshDatabase
All tests use `RefreshDatabase` trait to ensure clean state:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    
    // Tests run with fresh database each time
}
```

### Test Database Configuration
Ensure your `.env.testing` has proper database configuration:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Or use a dedicated test database:

```env
DB_CONNECTION=mysql
DB_DATABASE=catapult_test
```

## Running Tests in CI/CD

### GitHub Actions Example
```yaml
- name: Run Order Tests
  run: |
    php artisan test tests/Unit/Models/OrderTest.php
    php artisan test tests/Feature/Orders/
    php artisan test tests/Unit/Services/RecurringOrderServiceTest.php
```

### Coverage Reports
```bash
# Generate coverage report for order tests
php artisan test --coverage --coverage-html coverage/orders tests/Feature/Orders/ tests/Unit/Models/OrderTest.php
```

## Debugging Tests

### Verbose Output
```bash
php artisan test --verbose tests/Feature/Orders/RecurringOrderGenerationTest.php
```

### Debug Specific Test
```bash
php artisan test --filter=it_generates_next_recurring_order_correctly --verbose
```

### Database Inspection
Add debugging in tests to inspect database state:

```php
/** @test */
public function it_debugs_order_state()
{
    $order = $this->createCompleteOrder();
    
    // Debug output
    dump($order->toArray());
    dump($order->orderItems->toArray());
    
    // Continue test...
}
```

## Best Practices

1. **Use Factories**: Always use factories instead of manual model creation
2. **Test Edge Cases**: Include tests for boundary conditions and error states
3. **Use Helpers**: Leverage the test helpers for common scenarios
4. **Isolate Tests**: Each test should be independent and not rely on others
5. **Clear Assertions**: Use descriptive assertion messages
6. **Mock External Services**: Mock third-party services and APIs
7. **Test Workflows**: Include end-to-end integration tests
8. **Performance**: Consider performance implications of complex test scenarios

## Troubleshooting

### Common Issues

1. **Factory Relationship Errors**: Ensure related models exist before creating dependent models
2. **Date/Time Issues**: Use Carbon::setTestNow() for predictable time-based tests
3. **Database Constraints**: Check foreign key constraints in test database
4. **Memory Issues**: Use `--stop-on-failure` for large test suites

### Debugging Commands
```bash
# Check test database
php artisan migrate:status --env=testing

# Clear test cache
php artisan config:clear --env=testing
php artisan cache:clear --env=testing

# Regenerate autoloader
composer dump-autoload
```