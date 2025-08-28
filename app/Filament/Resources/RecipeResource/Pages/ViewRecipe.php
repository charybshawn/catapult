<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\EditAction;
use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Infolists;
use Filament\Resources\Pages\ViewRecord;

class ViewRecipe extends ViewRecord
{
    protected static string $resource = RecipeResource::class;
    
    public function infolist(Schema $schema): Schema
    {
        return $infolist
            ->schema([
                Section::make('Recipe Information')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Recipe Name'),
                            
                        Grid::make()
                            ->schema([
                                TextEntry::make('seedConsumable.name')
                                    ->label('Seed')
                                    ->default('Not specified'),
                                
                                TextEntry::make('seedConsumable.details')
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
                                        return $record->seedCultivar ? $record->seedCultivar->name : null;
                                    })
                                    ->visible(fn ($record) => $record->seedConsumable || $record->seedCultivar),
                            ])
                            ->columns(1),
                            
                        Grid::make()
                            ->schema([
                                TextEntry::make('soilConsumable.name')
                                    ->label('Soil')
                                    ->default('Not specified'),
                                
                                TextEntry::make('soilConsumable.details')
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
                            
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                Section::make('Growth Parameters')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('seed_soak_hours')
                                    ->label('Seed Soak')
                                    ->formatStateUsing(fn ($state) => $state . ' hours'),
                                
                                TextEntry::make('germination_days')
                                    ->label('Germination'),
                                    
                                TextEntry::make('blackout_days')
                                    ->label('Blackout'),
                                    
                                TextEntry::make('light_days')
                                    ->label('Light'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('totalDays')
                                    ->label('Total Days')
                                    ->state(function (Recipe $record): int {
                                        return $record->totalDays();
                                    }),
                                
                                TextEntry::make('effectivelyTotalDays')
                                    ->label('Days to Harvest')
                                    ->state(function (Recipe $record): float {
                                        return $record->effectiveTotalDays();
                                    })
                                    ->numeric(1),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('seed_density_grams_per_tray')
                                    ->label('Seed Density')
                                    ->suffix(' g/tray')
                                    ->numeric(1),
                                
                                TextEntry::make('expected_yield_grams')
                                    ->label('Expected Yield')
                                    ->suffix(' g/tray')
                                    ->numeric(0),
                            ]),
                    ]),
                    
                Section::make('Growth Phase Details')
                    ->schema([
                        Tabs::make('Growth Phases')
                            ->tabs([
                                Tab::make('Planting')
                                    ->schema([
                                        TextEntry::make('planting_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Tab::make('Germination')
                                    ->schema([
                                        TextEntry::make('germination_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Tab::make('Blackout')
                                    ->schema([
                                        TextEntry::make('blackout_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Tab::make('Light')
                                    ->schema([
                                        TextEntry::make('light_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                                
                                Tab::make('Harvesting')
                                    ->schema([
                                        TextEntry::make('harvesting_notes')
                                            ->label('')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
                    
                Section::make('Watering Schedule')
                    ->schema([
                        TextEntry::make('watering_instructions')
                            ->label('Instructions')
                            ->state('The watering schedule shows day-by-day water amounts for each growth phase.')
                            ->columnSpanFull(),
                        
                        TextEntry::make('wateringSchedule')
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
            EditAction::make(),
        ];
    }
} 