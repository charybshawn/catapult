<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository
{
    /**
     * Get all active products with caching
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return Cache::remember('active_products', now()->addMinutes(15), function () {
            return Product::with(['category', 'defaultPhoto', 'priceVariations' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('active', true)
            ->get();
        });
    }

    /**
     * Get all products visible in store with caching
     *
     * @return Collection
     */
    public function getStoreVisibleProducts(): Collection
    {
        return Cache::remember('store_visible_products', now()->addMinutes(15), function () {
            return Product::with(['category', 'defaultPhoto', 'priceVariations' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('active', true)
            ->where('is_visible_in_store', true)
            ->get();
        });
    }

    /**
     * Get products by category with caching
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getProductsByCategory(int $categoryId): Collection
    {
        return Cache::remember('category_products_' . $categoryId, now()->addMinutes(15), function () use ($categoryId) {
            return Product::with(['defaultPhoto', 'priceVariations' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('active', true)
            ->where('category_id', $categoryId)
            ->get();
        });
    }

    /**
     * Search products efficiently
     *
     * @param string $query
     * @return Collection
     */
    public function searchProducts(string $query): Collection
    {
        return Product::with(['category', 'defaultPhoto', 'priceVariations' => function ($query) {
            $query->where('is_active', true);
        }])
        ->where('active', true)
        ->where(function (Builder $builder) use ($query) {
            $builder->where('name', 'like', "%{$query}%")
                   ->orWhere('description', 'like', "%{$query}%");
        })
        ->limit(25) // Limit for performance
        ->get();
    }

    /**
     * Get product with all relationships optimized
     *
     * @param int $id
     * @return Product|null
     */
    public function getProductWithRelationships(int $id): ?Product
    {
        return Cache::remember('product_' . $id, now()->addMinutes(5), function () use ($id) {
            return Product::with([
                'category',
                'photos',
                'priceVariations' => function ($query) {
                    $query->where('is_active', true);
                },
                'productMix'
            ])
            ->find($id);
        });
    }

    /**
     * Clear product cache
     *
     * @param int $productId
     * @return void
     */
    public function clearProductCache(int $productId): void
    {
        Cache::forget('product_' . $productId);
        Cache::forget('active_products');
        Cache::forget('store_visible_products');
        
        // Get the product to clear category cache
        $product = Product::find($productId);
        if ($product) {
            Cache::forget('category_products_' . $product->category_id);
        }
    }
} 