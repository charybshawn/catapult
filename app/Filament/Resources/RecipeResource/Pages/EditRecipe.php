<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\Supplier;
use App\Models\SeedVariety;
use App\Models\RecipeStage;
use App\Models\RecipeWateringSchedule;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextEntry;

class EditRecipe extends EditRecord
{
    protected static string $resource = RecipeResource::class;
    
    use EditRecord\Concerns\HasWizard;
    
    protected function getStepsConfig(): Wizard
    {
        return parent::getStepsConfig()
            ->skippable();
    }
    
    public function form(Form $form): Form
    {
        return $form->schema([
            Wizard::make($this->getSteps())
                ->skippable()
                ->columnSpanFull()
        ]);
    }
    
    protected function getSteps(): array
    {
        return [
            Step::make('Basic Information')
                ->description('Enter the basic recipe information')
                ->schema([
                    Section::make('Recipe Details')
                        ->description("Edit basic details for your grow recipe")
                        ->schema([
                            TextInput::make('name')
                                ->label('Recipe Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter a descriptive name for this recipe')
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }']),
                                
                            Select::make('seed_consumable_id')
                                ->label('Seed')
                                ->options(function () {
                                    return Consumable::where('type', 'seed')
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($seed) {
                                            $lotInfo = $seed->lot_no ? " (Lot: {$seed->lot_no})" : "";
                                            $totalGrams = $seed->current_stock * $seed->quantity_per_unit;
                                            $stockInfo = " - {$totalGrams} g available";
                                            return [$seed->id => $seed->name . $lotInfo . $stockInfo];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Select a seed from inventory or add a new one')
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Seed Name/Variety')
                                        ->helperText('Include the variety name (e.g., "Basil - Genovese")')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->options(function () {
                                            return Supplier::query()
                                                ->where('type', 'seed')
                                                ->orWhereNull('type')
                                                ->orWhere('type', 'other')
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                            return $action
                                                ->form([
                                                    Forms\Components\TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Forms\Components\Hidden::make('type')
                                                        ->default('seed'),
                                                    Forms\Components\Textarea::make('contact_info')
                                                        ->label('Contact Information')
                                                        ->rows(3),
                                                ]);
                                        }),
                                    Forms\Components\TextInput::make('current_stock')
                                        ->label('Current Stock')
                                        ->numeric()
                                        ->required()
                                        ->default(1),
                                    Forms\Components\TextInput::make('unit')
                                        ->label('Unit of Measurement')
                                        ->default('packets')
                                        ->required(),
                                    Forms\Components\TextInput::make('quantity_per_unit')
                                        ->label('Quantity Per Unit (g)')
                                        ->helperText('Amount of seeds in grams per packet')
                                        ->numeric()
                                        ->required()
                                        ->default(10)
                                        ->minValue(0.01)
                                        ->step(0.01),
                                    Forms\Components\Hidden::make('quantity_unit')
                                        ->default('g'),
                                    Forms\Components\Hidden::make('type')
                                        ->default('seed'),
                                    Forms\Components\TextInput::make('cost_per_unit')
                                        ->label('Cost Per Unit ($)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->default(0),
                                    Forms\Components\TextInput::make('restock_threshold')
                                        ->label('Restock Threshold (grams)')
                                        ->helperText('Total seed weight at which to restock, regardless of packet count')
                                        ->numeric()
                                        ->required()
                                        ->default(100),
                                    Forms\Components\TextInput::make('restock_quantity')
                                        ->label('Restock Quantity')
                                        ->helperText('Number of packets to order when restocking')
                                        ->numeric()
                                        ->required()
                                        ->default(2),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Lot/Batch Information')
                                        ->helperText('Include seed variety details, lot numbers, or any other important information')
                                        ->rows(3),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return Consumable::create($data)->id;
                                }),

                            Select::make('soil_consumable_id')
                                ->label('Soil')
                                ->options(function () {
                                    return Consumable::where('type', 'soil')
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($soil) {
                                            $quantityInfo = "";
                                            if ($soil->total_quantity && $soil->quantity_unit) {
                                                $quantityInfo = " ({$soil->total_quantity} {$soil->quantity_unit} total)";
                                            }
                                            $stockInfo = " - {$soil->current_stock} {$soil->unit} available";
                                            return [$soil->id => $soil->name . $quantityInfo . $stockInfo];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Select soil from inventory or add a new one')
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Soil Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->options(function () {
                                            return Supplier::query()
                                                ->where('type', 'soil')
                                                ->orWhereNull('type')
                                                ->orWhere('type', 'other')
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                            return $action
                                                ->form([
                                                    Forms\Components\TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Forms\Components\Hidden::make('type')
                                                        ->default('soil'),
                                                    Forms\Components\Textarea::make('contact_info')
                                                        ->label('Contact Information')
                                                        ->rows(3),
                                                ]);
                                        }),
                                    Forms\Components\TextInput::make('current_stock')
                                        ->label('Current Stock')
                                        ->numeric()
                                        ->required()
                                        ->default(1),
                                    Forms\Components\TextInput::make('unit')
                                        ->label('Unit of Measurement')
                                        ->default('bags')
                                        ->required(),
                                    Forms\Components\TextInput::make('quantity_per_unit')
                                        ->label('Quantity Per Unit (L)')
                                        ->helperText('Amount of soil in liters per bag')
                                        ->numeric()
                                        ->required()
                                        ->default(50)
                                        ->minValue(0.01)
                                        ->step(0.01),
                                    Forms\Components\Hidden::make('quantity_unit')
                                        ->default('l'),
                                    Forms\Components\Hidden::make('type')
                                        ->default('soil'),
                                    Forms\Components\TextInput::make('cost_per_unit')
                                        ->label('Cost Per Unit ($)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->default(0),
                                    Forms\Components\TextInput::make('restock_threshold')
                                        ->label('Restock Threshold (bags)')
                                        ->helperText('Minimum number of bags to maintain in inventory')
                                        ->numeric()
                                        ->required()
                                        ->default(2),
                                    Forms\Components\TextInput::make('restock_quantity')
                                        ->label('Restock Quantity')
                                        ->helperText('Quantity to order when restocking')
                                        ->numeric()
                                        ->required()
                                        ->default(5),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Additional Information')
                                        ->rows(3),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return Consumable::create($data)->id;
                                }),
                            
                            TextInput::make('seed_density_grams_per_tray')
                                ->label('Planting Density (g/tray)')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }']),
                                
                            TextInput::make('expected_yield_grams')
                                ->label('Expected Yield (g/tray)')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ])
                        ->columns(2),
                ]),
            
            Step::make('Grow Plan')
                ->description('Specify plan details for this recipe')
                ->schema([
                    Section::make('Grow Plan Details')
                        ->schema([
                            TextInput::make('seed_soak_hours')
                                ->label('Seed Soak (hours)')
                                ->helperText('How many hours are we soaking the seed')
                                ->default(0)
                                ->numeric()
                                ->step(0.5)
                                ->minValue(0)
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    $germDays = (float) ($get('germination_days') ?? 0);
                                    $blackoutDays = (float) ($get('blackout_days') ?? 0);
                                    $dtm = (float) ($get('dtm') ?? 0);
                                    $state = (float) $state;
                                    
                                    // Recalculate light days
                                    $lightDays = max(0, $dtm - $germDays - $blackoutDays - ($state / 24));
                                    $set('calculated_light_days', $lightDays);
                                }),

                            Forms\Components\Grid::make()
                        ->schema([
                            TextInput::make('dtm')
                                ->label('DTM (Days To Maturity)')
                                ->helperText('How many days before we harvest.')
                                ->required()
                                ->numeric()
                                ->step(0.01)
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    if (!$state || $state <= 0) return;
                                    
                                    // Only auto-calculate if this is a new record
                                    if (!$get('id')) {
                                        // Auto-calculate the growth phase days based on DTM
                                        $germDays = 3; // Default germination days
                                        $blackoutDays = 2; // Default blackout days
                                        $soakHours = 0; // Default soak hours
                                        
                                        $set('germination_days', $germDays);
                                        $set('blackout_days', $blackoutDays);
                                        $set('seed_soak_hours', $soakHours);
                                    }
                                    
                                    $germDays = (float) ($get('germination_days') ?? 0);
                                    $blackoutDays = (float) ($get('blackout_days') ?? 0);
                                    $soakHours = (float) ($get('seed_soak_hours') ?? 0);
                                    $state = (float) $state;
                                    
                                    // Calculate light days as the remainder
                                    $lightDays = max(0, $state - $germDays - $blackoutDays - ($soakHours / 24));
                                    $set('calculated_light_days', $lightDays);
                                }),
                            
                            TextInput::make('germination_days')
                                ->label('Germination Days')
                                ->helperText('Number of days for germination.')
                                ->default(3)
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0)
                                ->required()
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    $blackoutDays = (float) ($get('blackout_days') ?? 0);
                                    $soakHours = (float) ($get('seed_soak_hours') ?? 0);
                                    $dtm = (float) ($get('dtm') ?? 0);
                                    $state = (float) $state;
                                    
                                    // Recalculate light days
                                    $lightDays = max(0, $dtm - $state - $blackoutDays - ($soakHours / 24));
                                    $set('calculated_light_days', $lightDays);
                                }),
                                
                            TextInput::make('blackout_days')
                                ->label('Blackout Days')
                                ->helperText('Number of days for blackout period.')
                                ->default(2)
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0)
                                ->required()
                                ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                    $germDays = (float) ($get('germination_days') ?? 0);
                                    $soakHours = (float) ($get('seed_soak_hours') ?? 0);
                                    $dtm = (float) ($get('dtm') ?? 0);
                                    $state = (float) $state;
                                    
                                    // Recalculate light days
                                    $lightDays = max(0, $dtm - $germDays - $state - ($soakHours / 24));
                                    $set('calculated_light_days', $lightDays);
                                }),
                                
                            Placeholder::make('calculated_light_days')
                                ->label('Light Days (calculated)')
                                ->content(function (Forms\Get $get): string {
                                    $germDays = (float) ($get('germination_days') ?? 0);
                                    $blackoutDays = (float) ($get('blackout_days') ?? 0);
                                    $soakHours = (float) ($get('seed_soak_hours') ?? 0);
                                    $dtm = (float) ($get('dtm') ?? 0);
                                    
                                    $lightDays = max(0, $dtm - $germDays - $blackoutDays - ($soakHours / 24));
                                    return number_format($lightDays, 2);
                                }),
                                ])
                                ->columns(4),
                                
                            Hidden::make('light_days')
                                ->default(0)
                                ->dehydrateStateUsing(function (Forms\Get $get): float {
                                    $germDays = (float) ($get('germination_days') ?? 0);
                                    $blackoutDays = (float) ($get('blackout_days') ?? 0);
                                    $soakHours = (float) ($get('seed_soak_hours') ?? 0);
                                    $dtm = (float) ($get('dtm') ?? 0);
                                    
                                    return max(0, $dtm - $germDays - $blackoutDays - ($soakHours / 24));
                                }),
                        ])
                        ->columns(1),
                        
                    Section::make('Growth Phase Notes')
                        ->description('Add specific notes for each phase of growth')
                        ->collapsible()
                        ->collapsed(true)
                        ->schema([
                            MarkdownEditor::make('planting_notes')
                                ->label('Planting Notes')
                                ->placeholder('Enter notes about the planting process')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                                
                            MarkdownEditor::make('germination_notes')
                                ->label('Germination Notes')
                                ->placeholder('Enter notes about the germination phase')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                                
                            MarkdownEditor::make('blackout_notes')
                                ->label('Blackout Notes')
                                ->placeholder('Enter notes about the blackout phase')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                                
                            MarkdownEditor::make('light_notes')
                                ->label('Light Notes')
                                ->placeholder('Enter notes about the light phase')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                                
                            MarkdownEditor::make('harvesting_notes')
                                ->label('Harvesting Notes')
                                ->placeholder('Enter notes about the harvesting process')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                        ])
                        ->columns(1),
                    
                    Section::make('Additional Notes')
                        ->schema([
                            MarkdownEditor::make('notes')
                                ->label('General Notes')
                                ->placeholder('Enter any additional notes for this recipe')
                                ->toolbarButtons([
                                    'blockquote',
                                    'bold',
                                    'bulletList',
                                    'heading',
                                    'italic',
                                    'link',
                                    'orderedList',
                                    'redo',
                                    'strike',
                                    'table',
                                    'undo',
                                ]),
                        ])
                        ->columns(1),
                ]),
            
            Step::make('Watering Schedule')
                ->description('Set up day-by-day watering amounts')
                ->schema([
                    Section::make('Watering Schedule')
                        ->description('Specify water amounts for each day of the grow cycle')
                        ->schema([
                            Placeholder::make('watering_instructions')
                                ->label('Instructions')
                                ->content('
                                    Set water amounts for each day of the grow cycle.
                                    Day 1 is planting day as well as the first day of germination.
                                    No watering is applied during germination days.
                                    Watering is suspended 24-48 hours in advance of harvest.
                                    
                                    Watering Methods:
                                    - Top Watering: Water directly on the growing medium from the top
                                    - Bottom Watering: Place tray in water to absorb from below
                                    - Misting: Light spray to maintain humidity without fully watering
                                    
                                    Fertilizer toggle will add liquid fertilizer to the water on indicated days.
                                '),
                            
                            Forms\Components\Hidden::make('_watering_schedule_refresh_trigger'),
                            Forms\Components\Hidden::make('watering_schedule_json'),

                            Forms\Components\Grid::make()
                                ->schema(function (Forms\Get $get) {
                                    // This will force Livewire to recompute the grid when the refresh trigger changes
                                    $refreshTrigger = $get('_watering_schedule_refresh_trigger');
                                    
                                    $dtm = $get('dtm') ?? 0;
                                    $germDays = $get('germination_days') ?? 0;
                                    $blackoutDays = $get('blackout_days') ?? 0;
                                    
                                    if ($dtm <= 0) {
                                        return [];
                                    }
                                    
                                    // Calculate the ranges for each phase based on the actual input values
                                    $germinationDays = range(1, $germDays);
                                    
                                    // Only create blackout days if blackout period exists
                                    $blackoutDaysRange = [];
                                    if ($blackoutDays > 0) {
                                        $blackoutDaysRange = range($germDays + 1, $germDays + $blackoutDays);
                                    }
                                    
                                    // Calculate light days correctly based on actual inputs
                                    // Light days include pre-harvest days now
                                    $lightStart = $germDays + $blackoutDays + 1;
                                    $lightDays = ($lightStart <= $dtm) ? range($lightStart, $dtm) : [];
                                    
                                    // Create the sections array for the form
                                    $sections = [];
                                    
                                    // Only add sections if they contain days
                                    if (!empty($germinationDays)) {
                                        $sections[] = Forms\Components\Section::make('Germination & Planting')
                                            ->icon('heroicon-o-sparkles')
                                            ->schema(function () use ($germinationDays) {
                                                $fields = [];
                                                
                                                foreach ($germinationDays as $day) {
                                                    $label = "Day {$day}";
                                                    $defaultValue = 0; // Default to 0 for all germination days
                                                    
                                                    if ($day == 1) {
                                                        $label .= " (Planting)";
                                                        $defaultValue = 500; // Only planting day gets water
                                                    }
                                                    
                                                    $fields[] = Forms\Components\TextInput::make("watering_day_{$day}")
                                                            ->label($label)
                                                            ->suffix('ml')
                                                            ->numeric()
                                                            ->minValue(0)
                                                        ->default($defaultValue)
                                                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }']);
                                                }
                                                
                                                return $fields;
                                            })
                                            ->collapsible()
                                            ->columnSpan(1);
                                    }
                                    
                                    // Add blackout section only if there are blackout days
                                    if (!empty($blackoutDaysRange)) {
                                        $sections[] = Forms\Components\Section::make('Blackout')
                                            ->icon('heroicon-o-moon')
                                            ->schema(function () use ($blackoutDaysRange) {
                                                $fields = [];
                                                
                                                foreach ($blackoutDaysRange as $day) {
                                                    $fields[] = Forms\Components\TextInput::make("watering_day_{$day}")
                                                            ->label("Day {$day}")
                                                            ->suffix('ml')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->default(500)
                                                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }']);
                                                }
                                                
                                                return $fields;
                                            })
                                            ->collapsible()
                                            ->columnSpan(1);
                                    }
                                    
                                    // Add light phase section only if there are light days
                                    if (!empty($lightDays)) {
                                        $sections[] = Forms\Components\Section::make('Light Phase')
                                            ->icon('heroicon-o-sun')
                                            ->schema(function () use ($lightDays, $dtm) {
                                                $fields = [];
                                                
                                                foreach ($lightDays as $day) {
                                                    $label = "Day {$day}";
                                                    $defaultValue = 500;
                                                    
                                                    // Label final two days as pre-harvest
                                                    if ($day > $dtm - 2) {
                                                        $label .= " (Pre-Harvest)";
                                                        $defaultValue = 0; // No water for pre-harvest days
                                                    }
                                                    
                                                    $fields[] = Forms\Components\TextInput::make("watering_day_{$day}")
                                                            ->label($label)
                                                            ->suffix('ml')
                                                            ->numeric()
                                                            ->minValue(0)
                                                        ->default($defaultValue)
                                                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }']);
                                                }
                                                
                                                return $fields;
                                            })
                                            ->collapsible()
                                            ->columnSpan(1);
                                    }
                                    
                                    // Dynamically determine the number of columns based on number of sections
                                    $columns = count($sections);
                                    
                                    return $sections;
                                })
                                ->reactive()
                                ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, Model $record) {
                                    if (!$record->wateringSchedule || $record->wateringSchedule->isEmpty()) {
                                        return;
                                    }
                                    
                                    // Load existing watering schedule
                                    foreach ($record->wateringSchedule as $entry) {
                                        $day = $entry->day_number;
                                        $amount = $entry->water_amount_ml;
                                        
                                        $set("watering_day_{$day}", $amount);
                                    }
                                })
                                ->columns(3)
                                ->visible(fn (Forms\Get $get) => $get('dtm') > 0),
                        ]),
                ])
                ->afterValidation(function (Forms\Get $get, Forms\Set $set) {
                    $dtm = $get('dtm');
                    $germDays = $get('germination_days') ?? 0;
                    $blackoutDays = $get('blackout_days') ?? 0;
                    
                    if (!$dtm || $dtm <= 0) {
                        return;
                    }
                    
                    // Ensure watering schedule is generated
                    $wateringSchedule = [];
                    
                    for ($i = 1; $i <= $dtm; $i++) {
                        $fieldName = "watering_day_{$i}";
                        if ($get($fieldName) !== null) {
                            // Determine day type based on the day number and actual phase durations
                            $dayType = $this->getDayType($i, $germDays, $blackoutDays, $dtm);
                            
                            $wateringSchedule[] = [
                                'day' => $i,
                                'day_type' => $dayType,
                                'amount' => $get($fieldName),
                            ];
                        }
                    }
                    
                    $set('watering_schedule_json', $wateringSchedule);
                }),
        ];
    }
    
    /**
     * Determine the growth phase type for a specific day number
     */
    protected function getDayType(int $day, int $germDays, int $blackoutDays, int $dtm): string
    {
        // Germination days (including day 1 which is planting)
        if ($day <= $germDays) {
            return $day === 1 ? 'planting' : 'germination';
        }
        
        // Blackout days (only if blackout period exists)
        if ($blackoutDays > 0 && $day > $germDays && $day <= $germDays + $blackoutDays) {
            return 'blackout';
        }
        
        // All remaining days are in the light phase
        // We still tag the final two days as pre-harvest for formatting purposes
        if ($day > $dtm - 2) {
            return 'light-pre-harvest'; // These are light days, but tagged as pre-harvest
        }
        
        // Regular light days
        return 'light';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('save')
                ->label('Save Recipe')
                ->action(fn () => $this->save())
                ->keyBindings(['mod+s'])
                ->color('success'),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array 
    {
        // If we have light_days but no dtm, calculate it
        if (isset($data['light_days']) && isset($data['germination_days']) && isset($data['blackout_days']) && !isset($data['dtm'])) {
            $data['dtm'] = $data['light_days'] + $data['germination_days'] + $data['blackout_days'];
        }
        
        // Convert seed_soak_days to seed_soak_hours if it exists
        if (isset($data['seed_soak_days']) && !isset($data['seed_soak_hours'])) {
            $data['seed_soak_hours'] = $data['seed_soak_days'] * 24;
        }
        
        return $data;
    }
    
    /**
     * Process form data before saving the record
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Process watering schedule data
        if (isset($data['watering_schedule_json']) && is_array($data['watering_schedule_json'])) {
            $wateringData = [];
            foreach ($data['watering_schedule_json'] as $entry) {
                if (isset($entry['day']) && isset($entry['amount'])) {
                    $wateringData[] = [
                        'day_number' => $entry['day'],
                        'water_amount_ml' => $entry['amount'],
                        'day_type' => $entry['day_type'] ?? 'light',
                        'is_fertilizer_day' => false,
                        'method' => 'bottom',
                        'notes' => '',
                    ];
                }
            }
            $data['watering_schedule_json'] = $wateringData;
        }
        
        // Convert seed_soak_hours to seed_soak_days if needed
        if (isset($data['seed_soak_hours']) && $data['seed_soak_hours'] > 0) {
            $data['seed_soak_days'] = $data['seed_soak_hours'] / 24;
        }
        
        // Handle consumable fields
        if (isset($data['seed_consumable_id']) && $data['seed_consumable_id']) {
            $data['seed_variety_id'] = null;
        }
        
        if (isset($data['soil_consumable_id']) && $data['soil_consumable_id']) {
            $data['supplier_soil_id'] = null;
        }
        
        return $data;
    }
}
