<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $harvestDate = $this->faker->dateTimeBetween('now', '+30 days');
        $deliveryDate = (clone $harvestDate)->modify('+1 day');

        return [
            'user_id' => User::factory(),
            'harvest_date' => $harvestDate,
            'delivery_date' => $deliveryDate,
            'status' => $this->faker->randomElement(['draft', 'pending', 'confirmed', 'processing', 'completed', 'cancelled']),
            'crop_status' => $this->faker->randomElement(['not_started', 'planted', 'growing', 'ready_to_harvest', 'harvested', 'na']),
            'fulfillment_status' => $this->faker->randomElement(['pending', 'processing', 'packing', 'packed', 'ready_for_delivery', 'out_for_delivery', 'delivered', 'cancelled']),
            'customer_type' => $this->faker->randomElement(['retail', 'wholesale']),
            'order_type' => $this->faker->randomElement(['website_immediate', 'farmers_market', 'b2b', 'b2b_recurring']),
            'billing_frequency' => $this->faker->randomElement(['immediate', 'weekly', 'monthly', 'quarterly']),
            'requires_invoice' => $this->faker->boolean(80),
            'is_recurring' => false,
            'is_recurring_active' => false,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create a recurring order template.
     */
    public function recurring(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = $this->faker->dateTimeBetween('now', '+7 days');
            $endDate = $this->faker->optional(0.7)->dateTimeBetween($startDate, '+1 year');

            return [
                'is_recurring' => true,
                'is_recurring_active' => true,
                'status' => 'template',
                'recurring_frequency' => $this->faker->randomElement(['weekly', 'biweekly', 'monthly']),
                'recurring_start_date' => $startDate,
                'recurring_end_date' => $endDate,
                'recurring_interval' => function (array $attributes) {
                    return $attributes['recurring_frequency'] === 'biweekly' ? 2 : null;
                },
                'next_generation_date' => $startDate,
            ];
        });
    }

    /**
     * Create a B2B recurring order.
     */
    public function b2bRecurring(): static
    {
        return $this->recurring()->state([
            'order_type' => 'b2b_recurring',
            'billing_frequency' => $this->faker->randomElement(['weekly', 'monthly', 'quarterly']),
            'requires_invoice' => true,
        ]);
    }

    /**
     * Create a farmer's market order.
     */
    public function farmersMarket(): static
    {
        return $this->state([
            'order_type' => 'farmers_market',
            'billing_frequency' => 'immediate',
            'requires_invoice' => false,
        ]);
    }

    /**
     * Create an order generated from a recurring template.
     */
    public function generatedFromRecurring(Order $parentOrder = null): static
    {
        return $this->state([
            'parent_recurring_order_id' => $parentOrder?->id ?? Order::factory()->recurring(),
            'is_recurring' => false,
            'status' => 'pending',
        ]);
    }

    /**
     * Create a wholesale customer order.
     */
    public function wholesale(): static
    {
        return $this->state([
            'customer_type' => 'wholesale',
        ])->for(
            User::factory()->state(['customer_type' => 'wholesale']),
            'user'
        );
    }

    /**
     * Create a retail customer order.
     */
    public function retail(): static
    {
        return $this->state([
            'customer_type' => 'retail',
        ])->for(
            User::factory()->state(['customer_type' => 'retail']),
            'user'
        );
    }

    /**
     * Create an order with billing periods set.
     */
    public function withBillingPeriod(): static
    {
        return $this->state(function (array $attributes) {
            $deliveryDate = $attributes['delivery_date'] ?? now()->addDays(1);
            $delivery = is_string($deliveryDate) ? new \DateTime($deliveryDate) : $deliveryDate;

            return [
                'billing_period_start' => $delivery->format('Y-m-01'),
                'billing_period_end' => $delivery->format('Y-m-t'),
            ];
        });
    }

    /**
     * Create a paused recurring order.
     */
    public function paused(): static
    {
        return $this->recurring()->state([
            'is_recurring_active' => false,
        ]);
    }

    /**
     * Create an order with specific status.
     */
    public function withStatus(string $status): static
    {
        return $this->state([
            'status' => $status,
        ]);
    }

    /**
     * Create an order with specific crop status.
     */
    public function withCropStatus(string $cropStatus): static
    {
        return $this->state([
            'crop_status' => $cropStatus,
        ]);
    }
}