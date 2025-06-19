<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Services\DebugService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms\Components;
use Illuminate\Support\Facades\Log;
use Throwable;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function form(Form $form): Form
    {
        // Use the standard resource form schema to show all sections including price variations
        try {
            return parent::form($form);
        } catch (Throwable $e) {
            // Log the error with our debug service
            DebugService::logError($e, 'ViewProduct::form');
            
            // Create a debug form that just shows basic info
            return $form->schema([
                Components\Section::make('Debug Info')
                    ->description('An error occurred while building the form.')
                    ->schema([
                        Components\Placeholder::make('error')
                            ->label('Error')
                            ->content($e->getMessage()),
                        Components\Placeholder::make('file')
                            ->label('File')
                            ->content($e->getFile() . ':' . $e->getLine()),
                    ]),
            ]);
        }
    }

    /**
     * Get form schema safely with thorough debugging
     */
    private function getSafeFormSchema(): array
    {
        try {
            // Debug the record
            Log::info('ViewProduct: Getting record', [
                'record_exists' => $this->record ? 'yes' : 'no',
                'record_id' => $this->record?->id,
            ]);
            
            // Check if record exists
            if (!$this->record) {
                return [
                    Components\Section::make('Error')
                        ->schema([
                            Components\Placeholder::make('error')
                                ->label('Error')
                                ->content('Record not found'),
                        ]),
                ];
            }
            
            // Debug the record's relations
            if ($this->record) {
                try {
                    // Check productMix directly
                    $hasProductMix = isset($this->record->productMix);
                    $productMixIsNull = $this->record->productMix === null;
                    
                    Log::info('ViewProduct: Checking productMix relation', [
                        'has_product_mix_property' => $hasProductMix ? 'yes' : 'no',
                        'product_mix_is_null' => $productMixIsNull ? 'yes' : 'no',
                    ]);
                    
                    // If we have the product, create a checkpoint for it
                    DebugService::checkpoint($this->record, 'product_record');
                    
                    // Try to access productMix and log what happens
                    if ($hasProductMix && !$productMixIsNull) {
                        DebugService::checkpoint($this->record->productMix, 'product_mix');
                    }
                } catch (Throwable $e) {
                    DebugService::logError($e, 'ViewProduct::getSafeFormSchema - checking relations');
                }
            }
            
            // Create a basic form schema with just the essential information
            return [
                Components\Group::make()
                    ->schema([
                        Components\Section::make('Product Information')
                            ->schema([
                                Components\Placeholder::make('name')
                                    ->content(fn ($record) => $record->name ?? 'N/A'),
                                Components\Placeholder::make('description')
                                    ->content(fn ($record) => $record->description ?? 'N/A'),
                                Components\Placeholder::make('base_price')
                                    ->content(fn ($record) => '$' . number_format($record->base_price ?? 0, 2)),
                                Components\Placeholder::make('id')
                                    ->content(fn ($record) => $record->id ?? 'N/A'),
                            ]),
                    ]),
            ];
        } catch (Throwable $e) {
            DebugService::logError($e, 'ViewProduct::getSafeFormSchema');
            
            // Return an absolute fallback schema
            return [
                Components\Section::make('Error Information')
                    ->schema([
                        Components\Placeholder::make('error')
                            ->label('Error')
                            ->content('An error occurred: ' . $e->getMessage()),
                    ]),
            ];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 