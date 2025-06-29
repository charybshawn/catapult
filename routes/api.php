<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['web', 'auth'])->get('/products/{product}/price', function (Request $request, \App\Models\Product $product) {
    $customerType = $request->get('customer_type', 'retail');
    
    return response()->json([
        'price' => $product->getPriceForCustomerType($customerType),
        'product_id' => $product->id,
        'customer_type' => $customerType,
    ]);
});

Route::middleware(['web', 'auth'])->get('/products/{product}/price-variations', function (Request $request, \App\Models\Product $product) {
    $customerId = $request->get('customer_id');
    $customer = $customerId ? \App\Models\User::find($customerId) : null;
    
    $priceVariations = $product->priceVariations()
        ->where('is_active', true)
        ->with('packagingType')
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get()
        ->map(function ($variation) use ($product, $customer) {
            $basePrice = $variation->price;
            $finalPrice = $basePrice;
            
            // Apply customer-specific wholesale pricing if applicable
            if ($customer && $customer->isWholesaleCustomer()) {
                $discountPercentage = $customer->getWholesaleDiscountPercentage($product);
                if ($discountPercentage > 0) {
                    $discountAmount = $basePrice * ($discountPercentage / 100);
                    $finalPrice = $basePrice - $discountAmount;
                }
            }
            
            return [
                'id' => $variation->id,
                'name' => $variation->name,
                'sku' => $variation->sku,
                'price' => $finalPrice,
                'base_price' => $basePrice,
                'discount_percentage' => $customer ? $customer->getWholesaleDiscountPercentage($product) : 0,
                'fill_weight_grams' => $variation->fill_weight,
                'is_default' => $variation->is_default,
                'packaging_type' => $variation->packagingType?->name,
            ];
        });
    
    return response()->json($priceVariations);
});

Route::middleware(['web', 'auth'])->post('/price-variations', function (Request $request) {
    $isGlobal = $request->boolean('is_global', false);
    $validated = $request->validate(\App\Models\PriceVariation::rules($isGlobal));

    $priceVariation = \App\Models\PriceVariation::create($validated);
    
    return response()->json([
        'id' => $priceVariation->id,
        'name' => $priceVariation->name,
        'sku' => $priceVariation->sku,
        'price' => $priceVariation->price,
        'fill_weight_grams' => $priceVariation->fill_weight,
        'is_default' => $priceVariation->is_default,
        'is_global' => $priceVariation->is_global,
        'packaging_type' => $priceVariation->packagingType?->name,
    ], 201);
});

Route::middleware(['web', 'auth'])->get('/packaging-types', function (Request $request) {
    $packagingTypes = \App\Models\PackagingType::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name', 'display_name'])
        ->map(function ($packaging) {
            return [
                'id' => $packaging->id,
                'name' => $packaging->display_name ?: $packaging->name,
            ];
        });
    
    return response()->json($packagingTypes);
});

 