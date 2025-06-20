#!/bin/bash

echo "🧪 Running Order System Test Suite"
echo "=================================="

echo ""
echo "📋 1. Basic Order Tests..."
php artisan test tests/Unit/BasicOrderTest.php --stop-on-failure

echo ""
echo "🏭 2. Factory Tests..." 
php artisan test tests/Unit/FactoryTest.php --stop-on-failure

echo ""
echo "📦 3. Order Model Tests (Sample)..."
php artisan test --filter="it_can_be_created_with_basic_attributes" --stop-on-failure

echo ""
echo "📦 4. Order Model Tests (Sample 2)..."
php artisan test --filter="it_calculates_total_amount_correctly" --stop-on-failure

echo ""
echo "📦 5. Order Model Tests (Sample 3)..."
php artisan test --filter="it_correctly_identifies_recurring_templates" --stop-on-failure

echo ""
echo "✅ Test Summary Complete!"
echo "========================"
echo ""
echo "ℹ️  Note: Full test suite includes:"
echo "   • Unit/Models/OrderTest.php (22 tests)"
echo "   • Unit/Services/RecurringOrderServiceTest.php (13 tests)"
echo "   • Feature/Orders/RecurringOrderGenerationTest.php (12 tests)"
echo "   • Feature/Orders/InvoiceConversionTest.php (14 tests)"
echo "   • Feature/Orders/OrderWorkflowIntegrationTest.php (8 tests)"
echo "   • Feature/Orders/OrderEdgeCasesTest.php (17 tests)"
echo "   • Feature/Console/ProcessRecurringOrdersCommandTest.php (9 tests)"
echo ""
echo "📊 Total: ~95 comprehensive order system tests"
echo ""
echo "⚠️  Some tests require database schema updates to run fully"
echo "   (missing inventory tables, crop_status/fulfillment_status columns)"