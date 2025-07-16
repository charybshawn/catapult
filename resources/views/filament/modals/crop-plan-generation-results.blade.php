@php
    $results = session('crop_plan_results', []);
    $success = $results['success'] ?? false;
@endphp

<div class="space-y-6">
    @if($success)
        @php
            $startDate = $results['start_date'] ?? '';
            $endDate = $results['end_date'] ?? '';
            $orderCount = $results['order_count'] ?? 0;
            $planCount = $results['plan_count'] ?? 0;
            $orders = $results['orders'] ?? collect();
            $plans = $results['plans'] ?? collect();
            $plansByOrder = $results['plans_by_order'] ?? collect();
            $varietyBreakdown = $results['variety_breakdown'] ?? collect();
        @endphp

        <!-- Summary Header -->
        <div class="bg-success-50 dark:bg-success-950 border border-success-200 dark:border-success-800 rounded-lg p-4">
            <div class="flex items-center space-x-2">
                <x-heroicon-s-check-circle class="w-5 h-5 text-success-600" />
                <h3 class="text-lg font-semibold text-success-900 dark:text-success-100">
                    Crop Plan Generation Complete
                </h3>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-success-800 dark:text-success-200">Date Range:</span>
                    <span class="text-success-700 dark:text-success-300">{{ $startDate }} to {{ $endDate }}</span>
                </div>
                <div>
                    <span class="font-medium text-success-800 dark:text-success-200">Orders Found:</span>
                    <span class="text-success-700 dark:text-success-300">{{ $orderCount }}</span>
                </div>
                <div>
                    <span class="font-medium text-success-800 dark:text-success-200">Plans Generated:</span>
                    <span class="text-success-700 dark:text-success-300">{{ $planCount }}</span>
                </div>
            </div>
        </div>

        @if($planCount > 0)
            <!-- Plans by Order -->
            <div class="space-y-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Plans by Order</h4>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @foreach($plansByOrder as $orderId => $orderPlans)
                        @php
                            $order = $orders->find($orderId);
                            $customerName = $order?->customer?->contact_name ?? 'Unknown';
                            $harvestDate = $order?->harvest_date?->format('M d, Y') ?? 'Unknown';
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h5 class="font-medium text-gray-900 dark:text-gray-100">
                                        Order #{{ $orderId }} - {{ $customerName }}
                                    </h5>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Harvest: {{ $harvestDate }} • {{ $orderPlans->count() }} plans
                                    </p>
                                </div>
                                <span class="bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 text-xs font-medium px-2 py-1 rounded">
                                    {{ $orderPlans->count() }} plans
                                </span>
                            </div>
                            <div class="mt-2 space-y-1">
                                @foreach($orderPlans as $plan)
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-gray-700 dark:text-gray-300">
                                            {{ $plan->variety?->common_name ?? 'Unknown' }}
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ number_format($plan->grams_needed, 1) }}g • {{ $plan->trays_needed }} trays
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Variety Breakdown -->
            @if($varietyBreakdown->count() > 0)
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Variety Breakdown</h4>
                    <div class="grid grid-cols-2 gap-3 max-h-40 overflow-y-auto">
                        @foreach($varietyBreakdown as $variety => $planCount)
                            <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $variety }}</span>
                                <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded">
                                    {{ $planCount }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @else
            <!-- No Plans Generated -->
            <div class="bg-warning-50 dark:bg-warning-950 border border-warning-200 dark:border-warning-800 rounded-lg p-4">
                <div class="flex items-center space-x-2">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-warning-600" />
                    <h3 class="text-lg font-semibold text-warning-900 dark:text-warning-100">
                        No Plans Generated
                    </h3>
                </div>
                <div class="mt-2 text-sm text-warning-800 dark:text-warning-200">
                    @if($orderCount === 0)
                        <p>No orders found in the date range. You may need to:</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            <li>Generate orders from recurring templates first</li>
                            <li>Check that orders have harvest dates in the next 30 days</li>
                            <li>Verify order statuses are valid (draft, pending, confirmed, in_production)</li>
                        </ul>
                    @else
                        <p>All {{ $orderCount }} orders already have crop plans or couldn't be processed.</p>
                    @endif
                </div>
            </div>
        @endif

    @else
        <!-- Error State -->
        <div class="bg-danger-50 dark:bg-danger-950 border border-danger-200 dark:border-danger-800 rounded-lg p-4">
            <div class="flex items-center space-x-2">
                <x-heroicon-s-x-circle class="w-5 h-5 text-danger-600" />
                <h3 class="text-lg font-semibold text-danger-900 dark:text-danger-100">
                    Error Generating Crop Plans
                </h3>
            </div>
            <div class="mt-2 text-sm text-danger-800 dark:text-danger-200">
                <p>{{ $results['error'] ?? 'An unknown error occurred.' }}</p>
                <p class="mt-1">Please check the logs for more details.</p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Clear session data after displaying
    @if(session()->has('crop_plan_results'))
        @php session()->forget('crop_plan_results'); @endphp
    @endif
</script>
@endpush