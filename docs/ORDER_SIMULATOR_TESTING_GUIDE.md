# Order Simulator Testing Guide

## Overview

This document describes the comprehensive test suite created for the Order Simulator feature in the Catapult application. The test suite ensures reliability, prevents regressions, and validates all edge cases related to product ordering, pricing calculations, and unit measurements.

## Test Structure

### 1. Unit Tests

**File**: `tests/Unit/Services/OrderCalculationServiceTest.php`

**Purpose**: Tests the core calculation logic in isolation

**Key Test Areas**:
- Single variety product calculations
- Product mix calculations with percentage-based distributions
- Mixed single and mix product scenarios
- Empty and invalid input handling
- Decimal precision and rounding
- Variety aggregation from multiple products
- Error handling for non-existent products/variations

**Critical Test Cases**:
- `it_calculates_variety_requirements_for_single_variety_products`
- `it_calculates_variety_requirements_for_product_mix`
- `it_handles_mixed_single_and_mix_products`
- `it_properly_rounds_decimal_weights`
- `it_aggregates_same_variety_from_multiple_products`

### 2. Feature Tests

**File**: `tests/Feature/OrderSimulatorTest.php`

**Purpose**: Tests the complete user interface and workflow

**Key Test Areas**:
- Page accessibility and display
- Product filtering (active products, retail pricing only)
- Quantity input and session persistence
- Hide/restore functionality
- Calculation workflow
- Clear functionality
- Session data integrity

**Critical Test Cases**:
- `it_shows_active_retail_products_with_price_variations`
- `it_filters_out_wholesale_and_live_tray_variations`
- `it_allows_updating_quantities_for_products`
- `it_calculates_requirements_successfully`
- `it_can_hide_and_restore_rows`

### 3. Integration Tests

**File**: `tests/Integration/OrderSimulatorPricingIntegrationTest.php`

**Purpose**: Tests integration between pricing system, measurements, and calculations

**Key Test Areas**:
- Product pricing tier integration
- Unit measurement conversions (grams, kg, lbs, oz)
- Packaging type integration
- Complex product mix scenarios
- Precision maintenance with fractional percentages
- Unit conversion error prevention

**Critical Test Cases**:
- `it_integrates_with_product_pricing_system`
- `it_handles_unit_measurement_conversions_correctly`
- `it_prevents_unit_conversion_errors`
- `it_handles_complex_product_mix_with_different_measurements`
- `it_maintains_precision_with_fractional_percentages`

### 4. Edge Case Tests

**File**: `tests/Feature/OrderSimulatorEdgeCasesTest.php`

**Purpose**: Tests boundary conditions and error scenarios

**Key Test Areas**:
- Extremely large quantities
- Very small or zero fill weights
- Null values handling
- Product mix edge cases
- Concurrent access scenarios
- Memory intensive calculations
- Special characters in names
- Circular references

**Critical Test Cases**:
- `it_handles_extremely_large_quantities`
- `it_handles_zero_fill_weights_gracefully`
- `it_handles_product_mix_with_zero_percentage_components`
- `it_handles_memory_intensive_calculations`
- `it_validates_session_data_integrity`

### 5. Bulk Pricing Tests

**File**: `tests/Feature/OrderSimulatorBulkPricingTest.php`

**Purpose**: Tests bulk pricing tiers and live tray filtering

**Key Test Areas**:
- Wholesale variation filtering
- Live tray variation filtering
- Bulk pricing tier calculations
- Weight-based pricing units
- Mixed pricing scenario handling
- UI display of bulk pricing

**Critical Test Cases**:
- `it_excludes_wholesale_pricing_variations_from_display`
- `it_excludes_live_tray_variations_from_display`
- `it_handles_bulk_pricing_tiers_correctly`
- `it_handles_weight_based_pricing_units_in_bulk`
- `it_handles_mixed_bulk_and_retail_pricing_scenarios`

## Test Helpers

**File**: `tests/TestHelpers/OrderSimulatorTestHelpers.php`

**Purpose**: Provides reusable test utilities

**Key Functions**:
- `createSingleVarietyProduct()`: Creates complete single variety products
- `createMixProduct()`: Creates product mixes with varieties and percentages
- `createBulkPricingVariations()`: Creates standard bulk pricing tiers
- `generateOrderItems()`: Converts product variations to order item format
- `assertVarietyTotals()`: Validates calculation results
- `convertWeight()`: Handles unit conversions for testing

## Running the Tests

### Run All Order Simulator Tests
```bash
php artisan test --filter OrderSimulator
```

### Run Specific Test Files
```bash
# Unit tests
php artisan test tests/Unit/Services/OrderCalculationServiceTest.php

# Feature tests
php artisan test tests/Feature/OrderSimulatorTest.php

# Integration tests
php artisan test tests/Integration/OrderSimulatorPricingIntegrationTest.php

# Edge case tests
php artisan test tests/Feature/OrderSimulatorEdgeCasesTest.php

# Bulk pricing tests
php artisan test tests/Feature/OrderSimulatorBulkPricingTest.php
```

### Run Tests with Coverage
```bash
php artisan test --coverage --min=80
```

## Key Features Tested

### 1. Product Filtering
- ✅ Only active products are displayed
- ✅ Only retail pricing variations are shown
- ✅ Wholesale variations are filtered out (case-insensitive)
- ✅ Live tray variations are filtered out (case-insensitive)
- ✅ Inactive variations are excluded

### 2. Quantity Management
- ✅ Quantity input and validation
- ✅ Session persistence of quantities
- ✅ Zero quantity handling
- ✅ Large quantity support
- ✅ Quantity removal when hiding rows

### 3. Calculation Engine
- ✅ Single variety product calculations
- ✅ Product mix percentage-based calculations
- ✅ Weight aggregation across varieties
- ✅ Proper rounding to 2 decimal places
- ✅ Unit conversion accuracy
- ✅ Mix validation (percentages = 100%)

### 4. Unit Measurements
- ✅ Gram, kilogram, pound, and ounce conversions
- ✅ Fill weight calculations
- ✅ Pricing unit handling
- ✅ Unit conversion error prevention
- ✅ Display unit formatting

### 5. Error Handling
- ✅ Invalid order items
- ✅ Non-existent products/variations
- ✅ Null and zero values
- ✅ Malformed session data
- ✅ Concurrent access scenarios
- ✅ Database integrity

### 6. Bulk Pricing
- ✅ Multiple bulk pricing tiers
- ✅ Weight-based pricing units
- ✅ Bulk mix product calculations
- ✅ Mixed retail and bulk scenarios
- ✅ Live tray exclusion

## Test Data Patterns

### Realistic Test Scenarios
The tests use realistic agricultural product data:
- **Basil Genovese Seeds**: Single variety with retail and bulk options
- **Cherry Tomato Seeds**: Single variety with multiple container sizes
- **Premium Salad Mix**: 3-variety mix (Buttercrunch 40%, Red Oak Leaf 35%, Arugula 25%)

### Weight Standards
- **4oz Container**: 113.4g
- **8oz Container**: 226.8g
- **1lb Container**: 453.6g
- **5lb Bulk**: 2,268g
- **10lb Bulk**: 4,536g

### Pricing Tiers
- **Retail**: Standard consumer pricing
- **Bulk**: Volume pricing with better per-unit rates
- **Wholesale**: Business pricing (filtered from Order Simulator)

## Quality Assurance

### Test Coverage Requirements
- Minimum 90% code coverage for Order Simulator components
- All public methods must have test coverage
- All business logic paths must be tested
- Edge cases and error conditions must be covered

### Performance Benchmarks
- Calculations with 50+ varieties: < 1 second
- UI updates with 100+ products: < 2 seconds
- Memory usage for large calculations: < 128MB
- Session data handling: < 100ms

### Data Integrity Checks
- Percentage totals in mixes = 100% ± 0.01%
- Weight calculations accurate to 0.01g
- Pricing calculations accurate to $0.01
- Session data consistency maintained

## Maintenance Guidelines

### Adding New Tests
1. Follow existing test patterns and naming conventions
2. Use the TestHelpers for common setup operations
3. Include both positive and negative test cases
4. Test edge cases and boundary conditions
5. Add realistic test data that reflects actual use cases

### Updating Tests for New Features
1. Update relevant test files when modifying Order Simulator logic
2. Add new test files for significant new features
3. Update TestHelpers when adding new test utilities
4. Maintain test documentation

### Performance Testing
1. Monitor test execution time
2. Profile memory usage for large datasets
3. Test concurrent access scenarios
4. Validate session handling under load

## Dependencies

### Required Models
- `Product`: Core product information
- `PriceVariation`: Pricing and packaging details
- `ProductMix`: Mix composition and percentages
- `MasterSeedCatalog`: Variety information
- `Category`: Product categorization
- `PackagingType`: Container specifications

### Required Services
- `OrderCalculationService`: Core calculation logic
- Laravel Session: Data persistence
- Livewire: UI component testing

### Test Dependencies
- PHPUnit: Test framework
- Laravel TestCase: Framework integration
- Livewire Testing: Component testing
- Factory classes: Test data generation
- RefreshDatabase: Database isolation

## Troubleshooting

### Common Test Failures

**"No Products Selected" Warning**
- Check that quantities are set > 0
- Verify products are active
- Ensure price variations are active and retail type

**Calculation Mismatches**
- Verify fill_weight values are correct
- Check percentage totals in mixes = 100%
- Ensure proper rounding (2 decimal places)

**UI Test Failures**
- Confirm product filtering logic
- Check Livewire component state
- Verify session data persistence

**Memory or Performance Issues**
- Review test data size
- Check for memory leaks in calculations
- Profile database queries

### Debugging Tips
1. Use `dd()` to inspect calculation results
2. Check database state with `dump()`
3. Verify session contents
4. Review Livewire component state
5. Monitor query count and performance

## Future Enhancements

### Planned Test Additions
- Multi-user concurrent testing
- API endpoint testing
- Mobile UI testing
- Accessibility testing
- Performance regression testing

### Test Automation
- Continuous integration pipelines
- Automated test execution on code changes
- Performance monitoring and alerts
- Test result reporting and analytics