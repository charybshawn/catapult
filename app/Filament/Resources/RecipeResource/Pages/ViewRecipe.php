<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewRecipe extends ViewRecord
{
    protected static string $resource = RecipeResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Recipe Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Recipe Name'),
                            
                        Infolists\Components\Grid::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('seedConsumable.name')
                                    ->label('Seed')
                                    ->default('Not specified'),
                                
                                Infolists\Components\TextEntry::make('seedConsumable.details')
                                    ->label('Seed Details')
                                    ->state(function ($record) {
                                        if ($record->seedConsumable) {
                                            $seed = $record->seedConsumable;
                                            $info = "{$seed->current_stock} {$seed->unit} available";
                                            if ($seed->total_quantity && $seed->quantity_unit) {
                                                $info .= " ({$seed->total_quantity} {$seed->quantity_unit} total)";
                                            }
                                            return $info;
                                        }
                                        return $record->seedVariety ? "Legacy: {$record->seedVariety->name}" : null;
                                    })
                                    ->visible(fn ($record) => $record->seedConsumable || $record->seedVariety),
                            ])
                            ->columns(1),
                            
                        Infolists\Components\Grid::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('soilConsumable.name')
                                    ->label('Soil')
                                    ->default('Not specified'),
                                
                                Infolists\Components\TextEntry::make('soilConsumable.details')
                                    ->label('Soil Details')
                                    ->state(function ($record) {
                                        if ($record->soilConsumable) {
                                            $soil = $record->soilConsumable;
                                            $info = "{$soil->current_stock} {$soil->unit} available";
                                            if ($soil->total_quantity && $soil->quantity_unit) {
                                                $info .= " ({$soil->total_quantity} {$soil->quantity_unit} total)";
                                            }
                                            return $info;
                                        }
                                        return $record->soilSupplier ? "Legacy: {$record->soilSupplier->name}" : null;
                                    })
                                    ->visible(fn ($record) => $record->soilConsumable || $record->soilSupplier),
                            ])
                            ->columns(1),
                            
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Growth Parameters')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('seed_soak_hours')
                                    ->label('Seed Soak')
                                    ->formatStateUsing(fn ($state) => $state . ' hours'),
                                
                                Infolists\Components\TextEntry::make('germination_days')
                                    ->label('Germination'),
                                    
                                Infolists\Components\TextEntry::make('blackout_days')
                                    ->label('Blackout'),
                                    
                                Infolists\Components\TextEntry::make('light_days')
                                    ->label('Light'),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('totalDays')
                                    ->label('Total Days')
                                    ->state(function (Recipe $record): int {
                                        return $record->totalDays();
                                    }),
                                
                                Infolists\Components\TextEntry::make('effectivelyTotalDays')
                                    ->label('Days to Harvest')
                                    ->state(function (Recipe $record): float {
                                        return $record->effectiveTotalDays();
                                    })
                                    ->numeric(1),
                            ]),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('seed_density_grams_per_tray')
                                    ->label('Seed Density')
                                    ->suffix(' g/tray')
                                    ->numeric(1),
                                
                                Infolists\Components\TextEntry::make('expected_yield_grams')
                                    ->label('Expected Yield')
                                    ->suffix(' g/tray')
                                    ->numeric(0),
                            ]),
                    ]),
                    
                Infolists\Components\Section::make('Growth Phase Details')
                    ->schema([
                        Infolists\Components\Tabs::make('Growth Phases')
                            ->tabs([
                                Infolists\Components\Tabs\Tab::make('Planting')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('planting_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Infolists\Components\Tabs\Tab::make('Germination')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('germination_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Infolists\Components\Tabs\Tab::make('Blackout')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('blackout_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Infolists\Components\Tabs\Tab::make('Light')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('light_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Infolists\Components\Tabs\Tab::make('Harvesting')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('harvesting_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
                    
                Infolists\Components\Section::make('Watering Schedule')
                    ->schema([
                        Infolists\Components\TextEntry::make('watering_instructions')
                            ->label('Instructions')
                            ->state('The watering schedule shows day-by-day water amounts for each growth phase.')
                            ->columnSpanFull(),
                        
                        Infolists\Components\TextEntry::make('wateringSchedule')
                            ->label('Watering Schedule')
                            ->formatStateUsing(function ($record) {
                                if (!$record->wateringSchedule || $record->wateringSchedule->isEmpty()) {
                                    return 'No watering schedule defined.';
                                }
                                
                                $schedule = [];
                                foreach ($record->wateringSchedule as $entry) {
                                    $fert = $entry->needs_liquid_fertilizer ? ' + Fertilizer' : '';
                                    $notes = !empty($entry->notes) ? " ({$entry->notes})" : '';
                                    $schedule[] = "Day {$entry->day_number}: {$entry->water_amount_ml} ml " . 
                                        ucfirst($entry->watering_method) . $fert . $notes;
                                }
                                
                                return implode('<br>', $schedule);
                            })
                            ->extraAttributes(fn (Recipe $record) => [
                                'data-markdown' => $record->wateringSchedule->isNotEmpty(),
                            ])
                            ->columnSpanFull()
                            ->visible(fn (Recipe $record) => $record->wateringSchedule->isNotEmpty()),
                    ])
                    ->collapsible()
                    ->collapsed(fn (Recipe $record) => $record->wateringSchedule->isEmpty()),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 