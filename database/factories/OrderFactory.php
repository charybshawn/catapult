<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderStatus;
use App\Models\CropStatus;
use App\Models\FulfillmentStatus;
use App\Models\CustomerType;
use App\Models\OrderType;
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
            'order_status_id' => OrderStatus::inRandomOrder()->first()?->id ?? OrderStatus::firstOrCreate(['code' => 'pending'], ['name' => 'Pending', 'description' => 'Order is pending'])->id,
            'crop_status_id' => CropStatus::inRandomOrder()->first()?->id ?? CropStatus::firstOrCreate(['code' => 'not_started'], ['name' => 'Not Started', 'description' => 'Crop has not been started'])->id,
            'fulfillment_status_id' => FulfillmentStatus::inRandomOrder()->first()?->id ?? FulfillmentStatus::firstOrCreate(['code' => 'pending'], ['name' => 'Pending', 'description' => 'Fulfillment is pending'])->id,
            'order_type_id' => OrderType::inRandomOrder()->first()?->id ?? OrderType::firstOrCreate(['code' => 'website_immediate'], ['name' => 'Website Immediate', 'description' => 'Immediate website order'])->id,
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
                'order_status_id' => OrderStatus::findByCode('template')?->id ?? OrderStatus::firstOrCreate(['code' => 'template'], ['name' => 'Template', 'description' => 'Recurring order template'])->id,
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
            'order_type_id' => OrderType::findByCode('b2b')?->id ?? OrderType::firstOrCreate(['code' => 'b2b'], ['name' => 'B2B', 'description' => 'Business to business order'])->id,
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
            'order_type_id' => OrderType::findByCode('farmers_market')?->id ?? OrderType::firstOrCreate(['code' => 'farmers_market'], ['name' => 'Farmers Market', 'description' => 'Farmers market order'])->id,
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
            'order_status_id' => OrderStatus::findByCode('pending')?->id ?? OrderStatus::firstOrCreate(['code' => 'pending'], ['name' => 'Pending', 'description' => 'Order is pending'])->id,
        ]);
    }

    /**
     * Create a wholesale customer order.
     */
    public function wholesale(): static
    {
        return $this->for(
            User::factory()->state(['customer_type_id' => CustomerType::findByCode('wholesale')?->id ?? 1]),
            'user'
        );
    }

    /**
     * Create a retail customer order.
     */
    public function retail(): static
    {
        return $this->for(
            User::factory()->state(['customer_type_id' => CustomerType::findByCode('retail')?->id ?? 1]),
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
            'order_status_id' => OrderStatus::findByCode($status)?->id ?? OrderStatus::firstOrCreate(['code' => $status], ['name' => ucfirst($status), 'description' => ucfirst($status) . ' status'])->id,
        ]);
    }

    /**
     * Create an order with specific crop status.
     */
    public function withCropStatus(string $cropStatus): static
    {
        return $this->state([
            'crop_status_id' => CropStatus::findByCode($cropStatus)?->id ?? CropStatus::firstOrCreate(['code' => $cropStatus], ['name' => ucfirst($cropStatus), 'description' => ucfirst($cropStatus) . ' status'])->id,
        ]);
    }
}