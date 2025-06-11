<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CropPlanCalculatorService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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
}