<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use App\Services\InvoiceConsolidationService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class InvoiceConversionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_invoice_for_website_immediate_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'website_immediate',
            'billing_frequency' => 'immediate',
            'requires_invoice' => true,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 15.50,
        ]);

        // Create invoice for the order
        $invoice = Invoice::factory()->forOrder($order)->create();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($order->id, $invoice->order_id);
        $this->assertEquals($user->id, $invoice->user_id);
        $this->assertEquals($order->totalAmount(), $invoice->amount);
        $this->assertEquals($order->totalAmount(), $invoice->total_amount);
    }

    /** @test */
    public function it_does_not_require_invoice_for_farmers_market_orders()
    {
        $order = Order::factory()->farmersMarket()->create([
            'requires_invoice' => false,
        ]);

        $this->assertTrue($order->shouldBypassInvoicing());
        $this->assertEquals('farmers_market', $order->order_type);
        $this->assertFalse($order->requires_invoice);
    }

    /** @test */
    public function it_handles_b2b_consolidated_billing()
    {
        $user = User::factory()->create(['customer_type' => 'wholesale']);
        
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
            'requires_invoice' => true,
        ]);

        $this->assertTrue($order->isConsolidatedBilling());
        $this->assertFalse($order->requiresImmediateInvoicing());
    }

    /** @test */
    public function it_creates_consolidated_invoice_for_multiple_orders()
    {
        $user = User::factory()->create();
        $startDate = Carbon::parse('2025-01-01');
        $endDate = Carbon::parse('2025-01-31');

        // Create multiple B2B orders for the same billing period
        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
            'delivery_date' => '2025-01-10',
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
            'delivery_date' => '2025-01-20',
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
        ]);

        // Add items to orders
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'quantity' => 3,
            'price' => 20.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'quantity' => 2,
            'price' => 25.00,
        ]);

        $totalAmount = $order1->totalAmount() + $order2->totalAmount();

        // Create consolidated invoice
        $consolidatedInvoice = Invoice::factory()->consolidated()->create([
            'user_id' => $user->id,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'consolidated_order_count' => 2,
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
        ]);

        // Link orders to consolidated invoice
        $order1->update(['consolidated_invoice_id' => $consolidatedInvoice->id]);
        $order2->update(['consolidated_invoice_id' => $consolidatedInvoice->id]);

        $this->assertTrue($consolidatedInvoice->is_consolidated);
        $this->assertEquals(2, $consolidatedInvoice->consolidated_order_count);
        $this->assertEquals($totalAmount, $consolidatedInvoice->total_amount);
        $this->assertNull($consolidatedInvoice->order_id); // No single order
        $this->assertEquals($user->id, $consolidatedInvoice->user_id);
    }

    /** @test */
    public function it_sets_billing_periods_for_b2b_orders()
    {
        $deliveryDate = Carbon::parse('2025-06-15'); // Mid-June

        $monthlyOrder = Order::factory()->create([
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
            'delivery_date' => $deliveryDate,
        ]);

        $weeklyOrder = Order::factory()->create([
            'order_type' => 'b2b',
            'billing_frequency' => 'weekly',
            'delivery_date' => $deliveryDate,
        ]);

        $quarterlyOrder = Order::factory()->create([
            'order_type' => 'b2b',
            'billing_frequency' => 'quarterly',
            'delivery_date' => $deliveryDate,
        ]);

        // Check monthly billing period
        $this->assertEquals('2025-06-01', $monthlyOrder->billing_period_start?->format('Y-m-d'));
        $this->assertEquals('2025-06-30', $monthlyOrder->billing_period_end?->format('Y-m-d'));

        // Check quarterly billing period
        $this->assertEquals('2025-04-01', $quarterlyOrder->billing_period_start?->format('Y-m-d'));
        $this->assertEquals('2025-06-30', $quarterlyOrder->billing_period_end?->format('Y-m-d'));
    }

    /** @test */
    public function it_generates_unique_invoice_numbers()
    {
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        $this->assertNotEmpty($invoice1->invoice_number);
        $this->assertNotEmpty($invoice2->invoice_number);
        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice1->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice2->invoice_number);
    }

    /** @test */
    public function it_calculates_invoice_amount_from_order_total()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 3,
            'price' => 12.50,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'price' => 7.25,
        ]);

        $expectedTotal = (3 * 12.50) + (1 * 7.25); // 37.50 + 7.25 = 44.75

        $invoice = Invoice::factory()->forOrder($order)->create();

        $this->assertEquals($expectedTotal, $invoice->amount);
        $this->assertEquals($expectedTotal, $invoice->total_amount);
    }

    /** @test */
    public function it_tracks_invoice_status_changes()
    {
        $invoice = Invoice::factory()->draft()->create();
        $this->assertEquals('draft', $invoice->status);
        $this->assertNull($invoice->sent_at);
        $this->assertNull($invoice->paid_at);

        // Mark as sent
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertEquals('sent', $invoice->status);
        $this->assertNotNull($invoice->sent_at);

        // Mark as paid
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertEquals('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    /** @test */
    public function it_handles_overdue_invoices()
    {
        $overdueInvoice = Invoice::factory()->overdue()->create();

        $this->assertEquals('overdue', $overdueInvoice->status);
        $this->assertNotNull($overdueInvoice->sent_at);
        $this->assertNull($overdueInvoice->paid_at);
        $this->assertTrue($overdueInvoice->due_date->isPast());
    }

    /** @test */
    public function it_calculates_correct_due_dates()
    {
        $issueDate = Carbon::parse('2025-01-15');
        
        $invoice = Invoice::factory()->create([
            'issue_date' => $issueDate,
            'due_date' => $issueDate->copy()->addDays(30),
        ]);

        $expectedDueDate = $issueDate->copy()->addDays(30);
        $this->assertEquals($expectedDueDate->format('Y-m-d'), $invoice->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_preserves_customer_information_in_invoice()
    {
        $user = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'customer_type' => 'wholesale',
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => 'wholesale',
        ]);

        $invoice = Invoice::factory()->forOrder($order)->create();

        $this->assertEquals($user->id, $invoice->user_id);
        $this->assertEquals($user->name, $invoice->user->name);
        $this->assertEquals($user->email, $invoice->user->email);
    }

    /** @test */
    public function it_supports_partial_payments_tracking()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'price' => 50.00,
        ]);

        $invoice = Invoice::factory()->forOrder($order)->create();
        
        // Create partial payment
        $order->payments()->create([
            'amount' => 60.00, // Partial payment
            'status' => 'completed',
        ]);

        $this->assertEquals(100.00, $order->totalAmount());
        $this->assertEquals(40.00, $order->remainingBalance());
        $this->assertFalse($order->isPaid());

        // Complete payment
        $order->payments()->create([
            'amount' => 40.00,
            'status' => 'completed',
        ]);

        $this->assertTrue($order->isPaid());
        $this->assertEquals(0.00, $order->remainingBalance());
    }

    /** @test */
    public function it_handles_invoice_cancellation()
    {
        $invoice = Invoice::factory()->create(['status' => 'sent']);

        $invoice->update(['status' => 'cancelled']);

        $this->assertEquals('cancelled', $invoice->status);
    }

    /** @test */
    public function it_creates_invoices_with_proper_billing_periods_for_consolidated_billing()
    {
        $user = User::factory()->create();
        $billingStart = Carbon::parse('2025-01-01');
        $billingEnd = Carbon::parse('2025-01-31');

        $orders = collect();
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::factory()->create([
                'user_id' => $user->id,
                'order_type' => 'b2b',
                'billing_frequency' => 'monthly',
                'delivery_date' => $billingStart->copy()->addDays($i * 5),
                'billing_period_start' => $billingStart,
                'billing_period_end' => $billingEnd,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'quantity' => 2,
                'price' => 25.00,
            ]);

            $orders->push($order);
        }

        $totalAmount = $orders->sum(fn($order) => $order->totalAmount());

        $consolidatedInvoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'billing_period_start' => $billingStart,
            'billing_period_end' => $billingEnd,
        ]);

        // Link all orders to the consolidated invoice
        $orders->each(fn($order) => $order->update([
            'consolidated_invoice_id' => $consolidatedInvoice->id
        ]));

        $this->assertTrue($consolidatedInvoice->is_consolidated);
        $this->assertEquals(3, $consolidatedInvoice->consolidated_order_count);
        $this->assertEquals($billingStart->format('Y-m-d'), $consolidatedInvoice->billing_period_start->format('Y-m-d'));
        $this->assertEquals($billingEnd->format('Y-m-d'), $consolidatedInvoice->billing_period_end->format('Y-m-d'));
        $this->assertEquals(150.00, $consolidatedInvoice->total_amount); // 3 orders * 2 items * $25
    }
}