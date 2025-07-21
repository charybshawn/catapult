<?php

namespace App\Services;

use App\Models\CropStage;
use Illuminate\Support\Collection;

class CropStageCache
{
    private static ?Collection $stages = null;
    private static ?array $stagesByCode = null;
    private static ?array $stagesById = null;
    
    /**
     * Get all stages (cached for the request)
     */
    public static function all(): Collection
    {
        if (self::$stages === null) {
            self::$stages = CropStage::all();
            self::buildCaches();
        }
        
        return self::$stages;
    }
    
    /**
     * Find stage by ID (cached)
     */
    public static function find($id): ?CropStage
    {
        if (self::$stagesById === null) {
            self::all(); // This will build the caches
        }
        
        return self::$stagesById[$id] ?? null;
    }
    
    /**
     * Find stage by code (cached)
     */
    public static function findByCode(string $code): ?CropStage
    {
        if (self::$stagesByCode === null) {
            self::all(); // This will build the caches
        }
        
        return self::$stagesByCode[$code] ?? null;
    }
    
    /**
     * Get next stage after the given stage
     */
    public static function getNextStage(CropStage $currentStage): ?CropStage
    {
        return self::all()
            ->where('sort_order', '>', $currentStage->sort_order)
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->first();
    }
    
    /**
     * Get previous stage before the given stage
     */
    public static function getPreviousStage(CropStage $currentStage): ?CropStage
    {
        return self::all()
            ->where('sort_order', '<', $currentStage->sort_order)
            ->where('is_active', true)
            ->sortByDesc('sort_order')
            ->first();
    }
    
    /**
     * Build the ID and code caches
     */
    private static function buildCaches(): void
    {
        self::$stagesById = [];
        self::$stagesByCode = [];
        
        foreach (self::$stages as $stage) {
            self::$stagesById[$stage->id] = $stage;
            self::$stagesByCode[$stage->code] = $stage;
        }
    }
    
    /**
     * Clear the cache (useful for tests or when stages are modified)
     */
    public static function clear(): void
    {
        self::$stages = null;
        self::$stagesById = null;
        self::$stagesByCode = null;
    }
}