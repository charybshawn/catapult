<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\OrderPlanningService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Handle business logic when orders are created through Filament
 * Extracted from OrderObserver to work WITH Filament patterns
 */
class HandleOrderCreationAction
{
    public function __construct(
        private OrderPlanningService $orderPlanningService
    ) {}

    public function execute(Order $order, CreateRecord $page): void
    {
        if ($this->shouldGeneratePlans($order)) {
            $this->generatePlansWithFeedback($order, $page);
        }
    }

    private function shouldGeneratePlans(Order $order): bool
    {
        $statusCode = $order->status?->code;
        return in_array($statusCode, ['draft', 'pending', 'confirmed', 'new'])
            && !$order->is_recurring 
            && $order->requiresCropProduction();
    }

    private function generatePlansWithFeedback(Order $order, CreateRecord $page): void
    {
        try {
            $result = $this->orderPlanningService->generatePlansForOrder($order);
            
            if ($result['success']) {
                $this->handlePlanningSuccess($result, $order);
            } else {
                $this->handlePlanningFailure($result, $order);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate crop plans for new order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Crop Plan Generation Error')
                ->body('An error occurred while generating crop plans. Please try again or contact support.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function handlePlanningSuccess(array $result, Order $order): void
    {
        Log::info('Auto-generated crop plans for new order', [
            'order_id' => $order->id,
            'plans_count' => $result['plans']->count()
        ]);

        Notification::make()
            ->title('Order Created Successfully')
            ->body("Generated {$result['plans']->count()} crop plans for this order")
            ->success()
            ->send();
    }

    private function handlePlanningFailure(array $result, Order $order): void
    {
        Log::warning('Failed to auto-generate crop plans for new order', [
            'order_id' => $order->id,
            'issues' => $result['issues']
        ]);

        // Format issues for user display
        $issueMessages = [];
        foreach ($result['issues'] as $issue) {
            if (is_array($issue)) {
                $recipe = $issue['recipe'] ?? 'Unknown variety';
                $problem = $issue['issue'] ?? 'Unknown issue';
                $issueMessages[] = "{$recipe}: {$problem}";
            } else {
                $issueMessages[] = (string) $issue;
            }
        }

        $issueText = implode('<br>', array_slice($issueMessages, 0, 5));
        if (count($issueMessages) > 5) {
            $issueText .= '<br>...and ' . (count($issueMessages) - 5) . ' more issues';
        }

        Notification::make()
            ->title('Crop Plan Generation Issues')
            ->body('Order created but some crop plans could not be generated:<br>' . $issueText)
            ->warning()
            ->persistent()
            ->send();
    }
}