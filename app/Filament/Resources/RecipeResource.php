<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\RelationManagers;
use App\Models\Recipe;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\LogOptions;
use App\Services\LotInventoryService;
use App\Models\Consumable;

class RecipeResource extends BaseResource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Recipes';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Get available lots for selection with formatted display names.
     * 
     * @return array
     */
    public static function getAvailableLotsForSelection(): array
    {
        $lotInventoryService = app(\App\Services\InventoryManagementService::class);
        $lotNumbers = $lotInventoryService->getAllLotNumbers();
        $options = [];
        
        foreach ($lotNumbers as $lotNumber) {
            $summary = $lotInventoryService->getLotSummary($lotNumber);
            
            // Skip depleted lots
            if ($summary['available'] <= 0) {
                continue;
            }
            
            // Get the first consumable entry for this lot to get seed info
            $seedTypeId = $lotInventoryService->getSeedTypeId();
            if (!$seedTypeId) {
                continue;
            }
            
            $consumable = Consumable::where('consumable_type_id', $seedTypeId)
                ->where('lot_no', $lotNumber)
                ->where('is_active', true)
                ->first();
            
            if ($consumable) {
                $unit = $consumable->quantity_unit ?? 'g';
                $seedName = $consumable->name ?? 'Unknown Seed';
                $available = $summary['available'];
                
                // Format: "LOT123 (1500g available) - Broccoli (Broccoli)"
                $label = "{$lotNumber} ({$available}{$unit} available) - {$seedName}";
                $options[$lotNumber] = $label;
            }
        }
        
        return $options;
    }

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Recipe Information')
                ->schema([
                    Forms\Components\Select::make('master_seed_catalog_id')
                        ->label('Variety')
                        ->relationship('masterSeedCatalog', 'common_name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, ?Recipe $record) {
                            if ($state && !$record) {
                                // When creating new recipe, set common_name based on selection
                                $catalog = \App\Models\MasterSeedCatalog::find($state);
                                if ($catalog) {
                                    $set('common_name', $catalog->common_name);
                                }
                            }
                            // Reset cultivar when variety changes
                            $set('master_cultivar_id', null);
                        }),

                    Forms\Components\Select::make('master_cultivar_id')
                        ->label('Cultivar')
                        ->options(function (callable $get) {
                            $catalogId = $get('master_seed_catalog_id');
                            if (!$catalogId) {
                                return [];
                            }
                            return \App\Models\MasterCultivar::where('master_seed_catalog_id', $catalogId)
                                ->where('is_active', true)
                                ->pluck('cultivar_name', 'id');
                        })
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state) {
                                $cultivar = \App\Models\MasterCultivar::find($state);
                                if ($cultivar) {
                                    $set('cultivar_name', $cultivar->cultivar_name);
                                    // Update recipe name
                                    $catalog = \App\Models\MasterSeedCatalog::find($get('master_seed_catalog_id'));
                                    if ($catalog && $cultivar) {
                                        $name = $catalog->common_name . ' (' . $cultivar->cultivar_name . ')';
                                        $set('name', $name);
                                    }
                                }
                            }
                        }),

                    Forms\Components\Hidden::make('common_name'),
                    Forms\Components\Hidden::make('cultivar_name'),

                    Forms\Components\Select::make('lot_number')
                        ->label('Seed Lot')
                        ->options(fn() => self::getAvailableLotsForSelection())
                        ->searchable()
                        ->nullable()
                        ->helperText('Shows only lots with available stock')
                        ->rules([
                            'nullable', 
                            'string', 
                            'max:255',
                            function ($attribute, $value, $fail) {
                                if ($value) {
                                    $lotInventoryService = app(\App\Services\InventoryManagementService::class);
                                    
                                    // Check if lot exists
                                    if (!$lotInventoryService->lotExists($value)) {
                                        $fail("The selected lot '{$value}' does not exist.");
                                        return;
                                    }
                                    
                                    // Check if lot has available stock
                                    if ($lotInventoryService->isLotDepleted($value)) {
                                        $fail("The selected lot '{$value}' is depleted and cannot be used.");
                                        return;
                                    }
                                    
                                    // Check if lot has sufficient quantity for typical recipe requirements
                                    $availableQuantity = $lotInventoryService->getLotQuantity($value);
                                    if ($availableQuantity < 10) { // Minimum 10g threshold
                                        $fail("The selected lot '{$value}' has insufficient stock (" . round($availableQuantity, 1) . "g available). Minimum 10g required.");
                                    }
                                }
                            }
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            // Clear lot_depleted_at when lot is changed
                            if ($state && $state !== $get('lot_number')) {
                                $set('lot_depleted_at', null);
                            }
                        })
                        ->suffixAction(
                            Forms\Components\Actions\Action::make('refresh_lots')
                                ->icon('heroicon-o-arrow-path')
                                ->tooltip('Refresh available lots')
                                ->action(function ($livewire) {
                                    $livewire->dispatch('refresh-form');
                                })
                        ),

                    // Seed Consumable Selection (unhidden for proper form functionality)
                    Forms\Components\Select::make('seed_consumable_id')
                        ->label('Seed Consumable')
                        ->relationship('seedConsumable', 'name', function ($query) {
                            return $query->whereHas('consumableType', function ($q) {
                                $q->where('name', 'like', '%seed%');
                            });
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Select the seed consumable for this recipe'),

                    // Show lot status information when editing
                    Forms\Components\Placeholder::make('lot_status')
                        ->label('Current Lot Status')
                        ->content(function (Forms\Get $get, ?Recipe $record) {
                            if (!$record || !$record->lot_number) {
                                return 'No lot assigned';
                            }
                            
                            $lotInventoryService = app(\App\Services\InventoryManagementService::class);
                            $summary = $lotInventoryService->getLotSummary($record->lot_number);
                            
                            if ($summary['available'] <= 0) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-2 text-sm text-red-600">' .
                                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' .
                                    '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>' .
                                    '</svg>' .
                                    "Lot {$record->lot_number} is depleted (0 available)" .
                                    '</div>'
                                );
                            }
                            
                            $seedTypeId = $lotInventoryService->getSeedTypeId();
                            $consumable = null;
                            if ($seedTypeId) {
                                $consumable = Consumable::where('consumable_type_id', $seedTypeId)
                                    ->where('lot_no', $record->lot_number)
                                    ->where('is_active', true)
                                    ->first();
                            }
                            
                            if (!$consumable) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-2 text-sm text-red-600">' .
                                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' .
                                    '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>' .
                                    '</svg>' .
                                    "Lot {$record->lot_number} not found" .
                                    '</div>'
                                );
                            }
                            
                            $unit = $consumable->quantity_unit ?? 'g';
                            $available = $summary['available'];
                            
                            // Check if lot is running low (less than 20% remaining)
                            $totalOriginal = $summary['total'];
                            $percentRemaining = $totalOriginal > 0 ? ($available / $totalOriginal) * 100 : 0;
                            
                            if ($percentRemaining < 20) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex items-center gap-2 text-sm text-yellow-600">' .
                                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' .
                                    '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>' .
                                    '</svg>' .
                                    "Lot {$record->lot_number}: {$available}{$unit} available (Low stock: " . round($percentRemaining, 1) . "% remaining)" .
                                    '</div>'
                                );
                            }
                            
                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex items-center gap-2 text-sm text-green-600">' .
                                '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">' .
                                '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' .
                                '</svg>' .
                                "Lot {$record->lot_number}: {$available}{$unit} available (" . round($percentRemaining, 1) . "% remaining)" .
                                '</div>'
                            );
                        })
                        ->visible(fn(?Recipe $record) => $record && $record->lot_number),


                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Growing Parameters')
                ->schema([
                    Forms\Components\TextInput::make('days_to_maturity')
                        ->label('Days to Maturity (DTM)')
                        ->helperText('Total days from planting to harvest')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(12)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            // Calculate light days
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($state ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('seed_soak_hours')
                        ->label('Seed Soak Hours')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\TextInput::make('germination_days')
                        ->label('Germination Days')
                        ->helperText('Days in germination stage')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(3)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($state ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('blackout_days')
                        ->label('Blackout Days')
                        ->helperText('Days in blackout stage')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(2)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($state ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('light_days')
                        ->label('Light Days')
                        ->helperText('Automatically calculated from DTM - (germination + blackout)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, callable $set, Forms\Get $get) {
                            // Calculate initial value when form loads
                            if ($get('days_to_maturity')) {
                                $germ = floatval($get('germination_days') ?? 0);
                                $blackout = floatval($get('blackout_days') ?? 0);
                                $dtm = floatval($get('days_to_maturity') ?? 0);
                                
                                $lightDays = max(0, $dtm - ($germ + $blackout));
                                $set('light_days', $lightDays);
                            }
                        }),
                    
                    Forms\Components\TextInput::make('seed_density_grams_per_tray')
                        ->label('Seed Density (g/tray)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(25)
                        ->required(),
                        
                    Forms\Components\TextInput::make('expected_yield_grams')
                        ->label('Expected Yield (g/tray)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01),
                ])
                ->columns(2),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'seedConsumable', // Keep for backward compatibility
                'soilConsumable'
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                    
                Tables\Columns\TextColumn::make('lot_number')
                    ->label('Seed Lot')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            // Fall back to old seedConsumable relationship for backward compatibility
                            if ($record->seedConsumable) {
                                $available = max(0, $record->seedConsumable->total_quantity - $record->seedConsumable->consumed_quantity);
                                $unit = $record->seedConsumable->quantity_unit ?? 'g';
                                return $record->seedConsumable->name . " ({$available} {$unit} available)";
                            }
                            return '-';
                        }
                        
                        $lotInventoryService = app(\App\Services\InventoryManagementService::class);
                        $summary = $lotInventoryService->getLotSummary($state);
                        
                        if ($summary['available'] <= 0) {
                            return "{$state} (Depleted)";
                        }
                        
                        $seedTypeId = app(\App\Services\InventoryManagementService::class)->getSeedTypeId();
                        $consumable = $seedTypeId ? Consumable::where('consumable_type_id', $seedTypeId)
                            ->where('lot_no', $state)
                            ->where('is_active', true)
                            ->first() : null;
                        
                        if (!$consumable) {
                            return "{$state} (Not Found)";
                        }
                        
                        $unit = $consumable->quantity_unit ?? 'g';
                        $available = $summary['available'];
                        $seedName = $consumable->name ?? 'Unknown';
                        
                        return "{$state} ({$available}{$unit}) - {$seedName}";
                    }),
                    
                Tables\Columns\TextColumn::make('soilConsumable.name')
                    ->label('Soil')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('totalDays')
                    ->label('Total Days')
                    ->getStateUsing(fn (Recipe $record): float => $record->totalDays())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(germination_days + blackout_days + light_days) ' . $direction);
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('days_to_maturity')
                    ->label('DTM')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('seed_density_grams_per_tray')
                    ->label('Seed Density (g)')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('expected_yield_grams')
                    ->label('Yield (g)')
                    ->numeric(0)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('germination_days')
                    ->label('Germ. Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('blackout_days')
                    ->label('Blackout Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('light_days')
                    ->label('Light Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        'unit' => 'Inactive',
                    ]),
                    
                Tables\Filters\SelectFilter::make('lot_number')
                    ->label('Seed Lot')
                    ->options(function () {
                        $lotInventoryService = app(\App\Services\InventoryManagementService::class);
                        $lotNumbers = $lotInventoryService->getAllLotNumbers();
                        $options = [];
                        
                        foreach ($lotNumbers as $lotNumber) {
                            $summary = $lotInventoryService->getLotSummary($lotNumber);
                            $seedTypeId = $lotInventoryService->getSeedTypeId();
                            
                            if (!$seedTypeId) {
                                continue;
                            }
                            
                            $consumable = Consumable::where('consumable_type_id', $seedTypeId)
                                ->where('lot_no', $lotNumber)
                                ->where('is_active', true)
                                ->first();
                            
                            if ($consumable) {
                                $unit = $consumable->quantity_unit ?? 'g';
                                $seedName = $consumable->name ?? 'Unknown';
                                $available = $summary['available'];
                                $status = $available > 0 ? "({$available}{$unit})" : "(Depleted)";
                                
                                $options[$lotNumber] = "{$lotNumber} {$status} - {$seedName}";
                            }
                        }
                        
                        return $options;
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit recipe'),
                Tables\Actions\Action::make('clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->tooltip('Clone recipe')
                    ->action(function (Recipe $record) {
                        $clone = $record->replicate();
                        $clone->name = $record->name . ' (Clone)';
                        $clone->save();
                        
                        // Clone related records
                        foreach ($record->stages as $stage) {
                            $stageClone = $stage->replicate();
                            $stageClone->recipe_id = $clone->id;
                            $stageClone->save();
                        }
                        
                        foreach ($record->wateringSchedule as $schedule) {
                            $scheduleClone = $schedule->replicate();
                            $scheduleClone->recipe_id = $clone->id;
                            $scheduleClone->save();
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Recipe cloned successfully')
                            ->send();
                            
                        return redirect()->route('filament.admin.resources.recipes.edit', ['record' => $clone->id]);
                    }),
                Tables\Actions\Action::make('updateGrows')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Apply to Grows')
                    ->tooltip('Apply recipe parameters to existing grows')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Apply Recipe Parameters to Existing Grows')
                    ->modalDescription(fn (Recipe $record) => "This will update existing grows using {$record->name} with the current recipe parameters.")
                    ->form([
                        Forms\Components\Select::make('current_stage')
                            ->label('Current Stage Filter')
                            ->options([
                                'all' => 'All Stages',
                                'germination' => 'Germination Only',
                                'blackout' => 'Blackout Only',
                                'light' => 'Light Only',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('affected_grows_count', null)),
                            
                        Forms\Components\Placeholder::make('affected_grows_count')
                            ->label('Affected Grows')
                            ->content(function (Recipe $record, Forms\Get $get) {
                                $stage = $get('current_stage');
                                
                                $query = \App\Models\Crop::where('recipe_id', $record->id)
                                    ->where('current_stage', '!=', 'harvested');
                                
                                if ($stage !== 'all') {
                                    $query->where('current_stage', $stage);
                                }
                                
                                $count = $query->count();
                                
                                if ($count === 0) {
                                    return "No active grows found for this recipe";
                                }
                                
                                return "{$count} grows will be affected";
                            }),
                            
                        Forms\Components\Checkbox::make('update_germination_days')
                            ->label('Update Germination Days'),
                            
                        Forms\Components\Checkbox::make('update_blackout_days')
                            ->label('Update Blackout Days'),
                            
                        Forms\Components\Checkbox::make('update_light_days')
                            ->label('Update Light Days'),
                            
                        Forms\Components\Checkbox::make('update_days_to_maturity')
                            ->label('Update Days to Maturity'),
                            
                        Forms\Components\Checkbox::make('update_expected_harvest_dates')
                            ->label('Update Expected Harvest Dates')
                            ->helperText('This will recalculate harvest dates based on the recipe settings'),
                            
                        Forms\Components\Checkbox::make('confirm_updates')
                            ->label('I understand this will modify existing grows')
                            ->required()
                            ->helperText('This action cannot be undone.'),
                    ])
                    ->action(function (Recipe $record, array $data) {
                        if (!isset($data['confirm_updates']) || !$data['confirm_updates']) {
                            Notification::make()
                                ->title('Confirmation Required')
                                ->body('You must confirm that you understand this action will modify existing grows.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Build the query for crops to update
                        $query = \App\Models\Crop::where('recipe_id', $record->id)
                            ->where('current_stage', '!=', 'harvested');
                        
                        if ($data['current_stage'] !== 'all') {
                            $query->where('current_stage', $data['current_stage']);
                        }
                        
                        // Get the crops to update
                        $crops = $query->get();
                        
                        if ($crops->isEmpty()) {
                            Notification::make()
                                ->title('No Grows Found')
                                ->body('No active grows found for the selected recipe and stage.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Track counters for notification
                        $totalCrops = $crops->count();
                        $updatedCrops = 0;
                        
                        // Begin a database transaction
                        \Illuminate\Support\Facades\DB::beginTransaction();
                        
                        try {
                            foreach ($crops as $crop) {
                                $needsUpdate = false;
                                
                                // Check if we need to update expected harvest date
                                $recalculateHarvestDate = $data['update_expected_harvest_dates'] ?? false;
                                
                                // Update the crop based on options selected
                                if (($data['update_germination_days'] ?? false) && $crop->current_stage === 'germination') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in germination stage
                                    if ($crop->germination_at) {
                                        $stageEnd = $crop->germination_at->copy()->addDays($record->germination_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_blackout_days'] ?? false) && $crop->current_stage === 'blackout') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in blackout stage
                                    if ($crop->blackout_at) {
                                        $stageEnd = $crop->blackout_at->copy()->addDays($record->blackout_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_light_days'] ?? false) && $crop->current_stage === 'light') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in light stage
                                    if ($crop->light_at) {
                                        $stageEnd = $crop->light_at->copy()->addDays($record->light_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_days_to_maturity'] ?? false) || $recalculateHarvestDate) {
                                    $needsUpdate = true;
                                    // Recalculate expected harvest date
                                    if ($crop->planting_at && $record->days_to_maturity) {
                                        $crop->expected_harvest_at = $crop->planting_at->copy()->addDays($record->days_to_maturity);
                                    }
                                }
                                
                                // Save the crop if any changes were made
                                if ($needsUpdate) {
                                    $crop->save();
                                    $updatedCrops++;
                                }
                            }
                            
                            // Commit the transaction
                            \Illuminate\Support\Facades\DB::commit();
                            
                            Notification::make()
                                ->title('Grows Updated Successfully')
                                ->body("Updated {$updatedCrops} out of {$totalCrops} grows to match recipe settings.")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            // Rollback the transaction on error
                            \Illuminate\Support\Facades\DB::rollBack();
                            
                            Notification::make()
                                ->title('Error Updating Grows')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete recipe')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Recipe')
                    ->modalDescription('Are you sure you want to delete this recipe?')
                    ->before(function (Tables\Actions\DeleteAction $action, Recipe $record) {
                        // Check if recipe has ACTIVE crops specifically
                        $activeCropsCount = $record->crops()->where('current_stage', '!=', 'harvested')->count();
                        $totalCropsCount = $record->crops()->count();
                        
                        if ($activeCropsCount > 0) {
                            // PREVENT deletion when there are active crops
                            $action->cancel();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete Recipe')
                                ->body(
                                    "This recipe cannot be deleted because it has {$activeCropsCount} active crops in progress." .
                                    '<br><br>Please harvest or remove the active crops first, or consider deactivating the recipe instead.'
                                )
                                ->danger()
                                ->persistent()
                                ->send();
                        } else if ($totalCropsCount > 0) {
                            // PREVENT deletion when there are completed crops (to preserve history)
                            $action->cancel();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Delete Recipe')
                                ->body(
                                    "This recipe cannot be deleted because it has {$totalCropsCount} completed crops in the system." .
                                    '<br><br>Deleting this recipe would remove valuable historical crop data. Consider deactivating the recipe instead.'
                                )
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                        // If no crops exist, allow normal deletion to proceed
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Deactivate Recipe')
                    ->modalDescription('This will deactivate the recipe, making it unavailable for new crops while preserving existing data.')
                    ->action(function (Recipe $record) {
                        $record->update(['is_active' => false]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Recipe Deactivated')
                            ->body("'{$record->name}' has been deactivated.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Recipe $record) => $record->is_active ?? true),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function (Recipe $record) {
                        $record->update(['is_active' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Recipe Activated')
                            ->body("'{$record->name}' has been activated.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Recipe $record) => !($record->is_active ?? true)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Recipes')
                        ->modalDescription('Are you sure you want to delete the selected recipes?')
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            // Check if any of the selected recipes have crops
                            $recipesWithActiveCrops = $records->filter(function ($record) {
                                return $record->crops()->where('current_stage', '!=', 'harvested')->count() > 0;
                            });
                            
                            $recipesWithCrops = $records->filter(function ($record) {
                                return $record->crops()->count() > 0;
                            });
                            
                            if ($recipesWithActiveCrops->isNotEmpty()) {
                                // PREVENT bulk deletion when there are active crops
                                $action->cancel();
                                
                                $activeCropsCount = $recipesWithActiveCrops->map(fn ($recipe) => $recipe->crops()->where('current_stage', '!=', 'harvested')->count())->sum();
                                $recipeNames = $recipesWithActiveCrops->pluck('name')->take(3)->implode(', ');
                                $moreCount = max(0, $recipesWithActiveCrops->count() - 3);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot Delete Recipes')
                                    ->body(
                                        "Cannot delete recipes because they have {$activeCropsCount} active crops in progress." .
                                        '<br><br>Recipes with active crops: <strong>' . $recipeNames . '</strong>' .
                                        ($moreCount > 0 ? " and {$moreCount} others" : '') .
                                        '<br><br>Please harvest or remove the active crops first, or consider deactivating the recipes instead.'
                                    )
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            } else if ($recipesWithCrops->isNotEmpty()) {
                                // PREVENT bulk deletion when there are completed crops (to preserve history)
                                $action->cancel();
                                
                                $totalCropsCount = $recipesWithCrops->map(fn ($recipe) => $recipe->crops()->count())->sum();
                                $recipeNames = $recipesWithCrops->pluck('name')->take(3)->implode(', ');
                                $moreCount = max(0, $recipesWithCrops->count() - 3);
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Cannot Delete Recipes')
                                    ->body(
                                        "Cannot delete recipes because they have {$totalCropsCount} completed crops in the system." .
                                        '<br><br>Recipes with crop history: <strong>' . $recipeNames . '</strong>' .
                                        ($moreCount > 0 ? " and {$moreCount} others" : '') .
                                        '<br><br>Deleting these recipes would remove valuable historical crop data. Consider deactivating the recipes instead.'
                                    )
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                            // If no crops exist, allow normal bulk deletion to proceed
                        }),
                    ...static::getActiveStatusBulkActions(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relation managers needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'common_name',
                'cultivar_name',
                'lot_number', 
                'germination_days', 
                'blackout_days', 
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active',
                'planting_notes',
                'harvesting_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}
