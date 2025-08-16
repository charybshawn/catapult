<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CalendarEventService;
use App\Services\CropPlanDashboardService;
use App\Models\CropPlan;
use App\Models\Order;
use App\Models\CropPlanStatus;
use App\Models\Customer;
use App\Models\Recipe;
use App\Models\MasterSeedCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalendarEventServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CalendarEventService $service;
    protected CropPlanDashboardService $dashboardService;
    protected CropPlanStatus $activeStatus;
    protected CropPlanStatus $draftStatus;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->dashboardService = new CropPlanDashboardService();
        $this->service = new CalendarEventService($this->dashboardService);
        
        // Create required status records directly
        $this->activeStatus = CropPlanStatus::create([
            'code' => 'active',
            'name' => 'Active',
            'description' => 'Active crop plan',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        
        $this->draftStatus = CropPlanStatus::create([
            'code' => 'draft',
            'name' => 'Draft',
            'description' => 'Draft crop plan',
            'color' => 'gray',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    public function test_it_generates_crop_planning_events()
    {
        $seedEntry = MasterSeedCatalog::factory()->create([
            'common_name' => 'Test Lettuce',
        ]);
        $recipe = Recipe::factory()->create([
            'seed_entry_id' => $seedEntry->id,
        ]);

        // Create order for delivery event
        $order = Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(10),
        ]);

        // Create crop plan for planting event
        $cropPlan = CropPlan::factory()->create([
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(5),
            'trays_needed' => 15,
        ]);

        $events = $this->service->getCropPlanningEvents();

        // Should have both delivery and planting events
        $this->assertCount(2, $events);

        // Check delivery event
        $deliveryEvent = collect($events)->firstWhere('extendedProps.type', 'delivery');
        $this->assertNotNull($deliveryEvent);
        $this->assertEquals('order-' . $order->id, $deliveryEvent['id']);
        $this->assertEquals("Delivery: Order #{$order->id}", $deliveryEvent['title']);
        $this->assertEquals($order->delivery_date->format('Y-m-d'), $deliveryEvent['start']);
        $this->assertEquals('#10b981', $deliveryEvent['backgroundColor']);

        // Check planting event
        $plantingEvent = collect($events)->firstWhere('extendedProps.type', 'planting');
        $this->assertNotNull($plantingEvent);
        $this->assertEquals('plant-' . $cropPlan->id, $plantingEvent['id']);
        $this->assertEquals('Plant: Test Lettuce', $plantingEvent['title']);
        $this->assertEquals($cropPlan->plant_by_date->format('Y-m-d'), $plantingEvent['start']);
        $this->assertEquals('#3b82f6', $plantingEvent['backgroundColor']); // active status color
    }

    public function test_it_applies_correct_status_colors()
    {
        $testCases = [
            'draft' => '#6b7280',
            'active' => '#3b82f6',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            'unknown' => '#6b7280',
        ];

        foreach ($testCases as $statusCode => $expectedColor) {
            $reflectionMethod = new \ReflectionMethod(CalendarEventService::class, 'getStatusColor');
            $reflectionMethod->setAccessible(true);
            
            $color = $reflectionMethod->invoke($this->service, $statusCode);
            $this->assertEquals($expectedColor, $color, "Color for status '{$statusCode}' should be '{$expectedColor}'");
        }
    }

    public function test_it_generates_events_by_specific_status()
    {
        $seedEntry = MasterSeedCatalog::factory()->create([
            'common_name' => 'Test Kale',
        ]);
        $recipe = Recipe::factory()->create([
            'seed_entry_id' => $seedEntry->id,
        ]);

        // Create active crop plan
        $activePlan = CropPlan::factory()->create([
            'recipe_id' => $recipe->id,
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(5),
        ]);

        // Create draft crop plan
        $draftPlan = CropPlan::factory()->create([
            'recipe_id' => $recipe->id,
            'status_id' => $this->draftStatus->id,
            'plant_by_date' => now()->addDays(7),
        ]);

        // Get only active status events
        $activeEvents = $this->service->getEventsByStatus('active');
        
        $this->assertCount(1, $activeEvents);
        $activeEvent = $activeEvents[0];
        $this->assertEquals('plant-' . $activePlan->id, $activeEvent['id']);
        $this->assertEquals('#3b82f6', $activeEvent['backgroundColor']);
        $this->assertEquals('active', $activeEvent['extendedProps']['status']);

        // Get only draft status events
        $draftEvents = $this->service->getEventsByStatus('draft');
        
        $this->assertCount(1, $draftEvents);
        $draftEvent = $draftEvents[0];
        $this->assertEquals('plant-' . $draftPlan->id, $draftEvent['id']);
        $this->assertEquals('#6b7280', $draftEvent['backgroundColor']);
        $this->assertEquals('draft', $draftEvent['extendedProps']['status']);
    }

    public function test_it_respects_date_range_filters()
    {
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(15);

        // Order within range
        $orderInRange = Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(10),
        ]);

        // Order outside range
        Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(20),
        ]);

        $events = $this->service->getCropPlanningEvents($startDate, $endDate);

        // Should only include the order within range
        $deliveryEvents = collect($events)->where('extendedProps.type', 'delivery');
        $this->assertCount(1, $deliveryEvents);
        
        $deliveryEvent = $deliveryEvents->first();
        $this->assertEquals('order-' . $orderInRange->id, $deliveryEvent['id']);
    }

    public function test_it_includes_extended_properties_for_events()
    {
        $seedEntry = MasterSeedCatalog::factory()->create([
            'common_name' => 'Test Spinach',
        ]);
        $recipe = Recipe::factory()->create([
            'seed_entry_id' => $seedEntry->id,
        ]);

        $order = Order::factory()->create([
            'status' => 'processing',
            'delivery_date' => now()->addDays(8),
        ]);

        $cropPlan = CropPlan::factory()->create([
            'order_id' => $order->id,
            'recipe_id' => $recipe->id,
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(3),
            'trays_needed' => 20,
        ]);

        $events = $this->service->getCropPlanningEvents();

        // Check delivery event extended properties
        $deliveryEvent = collect($events)->firstWhere('extendedProps.type', 'delivery');
        $this->assertEquals('delivery', $deliveryEvent['extendedProps']['type']);
        $this->assertEquals($order->id, $deliveryEvent['extendedProps']['orderId']);
        $this->assertEquals('processing', $deliveryEvent['extendedProps']['status']);

        // Check planting event extended properties
        $plantingEvent = collect($events)->firstWhere('extendedProps.type', 'planting');
        $this->assertEquals('planting', $plantingEvent['extendedProps']['type']);
        $this->assertEquals($cropPlan->id, $plantingEvent['extendedProps']['planId']);
        $this->assertEquals('Test Spinach', $plantingEvent['extendedProps']['variety']);
        $this->assertEquals(20, $plantingEvent['extendedProps']['trays']);
        $this->assertEquals('active', $plantingEvent['extendedProps']['status']);
        $this->assertEquals('Active', $plantingEvent['extendedProps']['statusName']);
    }

    public function test_it_handles_null_dates_gracefully()
    {
        // Create order without delivery date
        Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => null,
        ]);

        $events = $this->service->getCropPlanningEvents();

        // Should not crash and should not include events with null dates
        $deliveryEvents = collect($events)->where('extendedProps.type', 'delivery');
        $this->assertCount(0, $deliveryEvents);
    }
}