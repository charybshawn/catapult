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

Route::middleware('auth:sanctum')->get('/products/{product}/price', function (Request $request, \App\Models\Product $product) {
    $customerType = $request->get('customer_type', 'retail');
    
    return response()->json([
        'price' => $product->getPriceForCustomerType($customerType),
        'product_id' => $product->id,
        'customer_type' => $customerType,
    ]);
});

Route::middleware('auth:sanctum')->get('/products/{product}/price-variations', function (Request $request, \App\Models\Product $product) {
    $priceVariations = $product->priceVariations()
        ->where('is_active', true)
        ->with('packagingType')
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get()
        ->map(function ($variation) {
            return [
                'id' => $variation->id,
                'name' => $variation->name,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'fill_weight_grams' => $variation->fill_weight_grams,
                'is_default' => $variation->is_default,
                'packaging_type' => $variation->packagingType?->name,
            ];
        });
    
    return response()->json($priceVariations);
});

Route::middleware('auth:sanctum')->post('/price-variations', function (Request $request) {
    $validated = $request->validate([
        'product_id' => 'required|exists:products,id',
        'name' => 'required|string|max:255',
        'sku' => 'nullable|string|max:255',
        'price' => 'required|numeric|min:0',
        'fill_weight_grams' => 'nullable|numeric|min:0',
        'packaging_type_id' => 'nullable|exists:packaging_types,id',
        'is_default' => 'boolean',
        'is_active' => 'boolean'
    ]);

    $priceVariation = \App\Models\PriceVariation::create($validated);
    
    return response()->json([
        'id' => $priceVariation->id,
        'name' => $priceVariation->name,
        'sku' => $priceVariation->sku,
        'price' => $priceVariation->price,
        'fill_weight_grams' => $priceVariation->fill_weight_grams,
        'is_default' => $priceVariation->is_default,
        'packaging_type' => $priceVariation->packagingType?->name,
    ], 201);
});

Route::middleware('auth:sanctum')->get('/packaging-types', function (Request $request) {
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