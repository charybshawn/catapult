<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CropPlan;
use App\Models\Recipe;
use App\Services\CropPlanCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CropPlanningController extends Controller
{
    public function generatePdf(Request $request)
    {
        $deliveryDate = $request->get('delivery_date');
        
        if (!$deliveryDate) {
            abort(400, 'Delivery date is required');
        }

        $orders = Order::with([
            'user',
            'orderItems.product.productMix.seedEntries',
            'orderItems.priceVariation.packagingType'
        ])
            ->where('delivery_date', $deliveryDate)
            ->where('status', '!=', 'cancelled')
            ->get();

        if ($orders->isEmpty()) {
            abort(404, 'No orders found for the specified delivery date');
        }

        $calculator = new CropPlanCalculatorService();
        $result = $calculator->calculateForOrders($orders);

        $data = [
            'delivery_date' => Carbon::parse($deliveryDate),
            'orders' => $orders,
            'planting_plan' => $result['planting_plan'],
            'calculation_details' => $result['calculation_details'],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('pdf.crop-planting-schedule', $data);
        
        $filename = 'crop-planting-schedule-' . Carbon::parse($deliveryDate)->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Generate crop plans for a specific order
     */
    public function generateCropPlan(Order $order): JsonResponse
    {
        try {
            // Check if order already has crop plans
            if ($order->cropPlans()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order already has crop plans.'
                ], 400);
            }

            // Validate order has items
            if ($order->orderItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has no items to plan for.'
                ], 400);
            }

            // Validate order has delivery date
            if (!$order->delivery_date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order must have a delivery date to generate crop plans.'
                ], 400);
            }

            // Calculate planting requirements
            try {
                $calculator = new CropPlanCalculatorService(app(\App\Services\HarvestYieldCalculator::class));
                $orderDetails = $calculator->calculateForOrder($order);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to calculate planting requirements: ' . $e->getMessage()
                ], 400);
            }

            if (empty($orderDetails['seed_requirements'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No seed requirements found for this order. Products may not be mapped to seed entries.'
                ], 400);
            }

            $createdPlans = [];

            DB::transaction(function() use ($order, $orderDetails, &$createdPlans) {
                foreach ($orderDetails['seed_requirements'] as $seedEntryId => $requirement) {
                    // Find a recipe for this seed entry
                    $recipe = Recipe::whereHas('seedEntry', function($query) use ($seedEntryId) {
                        $query->where('id', $seedEntryId);
                    })->first();

                    if (!$recipe) {
                        Log::warning("No recipe found for seed entry ID: {$seedEntryId}");
                        continue;
                    }

                    // Calculate planting date (assume 14 days before delivery for microgreens)
                    $plantByDate = $order->delivery_date->copy()->subDays(14);
                    
                    // Expected harvest date (day before delivery)
                    $expectedHarvestDate = $order->delivery_date->copy()->subDay();

                    // Create crop plan
                    $cropPlan = CropPlan::create([
                        'order_id' => $order->id,
                        'recipe_id' => $recipe->id,
                        'status' => 'approved', // Auto-approve generated plans
                        'trays_needed' => $requirement['trays_needed'],
                        'grams_needed' => $requirement['grams_needed'],
                        'grams_per_tray' => $requirement['grams_needed'] / max($requirement['trays_needed'], 1),
                        'plant_by_date' => $plantByDate,
                        'expected_harvest_date' => $expectedHarvestDate,
                        'delivery_date' => $order->delivery_date,
                        'calculation_details' => [
                            'items_included' => $requirement['items'],
                            'auto_generated' => true,
                            'generated_at' => now()->toISOString(),
                        ],
                        'order_items_included' => $requirement['items'],
                        'created_by' => auth()->id() ?? 2, // Fallback to admin user
                        'approved_by' => auth()->id() ?? 2,
                        'approved_at' => now(),
                        'notes' => 'Auto-generated crop plan',
                    ]);

                    $createdPlans[] = $cropPlan;
                }
            });

            if (empty($createdPlans)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No crop plans could be created. Missing recipe data.'
                ], 400);
            }

            Log::info("Generated {count} crop plans for order {order_id}", [
                'count' => count($createdPlans),
                'order_id' => $order->id,
                'user_id' => auth()->id() ?? 'guest',
                'plans' => collect($createdPlans)->pluck('id')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Crop plans generated successfully!',
                'data' => [
                    'plans_created' => count($createdPlans),
                    'order_id' => $order->id,
                    'plan_ids' => collect($createdPlans)->pluck('id')->toArray()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate crop plan for order ' . $order->id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate crop plan: ' . $e->getMessage()
            ], 500);
        }
    }
}