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
                                            $totalWeight = $seed->current_stock;
                                            // Use the unit field from the database
                                            $displayUnit = $seed->unit ?? 'g';
                                            $stockInfo = " - " . number_format($totalWeight, 1) . " {$displayUnit} available";
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
                                        ->maxLength(255)
                                        ->datalist(function () {
                                            return Consumable::where('type', 'seed')
                                                ->where('is_active', true)
                                                ->pluck('name')
                                                ->unique()
                                                ->toArray();
                                        }),
                                    Forms\Components\Select::make('supplier_id')
                                        ->label('Supplier')
                                        ->options(function () {
                                            return Supplier::query()
                                                ->where(function ($query) {
                                                    $query->where('type', 'soil')
                                                          ->orWhereNull('type')
                                                          ->orWhere('type', 'other');
                                                })
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
                                    Forms\Components\TextInput::make('initial_stock')
                                        ->label('Quantity')
                                        ->helperText('Number of units in stock')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required()
                                        ->default(0),
                                    Forms\Components\Select::make('unit')
                                        ->label('Packaging Type')
                                        ->helperText('Container or form of packaging')
                                        ->options([
                                            'unit' => 'Unit(s)',
                                            'bag' => 'Bag(s)',
                                            'box' => 'Box(es)',
                                            'bottle' => 'Bottle(s)',
                                            'container' => 'Container(s)',
                                            'roll' => 'Roll(s)',
                                            'packet' => 'Packet(s)',
                                            'kg' => 'Kilogram(s)',
                                            'g' => 'Gram(s)',
                                            'l' => 'Liter(s)',
                                            'ml' => 'Milliliter(s)',
                                        ])
                                        ->required()
                                        ->default('packet'),
                                    Forms\Components\TextInput::make('unit_size')
                                        ->label('Unit Size')
                                        ->helperText('Capacity or size of each unit (e.g., 10g per packet)')
                                        ->numeric()
                                        ->required()
                                        ->default(10)
                                        ->minValue(0.01)
                                        ->step(0.01),
                                    Forms\Components\Select::make('quantity_unit')
                                        ->label('Unit of Measurement')
                                        ->helperText('Unit for the size/capacity value')
                                        ->options([
                                            'g' => 'Grams',
                                            'kg' => 'Kilograms',
                                            'l' => 'Liters',
                                            'ml' => 'Milliliters',
                                            'oz' => 'Ounces',
                                            'lb' => 'Pounds',
                                            'cm' => 'Centimeters',
                                            'm' => 'Meters',
                                        ])
                                        ->required()
                                        ->default('g'),
                                    Forms\Components\Hidden::make('type')
                                        ->default('seed'),
                                    Forms\Components\TextInput::make('restock_threshold')
                                        ->label('Restock Threshold')
                                        ->helperText('When stock falls below this number, reorder')
                                        ->numeric()
                                        ->required()
                                        ->default(2),
                                    Forms\Components\TextInput::make('restock_quantity')
                                        ->label('Restock Quantity')
                                        ->helperText('How many to order when restocking')
                                        ->numeric()
                                        ->required()
                                        ->default(5),
                                    Forms\Components\Textarea::make('notes')
                                        ->label('Additional Notes')
                                        ->rows(3),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    // Set default value for consumed_quantity to fix the SQL error
                                    $data['consumed_quantity'] = 0;
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
                                            // Use computed current_stock property
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
                                    Forms\Components\TextInput::make('initial_stock')
                                        ->label('Initial Stock')
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
                                ->integer()
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
                            Forms\Components\Hidden::make('watering_schedule_json')
                                ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, ?Model $record = null) {
                                    // When the form is loaded with existing data, log the state
                                    if ($record) {
                                        \Illuminate\Support\Facades\Log::debug('Hydrating watering_schedule_json from record');
                                        \Illuminate\Support\Facades\Log::debug('Record ID: ' . $record->id);
                                        // Log existing watering schedule entries
                                        \Illuminate\Support\Facades\Log::debug('Existing watering schedule entries count: ' . $record->wateringSchedule()->count());
                                        \Illuminate\Support\Facades\Log::debug('Watering schedule entries: ' . json_encode($record->wateringSchedule()->get()->toArray()));
                                    }
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state) {
                                    \Illuminate\Support\Facades\Log::debug('watering_schedule_json updated: ' . json_encode($state));
                                })
                                // Ensure the field is always included when submitting the form
                                ->dehydrated(true)
                                // Add custom dehydration logic to handle transformations if needed
                                ->dehydrateStateUsing(function (?array $state) {
                                    if (!$state) {
                                        \Illuminate\Support\Facades\Log::debug('Empty watering schedule state in dehydrateStateUsing');
                                        return [];
                                    }
                                    
                                    \Illuminate\Support\Facades\Log::debug('Dehydrating watering schedule state: ' . json_encode($state));
                                    return $state;
                                }),

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
                    \Illuminate\Support\Facades\Log::debug('Watering schedule afterValidation hook triggered');
                    
                    $dtm = $get('dtm');
                    $germDays = $get('germination_days') ?? 0;
                    $blackoutDays = $get('blackout_days') ?? 0;
                    
                    \Illuminate\Support\Facades\Log::debug("DTM: {$dtm}, Germination Days: {$germDays}, Blackout Days: {$blackoutDays}");
                    
                    if (!$dtm || $dtm <= 0) {
                        \Illuminate\Support\Facades\Log::debug('No valid DTM found, skipping watering schedule generation');
                        return;
                    }
                    
                    // Ensure watering schedule is generated
                    $wateringSchedule = [];
                    
                    for ($i = 1; $i <= $dtm; $i++) {
                        $fieldName = "watering_day_{$i}";
                        $fieldValue = $get($fieldName);
                        
                        if ($fieldValue !== null) {
                            // Use consistent key names that match database field names
                            $wateringSchedule[] = [
                                'day_number' => $i,
                                'water_amount_ml' => $fieldValue,
                                'watering_method' => 'bottom', // Default method
                                'needs_liquid_fertilizer' => false, // Default no fertilizer
                                'notes' => '',
                            ];
                        }
                    }
                    
                    \Illuminate\Support\Facades\Log::debug('Generated watering schedule with ' . count($wateringSchedule) . ' entries');
                    
                    if (count($wateringSchedule) > 0) {
                        // Set the hidden field value for form submission
                        $set('watering_schedule_json', $wateringSchedule);
                        
                        // CRITICAL: Also directly set the value in the data property
                        // This ensures it's available in afterSave even if the form state is not properly transmitted
                        if (property_exists($this, 'data')) {
                            $this->data['watering_schedule_json'] = $wateringSchedule;
                            \Illuminate\Support\Facades\Log::debug('Directly set watering_schedule_json in the data property');
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Could not directly set watering_schedule_json - data property not found');
                        }
                        
                        \Illuminate\Support\Facades\Log::debug('Set watering_schedule_json after validation: ' . json_encode($wateringSchedule));
                    } else {
                        \Illuminate\Support\Facades\Log::debug('No watering schedule data was collected');
                    }
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
                ->action(function () {
                    try {
                        \Illuminate\Support\Facades\Log::debug('Manual save action triggered');
                        \Illuminate\Support\Facades\Log::debug('Form data before save: ' . json_encode($this->form->getState()));
                        
                        // Check for watering schedule data
                        if (isset($this->data['watering_schedule_json'])) {
                            \Illuminate\Support\Facades\Log::debug('Watering schedule JSON exists in form data');
                        } else {
                            \Illuminate\Support\Facades\Log::debug('No watering schedule JSON found in form data');
                            
                            // Let's specifically check if watering_day_X fields exist
                            $hasDayFields = false;
                            foreach ($this->data as $key => $value) {
                                if (strpos($key, 'watering_day_') === 0) {
                                    $hasDayFields = true;
                                    \Illuminate\Support\Facades\Log::debug("Found watering day field: {$key} = {$value}");
                                }
                            }
                            
                            if ($hasDayFields) {
                                \Illuminate\Support\Facades\Log::debug('Watering day fields exist but not consolidated into JSON');
                            }
                        }
                        
                        $this->save();
                        
                        \Illuminate\Support\Facades\Log::debug('Recipe saved successfully');
                        \Illuminate\Support\Facades\Log::debug('Watering schedule count after save: ' . $this->record->wateringSchedule()->count());
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error saving recipe: ' . $e->getMessage());
                        \Illuminate\Support\Facades\Log::error('Error trace: ' . $e->getTraceAsString());
                        throw $e; // Re-throw to allow Filament to handle
                    }
                })
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
        
        return $data;
    }
    
    /**
     * Process form data before saving the record
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Illuminate\Support\Facades\Log::debug('mutateFormDataBeforeSave called with data: ' . json_encode($data));
        
        // Process watering schedule data
        if (isset($data['watering_schedule_json']) && is_array($data['watering_schedule_json'])) {
            $wateringData = [];
            foreach ($data['watering_schedule_json'] as $entry) {
                // Normalize data keys to ensure consistency
                $dayNumber = null;
                $waterAmount = null;
                $wateringMethod = 'bottom';  // Default method
                $needsFertilizer = false;    // Default no fertilizer
                $notes = '';                 // Default empty notes
                
                // First check for the day number under different possible keys
                if (isset($entry['day_number'])) {
                    $dayNumber = $entry['day_number'];
                } elseif (isset($entry['day'])) {
                    $dayNumber = $entry['day'];
                }
                
                // Then check for water amount under different possible keys
                if (isset($entry['water_amount_ml'])) {
                    $waterAmount = $entry['water_amount_ml'];
                } elseif (isset($entry['amount'])) {
                    $waterAmount = $entry['amount'];
                }
                
                // Check for watering method
                if (isset($entry['watering_method'])) {
                    $wateringMethod = $entry['watering_method'];
                } elseif (isset($entry['method'])) {
                    $wateringMethod = $entry['method'];
                }
                
                // Check for fertilizer flag
                if (isset($entry['needs_liquid_fertilizer'])) {
                    $needsFertilizer = $entry['needs_liquid_fertilizer'];
                } elseif (isset($entry['is_fertilizer_day'])) {
                    $needsFertilizer = $entry['is_fertilizer_day'];
                }
                
                // Check for notes
                if (isset($entry['notes'])) {
                    $notes = $entry['notes'];
                }
                
                if ($dayNumber !== null && $waterAmount !== null) {
                    $wateringData[] = [
                        'day_number' => $dayNumber,
                        'water_amount_ml' => $waterAmount,
                        'watering_method' => $wateringMethod,
                        'needs_liquid_fertilizer' => $needsFertilizer,
                        'notes' => $notes,
                    ];
                }
            }
            $data['watering_schedule_json'] = $wateringData;
            \Illuminate\Support\Facades\Log::debug('Processed watering_schedule_json: ' . json_encode($wateringData));
        } else {
            \Illuminate\Support\Facades\Log::debug('No watering_schedule_json found in data before save');
        }
        
        // Handle consumable fields
        if (isset($data['seed_consumable_id']) && $data['seed_consumable_id']) {
            $data['seed_variety_id'] = null;
        }
        
        if (isset($data['soil_consumable_id']) && $data['soil_consumable_id']) {
            $data['supplier_soil_id'] = null;
        }
        
        // If using the new consumable fields, extract the seed variety
        if (isset($data['seed_consumable_id']) && $data['seed_consumable_id']) {
            // Get the seed consumable
            $seedConsumable = \App\Models\Consumable::find($data['seed_consumable_id']);
            
            if ($seedConsumable) {
                if ($seedConsumable->seed_variety_id) {
                    // If the seed consumable already has a linked seed variety, use it
                    $data['seed_variety_id'] = $seedConsumable->seed_variety_id;
                } else {
                    // Extract the variety from the seed name and find or create a corresponding SeedVariety
                    $seedName = $seedConsumable->name;
                    
                    // Find or create seed variety based on consumable name
                    $seedVariety = \App\Models\SeedVariety::firstOrCreate(
                        ['name' => $seedName],
                        [
                            'crop_type' => 'microgreens',
                            'is_active' => true
                        ]
                    );
                    
                    // Set the seed_variety_id to the found or created variety
                    $data['seed_variety_id'] = $seedVariety->id;
                    
                    // Also update the consumable to link it to this seed variety for future use
                    $seedConsumable->seed_variety_id = $seedVariety->id;
                    $seedConsumable->save();
                }
            }
        }
        
        if (isset($data['soil_consumable_id']) && $data['soil_consumable_id']) {
            // We're using a consumable for the soil, so clear old reference
            $data['supplier_soil_id'] = null;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Add debug logging
        \Illuminate\Support\Facades\Log::debug('EditRecipe afterSave called');
        \Illuminate\Support\Facades\Log::debug('Data structure: ' . print_r(array_keys($this->data), true));
        \Illuminate\Support\Facades\Log::debug('Record ID: ' . $this->record->id);
        
        try {
            // First, check for day-by-day values in case the JSON wasn't properly populated
            $dtm = $this->data['dtm'] ?? 0;
            $germDays = $this->data['germination_days'] ?? 0;
            $blackoutDays = $this->data['blackout_days'] ?? 0;
            
            $manuallyCompiledSchedule = [];
            
            if ($dtm > 0) {
                \Illuminate\Support\Facades\Log::debug("Checking for individual day fields (DTM: {$dtm})");
                
                // Look for individual day field values as a backup
                for ($i = 1; $i <= $dtm; $i++) {
                    $fieldName = "watering_day_{$i}";
                    if (isset($this->data[$fieldName])) {
                        $dayType = $this->getDayType($i, $germDays, $blackoutDays, $dtm);
                        
                        $manuallyCompiledSchedule[] = [
                            'day_number' => $i,
                            'water_amount_ml' => $this->data[$fieldName],
                            'watering_method' => 'bottom', // Default method
                            'needs_liquid_fertilizer' => false, // Default no fertilizer
                            'notes' => '',
                        ];
                        
                        \Illuminate\Support\Facades\Log::debug("Found day {$i} with water amount: {$this->data[$fieldName]}");
                    }
                }
                
                \Illuminate\Support\Facades\Log::debug("Manually compiled " . count($manuallyCompiledSchedule) . " watering days");
            }
            
            // Process watering schedule data
            if (isset($this->data['watering_schedule_json'])) {
                $scheduleData = $this->data['watering_schedule_json'];
                
                if (is_string($scheduleData)) {
                    \Illuminate\Support\Facades\Log::debug('Watering schedule is a string - attempting to decode JSON');
                    $scheduleData = json_decode($scheduleData, true);
                }
                
                \Illuminate\Support\Facades\Log::debug('Processing watering schedule: ' . json_encode($scheduleData));
            }
            // If no JSON data, use the manually compiled schedule if available
            else if (!empty($manuallyCompiledSchedule)) {
                $scheduleData = $manuallyCompiledSchedule;
                \Illuminate\Support\Facades\Log::debug('Using manually compiled watering schedule');
            }
            else {
                \Illuminate\Support\Facades\Log::debug('No watering schedule data available');
                return;
            }
            
            // Delete existing schedule entries
            $deleteCount = $this->record->wateringSchedule()->count();
            $this->record->wateringSchedule()->delete();
            \Illuminate\Support\Facades\Log::debug("Deleted {$deleteCount} existing watering schedule entries");
            
            // Create new schedule entries
            if (is_array($scheduleData)) {
                \Illuminate\Support\Facades\Log::debug('Processing ' . count($scheduleData) . ' watering schedule entries');
                
                $entriesCreated = 0;
                foreach ($scheduleData as $entry) {
                    try {
                        // Normalize data keys to ensure consistency
                        $dayNumber = null;
                        $waterAmount = null;
                        $wateringMethod = 'bottom';  // Default method
                        $needsFertilizer = false;    // Default no fertilizer
                        $notes = '';                 // Default empty notes
                        
                        // First check for the day number under different possible keys
                        if (isset($entry['day_number'])) {
                            $dayNumber = $entry['day_number'];
                        } elseif (isset($entry['day'])) {
                            $dayNumber = $entry['day'];
                        }
                        
                        // Then check for water amount under different possible keys
                        if (isset($entry['water_amount_ml'])) {
                            $waterAmount = $entry['water_amount_ml'];
                        } elseif (isset($entry['amount'])) {
                            $waterAmount = $entry['amount'];
                        }
                        
                        // Check for watering method
                        if (isset($entry['watering_method'])) {
                            $wateringMethod = $entry['watering_method'];
                        } elseif (isset($entry['method'])) {
                            $wateringMethod = $entry['method'];
                        }
                        
                        // Check for fertilizer flag
                        if (isset($entry['needs_liquid_fertilizer'])) {
                            $needsFertilizer = $entry['needs_liquid_fertilizer'];
                        } elseif (isset($entry['is_fertilizer_day'])) {
                            $needsFertilizer = $entry['is_fertilizer_day'];
                        }
                        
                        // Check for notes
                        if (isset($entry['notes'])) {
                            $notes = $entry['notes'];
                        }
                        
                        if ($dayNumber !== null && $waterAmount !== null) {
                            \Illuminate\Support\Facades\Log::debug('Creating watering schedule entry: Day ' . $dayNumber . ', Amount ' . $waterAmount);
                            
                            $this->record->wateringSchedule()->create([
                                'day_number' => $dayNumber,
                                'water_amount_ml' => $waterAmount,
                                'watering_method' => $wateringMethod,
                                'needs_liquid_fertilizer' => $needsFertilizer,
                                'notes' => $notes,
                            ]);
                            
                            // Increment counter when entry is created
                            $entriesCreated++;
                        } else {
                            \Illuminate\Support\Facades\Log::debug('Skipping invalid watering schedule entry: ' . json_encode($entry));
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Error creating watering schedule entry: ' . $e->getMessage());
                        \Illuminate\Support\Facades\Log::error('Entry data: ' . json_encode($entry));
                    }
                }
                
                \Illuminate\Support\Facades\Log::debug("Created {$entriesCreated} new watering schedule entries");
                \Illuminate\Support\Facades\Log::debug("Final watering schedule count: " . $this->record->wateringSchedule()->count());
            } else {
                \Illuminate\Support\Facades\Log::error('Watering schedule data is not an array: ' . gettype($scheduleData));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error processing watering schedule: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error stack trace: ' . $e->getTraceAsString());
        }
    }
}
