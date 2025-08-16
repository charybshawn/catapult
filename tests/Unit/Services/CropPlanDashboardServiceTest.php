<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CropPlanDashboardService;
use App\Models\CropPlan;
use App\Models\Order;
use App\Models\CropPlanStatus;
use App\Models\Customer;
use App\Models\Recipe;
use App\Models\MasterSeedCatalog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CropPlanDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CropPlanDashboardService $service;
    protected CropPlanStatus $activeStatus;
    protected CropPlanStatus $draftStatus;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CropPlanDashboardService();
        
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

    public function test_it_gets_urgent_crops_within_seven_days()
    {
        // Create test data using factory for CropPlan
        $urgentCrop = CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(3),
        ]);

        // Create non-urgent crop plan (plant in 10 days)
        CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(10),
        ]);

        // Create draft crop plan (should not be included)
        CropPlan::factory()->create([
            'status_id' => $this->draftStatus->id,
            'plant_by_date' => now()->addDays(2),
        ]);

        $urgentCrops = $this->service->getUrgentCrops();

        $this->assertCount(1, $urgentCrops);
        $this->assertTrue($urgentCrops->flatten()->contains($urgentCrop));
    }

    public function test_it_gets_overdue_crops()
    {
        // Create overdue crop plan
        $overdueCrop = CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->subDays(2),
        ]);

        // Create future crop plan (should not be included)
        CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(2),
        ]);

        // Create draft overdue crop plan (should not be included)
        CropPlan::factory()->create([
            'status_id' => $this->draftStatus->id,
            'plant_by_date' => now()->subDays(3),
        ]);

        $overdueCrops = $this->service->getOverdueCrops();

        $this->assertCount(1, $overdueCrops);
        $this->assertTrue($overdueCrops->contains($overdueCrop));
    }

    public function test_it_gets_upcoming_orders_without_crop_plans()
    {
        // Create order within 14 days without crop plans
        $orderWithoutPlans = Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(5),
        ]);

        // Create order within 14 days with crop plans (should not be included)
        $orderWithPlans = Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(7),
        ]);
        
        // Add crop plan to second order
        CropPlan::factory()->create([
            'order_id' => $orderWithPlans->id,
            'status_id' => $this->activeStatus->id,
        ]);

        // Create order beyond 14 days (should not be included)
        Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(20),
        ]);

        $upcomingOrders = $this->service->getUpcomingOrders();

        $this->assertCount(1, $upcomingOrders);
        $this->assertTrue($upcomingOrders->contains($orderWithoutPlans));
    }

    public function test_it_gets_dashboard_stats()
    {
        // Create test data
        CropPlan::factory()->count(2)->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(3),
        ]);

        CropPlan::factory()->count(3)->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->subDays(2),
        ]);

        Order::factory()->count(4)->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(5),
        ]);

        $stats = $this->service->getDashboardStats();

        $this->assertEquals(2, $stats['urgent_crops_count']);
        $this->assertEquals(3, $stats['overdue_crops_count']);
        $this->assertEquals(4, $stats['upcoming_orders_count']);
    }

    public function test_it_gets_crop_plans_by_date_range()
    {
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(15);

        // Create crop plan within range
        $cropInRange = CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(10),
        ]);

        // Create crop plan outside range
        CropPlan::factory()->create([
            'status_id' => $this->activeStatus->id,
            'plant_by_date' => now()->addDays(20),
        ]);

        $cropPlans = $this->service->getCropPlansByDateRange($startDate, $endDate);

        $this->assertCount(1, $cropPlans);
        $this->assertTrue($cropPlans->contains($cropInRange));
    }

    public function test_it_gets_orders_by_delivery_date_range()
    {
        $startDate = now()->addDays(5);
        $endDate = now()->addDays(15);

        // Create order within range
        $orderInRange = Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(10),
        ]);

        // Create order outside range
        Order::factory()->create([
            'status' => 'confirmed',
            'delivery_date' => now()->addDays(20),
        ]);

        // Create order with cancelled status (should not be included)
        Order::factory()->create([
            'status' => 'cancelled',
            'delivery_date' => now()->addDays(8),
        ]);

        $orders = $this->service->getOrdersByDeliveryDateRange($startDate, $endDate);

        $this->assertCount(1, $orders);
        $this->assertTrue($orders->contains($orderInRange));
    }

    public function test_it_handles_missing_active_status_gracefully()
    {
        // Delete the active status
        $this->activeStatus->delete();

        $urgentCrops = $this->service->getUrgentCrops();
        $overdueCrops = $this->service->getOverdueCrops();

        $this->assertTrue($urgentCrops->isEmpty());
        $this->assertTrue($overdueCrops->isEmpty());
    }
}