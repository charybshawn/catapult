<?php

namespace App\Filament\Resources\CropResource\Actions;

use Filament\Tables\Actions\Action;

/**
 * Crop Batch Debug Action
 * Simple action that opens the view modal where debug info is displayed
 */
class CropBatchDebugAction
{
    public static function make(): Action
    {
        return Action::make('debug')
            ->label('')
            ->icon('heroicon-o-code-bracket')
            ->tooltip('Debug Info')
            ->modalHeading('🔧 Debug Information')
            ->modalDescription('Raw database information and relationships')
            ->modalContent(function ($record) {
                // Fresh query with all relationships loaded to avoid lazy loading violations
                $cropBatch = \App\Models\CropBatch::with([
                    'crops',                            // Load crops first
                    'crops.recipe',                     // Then recipe
                    'crops.recipe.masterSeedCatalog',   // Then nested relationships
                    'crops.recipe.masterCultivar', 
                    'crops.currentStage',
                    'crops.recipe.soilConsumable',
                    'order',
                    'cropPlan',
                    'status'
                ])->find($record->id);
                
                // Debug what's actually loaded
                \Log::info('Loaded relationships:', [
                    'crops_loaded' => $cropBatch->relationLoaded('crops'),
                    'crop_count' => $cropBatch->crops->count(),
                    'first_crop_recipe_loaded' => $cropBatch->crops->first()?->relationLoaded('recipe'),
                ]);
                
                $firstCrop = $cropBatch->crops->first();
                
                return view('filament.crop-batch-modal', [
                    'record' => $cropBatch,
                    'varietyName' => $firstCrop?->recipe?->name ?? 'Unknown Variety',
                    'currentStage' => $firstCrop?->currentStage?->name ?? 'Unknown',
                    'stageAge' => $firstCrop?->stage_age ?? 'Unknown',
                    'totalAge' => $firstCrop?->total_age ?? 'Unknown',
                ]);
            })
            ->modalSubmitAction(false)
            ->modalCancelAction(fn () => \Filament\Actions\Action::make('close')
                ->label('Close')
                ->color('gray'));
    }
}