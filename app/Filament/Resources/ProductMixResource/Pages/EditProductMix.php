<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditProductMix extends BaseEditRecord
{
    protected static string $resource = ProductMixResource::class;
    
    protected array $formData = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Explicitly load the masterSeedCatalogs relationship data
        $components = $this->record->masterSeedCatalogs()
            ->withPivot('percentage', 'cultivar', 'recipe_id')
            ->get();
            
        $data['masterSeedCatalogs'] = $components->map(function ($catalog) {
            $cultivar = $catalog->pivot->cultivar ?: 'Unknown';
            return [
                'master_seed_catalog_id' => $catalog->id,
                'cultivar' => $cultivar,
                'percentage' => floatval($catalog->pivot->percentage),
                'recipe_id' => $catalog->pivot->recipe_id,
                'variety_selection' => $catalog->id . '|' . $cultivar,
            ];
        })->toArray();
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->tooltip('Delete mix'),
        ];
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store the form data for use in afterSave
        $this->formData = $data;
        
        // Log the data to debug
        Log::info('ProductMix save data:', [
            'data' => $data,
            'this_data_masterSeedCatalogs' => $this->data['masterSeedCatalogs'] ?? [],
            'form_data_masterSeedCatalogs' => $data['masterSeedCatalogs'] ?? []
        ]);
        
        // Validate the percentages add up to 100
        $this->validatePercentages();
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Use the stored form data instead of $this->data
        $components = $this->formData['masterSeedCatalogs'] ?? [];
        $syncData = [];
        
        foreach ($components as $component) {
            // Handle both new and existing components
            $catalogId = null;
            $cultivar = null;
            $percentage = null;
            $recipeId = null;
            
            // Check if this is a new component (has variety_selection)
            if (isset($component['variety_selection']) && $component['variety_selection']) {
                [$catalogId, $cultivar] = explode('|', $component['variety_selection']);
                $percentage = $component['percentage'] ?? null;
                $recipeId = $component['recipe_id'] ?? null;
            } else {
                // This is existing data
                $catalogId = $component['master_seed_catalog_id'] ?? null;
                $cultivar = $component['cultivar'] ?? null;
                $percentage = $component['percentage'] ?? null;
                $recipeId = $component['recipe_id'] ?? null;
            }
            
            if ($catalogId && $percentage) {
                $syncData[$catalogId] = [
                    'percentage' => $percentage,
                    'cultivar' => $cultivar,
                    'recipe_id' => $recipeId,
                ];
            }
        }
        
        Log::info('Syncing components:', [
            'raw_data' => $components,
            'sync_data' => $syncData
        ]);
        
        $this->record->masterSeedCatalogs()->sync($syncData);
    }
    
    protected function validatePercentages(): void
    {
        $components = $this->data['masterSeedCatalogs'] ?? [];
        $total = 0;
        
        foreach ($components as $component) {
            if (isset($component['percentage']) && is_numeric($component['percentage'])) {
                $total += floatval($component['percentage']);
            }
        }
        
        // Round to 2 decimal places to match database precision
        $total = round($total, 2);
        
        // Allow for very small floating point differences
        if (abs($total - 100) > 0.01) {
            Notification::make()
                ->title('Invalid Mix Percentages')
                ->body('The total percentage must equal 100%. Current total: ' . number_format($total, 2) . '%')
                ->danger()
                ->persistent()
                ->send();
                
            $this->halt();
        }
    }
} 