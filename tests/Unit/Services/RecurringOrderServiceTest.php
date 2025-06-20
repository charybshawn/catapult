<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use App\Services\RecurringOrderService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RecurringOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecurringOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecurringOrderService::class);
    }

    /** @test */
    public function it_gets_active_recurring_orders_only()
    {
        $activeTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
        ]);

        $inactiveTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $generatedOrder = Order::factory()->generatedFromRecurring($activeTemplate)->create();

        $activeOrders = $this->service->getActiveRecurringOrders();

        $this->assertCount(1, $activeOrders);
        $this->assertTrue($activeOrders->contains($activeTemplate));
        $this->assertFalse($activeOrders->contains($inactiveTemplate));
        $this->assertFalse($activeOrders->contains($generatedOrder));
    }

    /** @test */
    public function it_determines_when_order_should_generate()
    {
        $readyOrder = Order::factory()->recurring()->create([
            'next_generation_date' => now()->subHour(),
        ]);

        $futureOrder = Order::factory()->recurring()->create([
            'next_generation_date' => now()->addHour(),
        ]);

        $noDateOrder = Order::factory()->recurring()->create([
            'next_generation_date' => null,
        ]);

        $this->assertTrue($this->invokePrivateMethod($this->service, 'shouldGenerateOrder', [$readyOrder]));
        $this->assertFalse($this->invokePrivateMethod($this->service, 'shouldGenerateOrder', [$futureOrder]));
        
        // No date order should have date calculated and return false initially
        $result = $this->invokePrivateMethod($this->service, 'shouldGenerateOrder', [$noDateOrder]);
        $this->assertFalse($result);
        $this->assertNotNull($noDateOrder->fresh()->next_generation_date);
    }

    /** @test */
    public function it_determines_when_order_should_be_deactivated()
    {
        $expiredOrder = Order::factory()->recurring()->create([
            'recurring_end_date' => now()->subDay(),
        ]);

        $activeOrder = Order::factory()->recurring()->create([
            'recurring_end_date' => now()->addDay(),
        ]);

        $indefiniteOrder = Order::factory()->recurring()->create([
            'recurring_end_date' => null,
        ]);

        $this->assertTrue($this->invokePrivateMethod($this->service, 'shouldDeactivateOrder', [$expiredOrder]));
        $this->assertFalse($this->invokePrivateMethod($this->service, 'shouldDeactivateOrder', [$activeOrder]));
        $this->assertFalse($this->invokePrivateMethod($this->service, 'shouldDeactivateOrder', [$indefiniteOrder]));
    }

    /** @test */
    public function it_gets_upcoming_recurring_orders_within_timeframe()
    {
        $today = now();
        
        $todayOrder = Order::factory()->recurring()->create([
            'next_generation_date' => $today,
        ]);

        $tomorrowOrder = Order::factory()->recurring()->create([
            'next_generation_date' => $today->copy()->addDay(),
        ]);

        $weekOrder = Order::factory()->recurring()->create([
            'next_generation_date' => $today->copy()->addDays(7),
        ]);

        $futureOrder = Order::factory()->recurring()->create([
            'next_generation_date' => $today->copy()->addDays(10),
        ]);

        $upcomingOrders = $this->service->getUpcomingRecurringOrders(7);

        $this->assertCount(3, $upcomingOrders);
        $this->assertTrue($upcomingOrders->contains($todayOrder));
        $this->assertTrue($upcomingOrders->contains($tomorrowOrder));
        $this->assertTrue($upcomingOrders->contains($weekOrder));
        $this->assertFalse($upcomingOrders->contains($futureOrder));
    }

    /** @test */
    public function it_creates_recurring_order_template_with_defaults()
    {
        $user = User::factory()->create();
        
        $data = [
            'user_id' => $user->id,
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => '2025-01-01',
            'harvest_date' => '2025-01-01',
            'delivery_date' => '2025-01-02',
        ];

        $template = $this->service->createRecurringOrderTemplate($data);

        $this->assertTrue($template->is_recurring);
        $this->assertTrue($template->is_recurring_active);
        $this->assertEquals('template', $template->status);
        $this->assertEquals('weekly', $template->recurring_frequency);
        $this->assertNotNull($template->next_generation_date);
    }

    /** @test */
    public function it_pauses_recurring_order()
    {
        $template = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
        ]);

        $result = $this->service->pauseRecurringOrder($template);

        $this->assertTrue($result);
        $this->assertFalse($template->fresh()->is_recurring_active);
    }

    /** @test */
    public function it_resumes_paused_recurring_order()
    {
        $template = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $result = $this->service->resumeRecurringOrder($template);

        $this->assertTrue($result);
        $template->refresh();
        $this->assertTrue($template->is_recurring_active);
        $this->assertNotNull($template->next_generation_date);
    }

    /** @test */
    public function it_cannot_pause_non_recurring_template()
    {
        $regularOrder = Order::factory()->create(['is_recurring' => false]);

        $result = $this->service->pauseRecurringOrder($regularOrder);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_manually_generates_next_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        // Create template with past start date so it's ready to generate
        $template = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'is_recurring_active' => true,
            'recurring_frequency' => 'weekly', // Fixed frequency for predictable testing
            'recurring_start_date' => now()->subDays(14), // 2 weeks ago
            'last_generated_at' => now()->subDays(7), // Last generated 1 week ago
        ]);

        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
        ]);

        $newOrder = $this->service->generateNextOrder($template);

        $this->assertNotNull($newOrder);
        $this->assertEquals($template->id, $newOrder->parent_recurring_order_id);
        $this->assertEquals($user->id, $newOrder->user_id);
        $this->assertFalse($newOrder->is_recurring);
    }

    /** @test */
    public function it_throws_exception_when_manually_generating_from_non_template()
    {
        $regularOrder = Order::factory()->create(['is_recurring' => false]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order is not a recurring template');

        $this->service->generateNextOrder($regularOrder);
    }

    /** @test */
    public function it_throws_exception_when_generating_from_inactive_template()
    {
        $template = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recurring order template is not active');

        $this->service->generateNextOrder($template);
    }

    /** @test */
    public function it_calculates_next_date_correctly_for_different_frequencies()
    {
        $startDate = Carbon::parse('2025-01-01');

        $weeklyDate = $this->invokePrivateMethod($this->service, 'calculateNextDate', [
            $startDate, 'weekly'
        ]);

        $biweeklyDate = $this->invokePrivateMethod($this->service, 'calculateNextDate', [
            $startDate, 'biweekly', 3
        ]);

        $monthlyDate = $this->invokePrivateMethod($this->service, 'calculateNextDate', [
            $startDate, 'monthly'
        ]);

        $unknownDate = $this->invokePrivateMethod($this->service, 'calculateNextDate', [
            $startDate, 'unknown'
        ]);

        $this->assertEquals('2025-01-08', $weeklyDate->format('Y-m-d'));
        $this->assertEquals('2025-01-22', $biweeklyDate->format('Y-m-d')); // 3 weeks
        $this->assertEquals('2025-02-01', $monthlyDate->format('Y-m-d'));
        $this->assertEquals('2025-01-08', $unknownDate->format('Y-m-d')); // Defaults to weekly
    }

    /** @test */
    public function it_gets_recurring_order_statistics()
    {
        // Create various types of templates and orders
        Order::factory()->recurring()->create(['is_recurring_active' => true]);
        Order::factory()->recurring()->create(['is_recurring_active' => true]);
        Order::factory()->recurring()->create(['is_recurring_active' => false]);
        
        $template = Order::factory()->recurring()->create(['is_recurring_active' => true]);
        Order::factory()->generatedFromRecurring($template)->create();
        Order::factory()->generatedFromRecurring($template)->create();

        // Create upcoming orders
        Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->addDays(3),
        ]);

        $stats = $this->service->getRecurringOrderStats();

        $this->assertEquals(4, $stats['active_templates']);
        $this->assertEquals(1, $stats['paused_templates']);
        $this->assertEquals(2, $stats['total_generated']);
        $this->assertEquals(1, $stats['upcoming_week']);
    }

    /** @test */
    public function it_processes_multiple_recurring_orders_with_mixed_results()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Ready for generation
        $readyTemplate = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);
        OrderItem::factory()->create([
            'order_id' => $readyTemplate->id,
            'product_id' => $product->id,
        ]);

        // Expired template
        $expiredTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'recurring_end_date' => now()->subDay(),
        ]);

        // Not ready yet
        $futureTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->addHour(),
        ]);

        $results = $this->service->processRecurringOrders();

        $this->assertEquals(3, $results['processed']);
        $this->assertEquals(1, $results['generated']);
        $this->assertEquals(1, $results['deactivated']);
        $this->assertEmpty($results['errors']);

        // Verify state changes
        $this->assertCount(1, $readyTemplate->generatedOrders);
        $this->assertFalse($expiredTemplate->fresh()->is_recurring_active);
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, $methodName, $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $args);
    }
}