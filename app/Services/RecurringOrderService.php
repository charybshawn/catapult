<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RecurringOrderService
{
    /**
     * Process all active recurring orders and generate new orders as needed.
     */
    public function processRecurringOrders(): array
    {
        $results = [
            'processed' => 0,
            'generated' => 0,
            'deactivated' => 0,
            'errors' => []
        ];

        $recurringOrders = $this->getActiveRecurringOrders();
        
        foreach ($recurringOrders as $order) {
            try {
                $results['processed']++;
                
                if ($this->shouldGenerateOrder($order)) {
                    $newOrder = $order->generateNextRecurringOrder();
                    
                    if ($newOrder) {
                        $results['generated']++;
                        Log::info('Generated recurring order', [
                            'template_id' => $order->id,
                            'new_order_id' => $newOrder->id,
                            'customer' => $order->user->name ?? 'Unknown',
                            'harvest_date' => $newOrder->harvest_date
                        ]);
                    }
                } elseif ($this->shouldDeactivateOrder($order)) {
                    $order->update(['is_recurring_active' => false]);
                    $results['deactivated']++;
                    Log::info('Deactivated recurring order (past end date)', [
                        'template_id' => $order->id,
                        'customer' => $order->user->name ?? 'Unknown'
                    ]);
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ];
                Log::error('Error processing recurring order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Get all active recurring order templates.
     */
    public function getActiveRecurringOrders(): Collection
    {
        return Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id') // Only templates, not generated orders
            ->with(['user', 'orderItems', 'packagingTypes'])
            ->get();
    }

    /**
     * Check if an order should generate a new instance.
     */
    protected function shouldGenerateOrder(Order $order): bool
    {
        // If no next generation date is set, calculate it
        if (!$order->next_generation_date) {
            $nextDate = $order->calculateNextGenerationDate();
            if ($nextDate) {
                $order->update(['next_generation_date' => $nextDate]);
            }
            return false; // Don't generate immediately after setting date
        }

        // Check if it's time to generate
        return now()->gte($order->next_generation_date);
    }

    /**
     * Check if an order should be deactivated.
     */
    protected function shouldDeactivateOrder(Order $order): bool
    {
        return $order->recurring_end_date && now()->gt($order->recurring_end_date);
    }

    /**
     * Get upcoming recurring orders for the next N days.
     */
    public function getUpcomingRecurringOrders(int $days = 7): Collection
    {
        $endDate = now()->addDays($days);
        
        return Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id')
            ->where('next_generation_date', '<=', $endDate)
            ->with(['user', 'orderItems'])
            ->orderBy('next_generation_date')
            ->get();
    }

    /**
     * Create a new recurring order template.
     */
    public function createRecurringOrderTemplate(array $data): Order
    {
        // Ensure required recurring fields are set
        $data['is_recurring'] = true;
        $data['is_recurring_active'] = true;
        $data['status'] = 'template'; // Special status for templates
        
        // Calculate initial next generation date
        if (isset($data['recurring_start_date'])) {
            $startDate = Carbon::parse($data['recurring_start_date']);
            $data['next_generation_date'] = $this->calculateNextDate($startDate, $data['recurring_frequency'], $data['recurring_interval'] ?? null);
        }

        $order = Order::create($data);
        
        Log::info('Created recurring order template', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown',
            'frequency' => $order->recurring_frequency
        ]);

        return $order;
    }

    /**
     * Calculate the next date based on frequency.
     */
    protected function calculateNextDate(Carbon $fromDate, string $frequency, ?int $interval = null): Carbon
    {
        return match($frequency) {
            'weekly' => $fromDate->addWeek(),
            'biweekly' => $fromDate->addWeeks($interval ?? 2),
            'monthly' => $fromDate->addMonth(),
            default => $fromDate->addWeek()
        };
    }

    /**
     * Pause a recurring order (set inactive but keep for future reactivation).
     */
    public function pauseRecurringOrder(Order $order): bool
    {
        if (!$order->isRecurringTemplate()) {
            return false;
        }

        $order->update(['is_recurring_active' => false]);
        
        Log::info('Paused recurring order', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown'
        ]);

        return true;
    }

    /**
     * Resume a paused recurring order.
     */
    public function resumeRecurringOrder(Order $order): bool
    {
        if (!$order->isRecurringTemplate() || $order->is_recurring_active) {
            return false;
        }

        // Recalculate next generation date from now
        $nextDate = $this->calculateNextDate(now(), $order->recurring_frequency, $order->recurring_interval);
        
        $order->update([
            'is_recurring_active' => true,
            'next_generation_date' => $nextDate
        ]);
        
        Log::info('Resumed recurring order', [
            'template_id' => $order->id,
            'customer' => $order->user->name ?? 'Unknown',
            'next_generation' => $nextDate
        ]);

        return true;
    }

    /**
     * Manually generate the next order for a recurring template.
     */
    public function generateNextOrder(Order $template): ?Order
    {
        if (!$template->isRecurringTemplate()) {
            throw new \InvalidArgumentException('Order is not a recurring template');
        }

        if (!$template->is_recurring_active) {
            throw new \InvalidArgumentException('Recurring order template is not active');
        }

        // Use the model's generateNextRecurringOrder method
        $newOrder = $template->generateNextRecurringOrder();
        
        if ($newOrder) {
            Log::info('Manually generated recurring order', [
                'template_id' => $template->id,
                'new_order_id' => $newOrder->id,
                'customer' => $template->user->name ?? 'Unknown',
                'generated_by' => auth()->user()?->name ?? 'System'
            ]);
        }

        return $newOrder;
    }

    /**
     * Get statistics about recurring orders.
     */
    public function getRecurringOrderStats(): array
    {
        $activeTemplates = Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id')
            ->count();

        $pausedTemplates = Order::where('is_recurring', true)
            ->where('is_recurring_active', false)
            ->whereNull('parent_recurring_order_id')
            ->count();

        $generatedOrders = Order::whereNotNull('parent_recurring_order_id')->count();

        $upcomingWeek = $this->getUpcomingRecurringOrders(7)->count();

        return [
            'active_templates' => $activeTemplates,
            'paused_templates' => $pausedTemplates,
            'total_generated' => $generatedOrders,
            'upcoming_week' => $upcomingWeek
        ];
    }
}