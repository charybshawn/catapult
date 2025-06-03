<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ConsumableCalculatorService;
use App\Models\Consumable;
use App\Models\SeedVariety;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsumableCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsumableCalculatorService $calculatorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculatorService = new ConsumableCalculatorService();
    }

    public function test_calculateAvailableStock(): void
    {
        $consumable = Consumable::factory()->create([
            'initial_stock' => 100,
            'consumed_quantity' => 30,
        ]);

        $result = $this->calculatorService->calculateAvailableStock($consumable);

        $this->assertEquals(70.0, $result);
    }

    public function test_calculateAvailableStock_never_goes_below_zero(): void
    {
        $consumable = Consumable::factory()->create([
            'initial_stock' => 50,
            'consumed_quantity' => 80,
        ]);

        $result = $this->calculatorService->calculateAvailableStock($consumable);

        $this->assertEquals(0.0, $result);
    }

    public function test_calculateTotalQuantity_with_quantity_per_unit(): void
    {
        $consumable = Consumable::factory()->create([
            'initial_stock' => 10,
            'consumed_quantity' => 2,
            'quantity_per_unit' => 5.0,
        ]);

        $result = $this->calculatorService->calculateTotalQuantity($consumable);

        $this->assertEquals(40.0, $result); // 8 * 5.0
    }

    public function test_calculateTotalQuantity_without_quantity_per_unit(): void
    {
        $consumable = Consumable::factory()->create([
            'initial_stock' => 10,
            'consumed_quantity' => 3,
            'quantity_per_unit' => 0,
        ]);

        $result = $this->calculatorService->calculateTotalQuantity($consumable);

        $this->assertEquals(0.0, $result); // No quantity_per_unit means calculation returns 0
    }

    public function test_calculateCostPerGram_with_valid_data(): void
    {
        $consumable = Consumable::factory()->create([
            'cost_per_unit' => 10.0,
            'quantity_per_unit' => 1000.0,
            'quantity_unit' => 'g',
        ]);

        $result = $this->calculatorService->calculateCostPerGram($consumable);

        $this->assertEquals(0.01, $result); // 10.0 / 1000.0
    }

    public function test_calculateCostPerGram_with_kg_unit(): void
    {
        $consumable = Consumable::factory()->create([
            'cost_per_unit' => 15.0,
            'quantity_per_unit' => 2.0,
            'quantity_unit' => 'kg',
        ]);

        $result = $this->calculatorService->calculateCostPerGram($consumable);

        $this->assertEquals(0.0075, $result); // 15.0 / (2.0 * 1000)
    }

    public function test_calculateCostPerGram_returns_null_without_cost(): void
    {
        $consumable = Consumable::factory()->create([
            'cost_per_unit' => 0,
            'quantity_per_unit' => 100.0,
            'quantity_unit' => 'g',
        ]);

        $result = $this->calculatorService->calculateCostPerGram($consumable);

        $this->assertEquals(0.0, $result); // Returns 0 when cost_per_unit is 0
    }

    public function test_calculateUsageRate(): void
    {
        Carbon::setTestNow('2023-01-10 10:00:00');
        
        $consumable = Consumable::factory()->create([
            'consumed_quantity' => 20.0,
            'created_at' => Carbon::parse('2023-01-01 10:00:00'),
        ]);

        $result = $this->calculatorService->calculateUsageRate($consumable);

        $this->assertEquals(20.0 / 9, $result); // 20 / 9 days

        Carbon::setTestNow(); // Reset
    }

    public function test_calculateDaysUntilRestock(): void
    {
        Carbon::setTestNow('2023-01-10 10:00:00');

        $consumable = Consumable::factory()->create([
            'initial_stock' => 100,
            'consumed_quantity' => 20,
            'restock_threshold' => 30,
            'created_at' => Carbon::parse('2023-01-01 10:00:00'),
        ]);

        $result = $this->calculatorService->calculateDaysUntilRestock($consumable);

        // Available stock: 80, threshold: 30, stock until reorder: 50
        // Usage rate: 20/9 ≈ 2.22 per day
        // Days until restock: 50/2.22 ≈ 23 days
        $this->assertGreaterThan(20, $result);

        Carbon::setTestNow(); // Reset
    }

    public function test_formatDisplayName_for_seed_variety(): void
    {
        $seedVariety = SeedVariety::factory()->create([
            'name' => 'Basil - Genovese',
        ]);

        $consumable = Consumable::factory()->create([
            'type' => 'seed',
            'name' => 'Generic Name',
            'seed_variety_id' => $seedVariety->id,
        ]);
        
        // Mock the relationship
        $consumable->setRelation('seedVariety', $seedVariety);

        $result = $this->calculatorService->formatDisplayName($consumable);

        $this->assertEquals('Basil - Genovese', $result);
    }

    public function test_formatDisplayName_for_other_types(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'name' => 'Organic Potting Soil',
        ]);

        $result = $this->calculatorService->formatDisplayName($consumable);

        $this->assertEquals('Organic Potting Soil', $result);
    }

    public function test_getValidMeasurementUnits_returns_expected_units(): void
    {
        $result = $this->calculatorService->getValidMeasurementUnits();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('g', $result);
        $this->assertArrayHasKey('kg', $result);
        $this->assertEquals('Grams', $result['g']);
        $this->assertEquals('Kilograms', $result['kg']);
    }

    public function test_getValidTypes_returns_expected_types(): void
    {
        $result = $this->calculatorService->getValidTypes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('seed', $result);
        $this->assertArrayHasKey('soil', $result);
        $this->assertEquals('Seed', $result['seed']);
        $this->assertEquals('Soil', $result['soil']);
    }

    public function test_getValidUnitTypes_returns_expected_units(): void
    {
        $result = $this->calculatorService->getValidUnitTypes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('pieces', $result);
        $this->assertArrayHasKey('bags', $result);
        $this->assertEquals('Pieces', $result['pieces']);
        $this->assertEquals('Bags', $result['bags']);
    }

    public function test_calculateReorderSuggestion(): void
    {
        Carbon::setTestNow('2023-01-10 10:00:00');

        $consumable = Consumable::factory()->create([
            'initial_stock' => 100,
            'consumed_quantity' => 80,
            'restock_threshold' => 30,
            'restock_quantity' => 50,
            'created_at' => Carbon::parse('2023-01-01 10:00:00'),
        ]);

        $result = $this->calculatorService->calculateReorderSuggestion($consumable);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('needs_reorder', $result);
        $this->assertArrayHasKey('current_stock', $result);
        $this->assertArrayHasKey('urgency', $result);
        $this->assertTrue($result['needs_reorder']); // 20 <= 30
        $this->assertEquals(20.0, $result['current_stock']);

        Carbon::setTestNow(); // Reset
    }
}