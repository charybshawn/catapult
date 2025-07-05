<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dueDate = $this->faker->dateTimeBetween('now', '+30 days');
        $amount = $this->faker->randomFloat(2, 10, 500);

        return [
            'order_id' => Order::factory(),
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $amount,
            'payment_status_id' => \App\Models\PaymentStatus::inRandomOrder()->first()?->id ?? \App\Models\PaymentStatus::firstOrCreate(['code' => 'pending'], ['name' => 'Pending', 'description' => 'Payment is pending'])->id,
            'due_date' => $dueDate,
            'sent_at' => $this->faker->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'paid_at' => null, // Will be set for paid invoices
            'is_consolidated' => false,
            'consolidated_order_count' => 1,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this;
    }

    /**
     * Create a paid invoice.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAt = $this->faker->dateTimeBetween('-30 days', 'now');
            
            return [
                'payment_status_id' => \App\Models\PaymentStatus::findByCode('paid')?->id ?? \App\Models\PaymentStatus::firstOrCreate(['code' => 'paid'], ['name' => 'Paid', 'description' => 'Payment completed'])->id,
                'paid_at' => $paidAt,
                'sent_at' => $this->faker->dateTimeBetween('-30 days', $paidAt),
            ];
        });
    }

    /**
     * Create a draft invoice.
     */
    public function draft(): static
    {
        return $this->state([
            'payment_status_id' => \App\Models\PaymentStatus::findByCode('draft')?->id ?? \App\Models\PaymentStatus::firstOrCreate(['code' => 'draft'], ['name' => 'Draft', 'description' => 'Invoice is draft'])->id,
            'sent_at' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Create an overdue invoice.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $pastDueDate = $this->faker->dateTimeBetween('-60 days', '-1 day');
            
            return [
                'payment_status_id' => \App\Models\PaymentStatus::findByCode('overdue')?->id ?? \App\Models\PaymentStatus::firstOrCreate(['code' => 'overdue'], ['name' => 'Overdue', 'description' => 'Payment overdue'])->id,
                'due_date' => $pastDueDate,
                'sent_at' => $this->faker->dateTimeBetween('-90 days', '-30 days'),
                'paid_at' => null,
            ];
        });
    }

    /**
     * Create a consolidated invoice.
     */
    public function consolidated(): static
    {
        return $this->state(function (array $attributes) {
            $orderCount = $this->faker->numberBetween(2, 10);
            
            return [
                'order_id' => null, // Consolidated invoices don't have a single order
                'is_consolidated' => true,
                'consolidated_order_count' => $orderCount,
                'amount' => $this->faker->randomFloat(2, $orderCount * 50, $orderCount * 200),
            ];
        });
    }

    /**
     * Create invoice for specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state([
            'order_id' => $order->id,
            'amount' => $order->totalAmount(),
        ]);
    }

    /**
     * Create invoice for specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state([
            // User is accessed through order relationship
        ]);
    }

    /**
     * Create invoice with billing period.
     */
    public function withBillingPeriod(\DateTime $start = null, \DateTime $end = null): static
    {
        $start = $start ?? $this->faker->dateTimeBetween('-2 months', '-1 month');
        $end = $end ?? (clone $start)->modify('+1 month');

        return $this->state([
            'billing_period_start' => $start,
            'billing_period_end' => $end,
        ]);
    }

    /**
     * Create invoice with specific amount.
     */
    public function withAmount(float $amount): static
    {
        return $this->state([
            'amount' => $amount,
        ]);
    }

    /**
     * Create invoice with specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state([
            'payment_status_id' => \App\Models\PaymentStatus::findByCode($status)?->id ?? \App\Models\PaymentStatus::firstOrCreate(['code' => $status], ['name' => ucfirst($status), 'description' => ucfirst($status) . ' status'])->id,
        ]);
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $number = $this->faker->unique()->numberBetween(1000, 9999);
        
        return "{$prefix}-{$year}-{$number}";
    }
}