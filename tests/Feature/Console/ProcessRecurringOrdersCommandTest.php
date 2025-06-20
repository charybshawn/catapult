<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

class ProcessRecurringOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_processes_recurring_orders_successfully()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $template = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
        ]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('Processing recurring orders', $output);
        $this->assertStringContains('Templates Processed', $output);
        $this->assertStringContains('New Orders Generated', $output);

        // Verify order was generated
        $this->assertCount(1, $template->generatedOrders);
    }

    /** @test */
    public function it_shows_dry_run_information()
    {
        $user = User::factory()->create(['name' => 'Test Customer']);
        
        $template = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_frequency' => 'weekly',
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create(['order_id' => $template->id]);

        $exitCode = Artisan::call('orders:process-recurring', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('DRY RUN MODE', $output);
        $this->assertStringContains('Test Customer', $output);
        $this->assertStringContains('Weekly', $output);
        $this->assertStringContains('Orders that would be generated', $output);

        // Verify no orders were actually generated
        $this->assertCount(0, $template->generatedOrders);
    }

    /** @test */
    public function it_handles_no_orders_to_process()
    {
        // Create template that's not ready for generation
        Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->addDays(1),
        ]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('No recurring orders needed processing', $output);
    }

    /** @test */
    public function it_displays_error_information_when_generation_fails()
    {
        // Create template with invalid user ID to cause error
        $template = Order::factory()->recurring()->create([
            'user_id' => 999999, // Non-existent user
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create(['order_id' => $template->id]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode); // Command still succeeds but reports errors

        $output = Artisan::output();
        $this->assertStringContains('Errors Encountered', $output);
    }

    /** @test */
    public function it_shows_deactivated_templates()
    {
        Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'recurring_end_date' => now()->subDay(), // Expired
        ]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('Templates Deactivated', $output);
    }

    /** @test */
    public function it_shows_success_message_when_orders_generated()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $template1 = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        $template2 = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create([
            'order_id' => $template1->id,
            'product_id' => $product->id,
        ]);

        OrderItem::factory()->create([
            'order_id' => $template2->id,
            'product_id' => $product->id,
        ]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('Successfully generated 2 new orders', $output);
        $this->assertStringContains('Recurring order processing completed successfully', $output);
    }

    /** @test */
    public function dry_run_shows_accurate_statistics()
    {
        // Create various template states
        Order::factory()->recurring()->create(['is_recurring_active' => true]); // Active
        Order::factory()->recurring()->create(['is_recurring_active' => false]); // Paused
        
        $template = Order::factory()->recurring()->create(['is_recurring_active' => true]);
        Order::factory()->generatedFromRecurring($template)->create(); // Generated order

        // Template ready for generation today
        $readyTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);
        OrderItem::factory()->create(['order_id' => $readyTemplate->id]);

        $exitCode = Artisan::call('orders:process-recurring', ['--dry-run' => true]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('Active Templates', $output);
        $this->assertStringContains('Paused Templates', $output);
        $this->assertStringContains('Total Generated Orders', $output);
        $this->assertStringContains('Due for Processing Today', $output);
    }

    /** @test */
    public function it_handles_force_flag()
    {
        // The --force flag is defined in the command signature but doesn't affect behavior in current implementation
        // This test ensures the flag is accepted without errors
        
        $exitCode = Artisan::call('orders:process-recurring', ['--force' => true]);

        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContains('Processing recurring orders', $output);
    }

    /** @test */
    public function it_processes_templates_with_different_frequencies()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // Create templates with different frequencies
        $weeklyTemplate = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_frequency' => 'weekly',
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        $monthlyTemplate = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_frequency' => 'monthly',
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        $biweeklyTemplate = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_frequency' => 'biweekly',
            'recurring_interval' => 2,
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        // Add items to all templates
        OrderItem::factory()->create(['order_id' => $weeklyTemplate->id, 'product_id' => $product->id]);
        OrderItem::factory()->create(['order_id' => $monthlyTemplate->id, 'product_id' => $product->id]);
        OrderItem::factory()->create(['order_id' => $biweeklyTemplate->id, 'product_id' => $product->id]);

        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        // Verify all templates generated orders
        $this->assertCount(1, $weeklyTemplate->generatedOrders);
        $this->assertCount(1, $monthlyTemplate->generatedOrders);
        $this->assertCount(1, $biweeklyTemplate->generatedOrders);

        $output = Artisan::output();
        $this->assertStringContains('Successfully generated 3 new orders', $output);
    }
}