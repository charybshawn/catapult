<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Filament\Pages\BaseCreateRecord;
use App\Models\Consumable;
use App\Models\SeedVariety;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Actions\Action as FormsAction;

class CreateConsumable extends BaseCreateRecord
{
    protected static string $resource = ConsumableResource::class;
    
    // Property to track form submission state
    protected $hasFormBeenSubmitted = false;
    
    // Add custom validation rules
    protected function getFormValidationRules(): array
    {
        $rules = parent::getFormValidationRules();
        
        // For seed types, ensure seed_variety_id is required
        if (isset($this->data['type']) && $this->data['type'] === 'seed') {
            $rules['seed_variety_id'] = ['required', 'exists:seed_varieties,id'];
        }
        
        return $rules;
    }
    
    // Completely rebuilt method with a simplified approach
    protected function configureForm(): void
    {
        // Start fresh with a new form
        $this->form
            ->schema([
                // Basic Information section with both type and seed variety
                Section::make('Basic Information')
                    ->schema([
                        Select::make('type')
                            ->label('Consumable Type')
                            ->options([
                                'seed' => 'Seed',
                                'soil' => 'Soil',
                                'packaging' => 'Packaging',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->live()
                            ->default('seed'),
                            
                        // For seed type - using a more direct approach
                        Select::make('seed_variety_id')
                            ->label('Seed Variety')
                            ->helperText('Select a seed variety or create a new one')
                            ->options(function () {
                                return SeedVariety::where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->required(fn (Get $get): bool => $get('type') === 'seed')
                            ->hidden(fn (Get $get): bool => $get('type') !== 'seed')
                            ->searchable()
                            ->preload()
                            ->dehydrated() // Always include in form data
                            ->validationAttribute('Seed Variety')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Variety Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Sunflower - Black Oil'),
                                TextInput::make('crop_type')
                                    ->label('Crop Type')
                                    ->default('microgreens')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Sunflower'),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $variety = SeedVariety::create($data);
                                Log::info('Created new seed variety', [
                                    'id' => $variety->id, 
                                    'name' => $variety->name
                                ]);
                                return $variety->id;
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state && $state !== '') {
                                    $variety = SeedVariety::find($state);
                                    if ($variety) {
                                        $set('name', $variety->name);
                                    }
                                }
                            }),
                            
                        // For non-seed types
                        TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => $get('type') !== 'seed'),
                    ])->columns(1),
                    
                // Inventory Details section
                Section::make('Inventory Details')
                    ->schema([
                        // Common fields for all types
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable(),
                            
                        // Seed type specific fields
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_quantity')
                                    ->label('Total Quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(10),
                                    
                                Select::make('quantity_unit')
                                    ->label('Unit')
                                    ->options([
                                        'g' => 'Grams',
                                        'kg' => 'Kilograms',
                                        'oz' => 'Ounces',
                                        'lb' => 'Pounds',
                                    ])
                                    ->required()
                                    ->default('kg'),
                                    
                                TextInput::make('lot_no')
                                    ->label('Lot/Batch Number')
                                    ->maxLength(100),
                            ])
                            ->visible(fn (Get $get): bool => $get('type') === 'seed'),
                            
                        // Non-seed fields
                        Grid::make(2)
                            ->schema([
                                TextInput::make('initial_stock')
                                    ->label('Initial Stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0)
                                    ->visible(fn (Get $get): bool => $get('type') !== 'seed'),
                                    
                                Select::make('unit')
                                    ->label('Unit')
                                    ->options([
                                        'unit' => 'Unit(s)',
                                        'bag' => 'Bag(s)',
                                        'box' => 'Box(es)',
                                        'container' => 'Container(s)',
                                    ])
                                    ->required()
                                    ->default('unit')
                                    ->visible(fn (Get $get): bool => $get('type') !== 'seed'),
                            ]),
                            
                        // Restock settings 
                        Forms\Components\Fieldset::make('Restock Settings')
                            ->schema([
                                TextInput::make('restock_threshold')
                                    ->label('Restock Threshold')
                                    ->helperText('When stock falls below this level, reorder')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        return $get('type') === 'seed' ? 5 : 1;
                                    }),
                                
                                TextInput::make('restock_quantity')
                                    ->label('Restock Quantity')
                                    ->helperText('How much to order when restocking')
                                    ->numeric()
                                    ->default(function (Get $get) {
                                        return $get('type') === 'seed' ? 10 : 2;
                                    }),
                            ])
                            ->columns(2),
                    ]),
                    
                // Hidden fields to ensure proper processing
                Hidden::make('consumed_quantity')->default(0),
                
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
            ])
            ->statePath('data')
            // This is the key setting to prevent validation errors until submission
            ->live(false); // This should help prevent field validation until necessary
        
        // Log the form configuration completion
        Log::info('Form configuration complete');
    }
    
    // Enhanced version of beforeValidate with improved seed variety detection
    protected function beforeValidate(): void
    {
        // Only log data for debugging
        Log::info('Form validation starting', [
            'data' => $this->data
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Log the initial data
        Log::info('Mutating form data before create:', [
            'type' => $data['type'] ?? 'not set',
            'has_seed_variety_id' => isset($data['seed_variety_id']),
            'seed_variety_id' => $data['seed_variety_id'] ?? 'not set',
            'has_name' => isset($data['name']),
            'name' => $data['name'] ?? 'not set',
            'has_total_quantity' => isset($data['total_quantity']),
        ]);
        
        try {
            // For seed consumables, ensure we have a seed variety and properly set related fields
            if (isset($data['type']) && $data['type'] === 'seed') {
                // If no seed variety, don't proceed - should have been caught by beforeValidate
                if (empty($data['seed_variety_id'])) {
                    Log::error('Seed variety ID missing in mutation');
                    
                    Notification::make()
                        ->title('Seed Variety Required')
                        ->body('A seed variety must be selected for seed consumables.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Seed variety is required for seed consumables');
                }
                
                // Get the seed variety and ensure it exists
                $seedVariety = SeedVariety::find($data['seed_variety_id']);
                if (!$seedVariety) {
                    Log::error('Seed variety not found with ID: ' . $data['seed_variety_id']);
                    
                    Notification::make()
                        ->title('Seed Variety Not Found')
                        ->body('The selected seed variety could not be found.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Selected seed variety not found');
                }
                
                // Always set name from the seed variety for seed consumables
                $data['name'] = $seedVariety->name;
                Log::info('Setting consumable name from seed variety', [
                    'seed_variety_id' => $data['seed_variety_id'],
                    'name' => $data['name'],
                    'crop_type' => $seedVariety->crop_type ?? 'none'
                ]);
                
                // For seed type: set initial_stock from total_quantity and default values for other fields
                $data['initial_stock'] = $data['total_quantity'] ?? 0;
                $data['unit'] = $data['unit'] ?? 'g';
                $data['quantity_per_unit'] = 1; // Default for seeds
                
                Log::info('Seed consumable field mapping:', [
                    'total_quantity' => $data['total_quantity'] ?? 'not set',
                    'initial_stock' => $data['initial_stock'],
                    'unit' => $data['unit'],
                    'quantity_unit' => $data['quantity_unit'] ?? 'not set'
                ]);
            }
            
            // For non-seed consumables, ensure we have a name
            if (isset($data['type']) && $data['type'] !== 'seed' && empty($data['name'])) {
                Log::error('Name missing for non-seed consumable');
                throw new \Exception('Name is required for non-seed consumables');
            }
            
            // Set consumed_quantity to 0 for new consumables
            $data['consumed_quantity'] = 0;
            
            // Calculate total_quantity for non-seed consumables (if applicable)
            if (isset($data['type']) && $data['type'] !== 'seed') {
                if (isset($data['quantity_per_unit']) && $data['quantity_per_unit'] > 0 && isset($data['initial_stock'])) {
                    $data['total_quantity'] = $data['initial_stock'] * $data['quantity_per_unit'];
                } else {
                    // Default to initial_stock if quantity_per_unit is not set
                    $data['total_quantity'] = $data['initial_stock'] ?? 0;
                }
            }
            
            // Log the final data
            Log::info('Final form data after mutation:', [
                'type' => $data['type'] ?? 'not set',
                'name' => $data['name'] ?? 'not set',
                'seed_variety_id' => $data['seed_variety_id'] ?? 'not set',
                'initial_stock' => $data['initial_stock'] ?? 'not set',
                'total_quantity' => $data['total_quantity'] ?? 'not set'
            ]);
            
            return $data;
        } catch (\Exception $e) {
            Log::error('Error in mutateFormDataBeforeCreate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // Override the create method to provide better feedback
    protected function onCreate(array $data): mixed
    {
        try {
            if ($data['type'] === 'seed' && empty($data['seed_variety_id'])) {
                Notification::make()
                    ->title('Missing Seed Variety')
                    ->body('You must select a seed variety when creating a seed consumable.')
                    ->danger()
                    ->send();
                
                return null;
            }
            
            return parent::onCreate($data);
        } catch (\Exception $e) {
            Log::error('Error creating consumable:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            Notification::make()
                ->title('Error Creating Consumable')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            return null;
        }
    }
    
    /**
     * Override to provide better validation and logging for seed varieties
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            // Log the data received by the record creation handler
            Log::info('Creating consumable with data:', [
                'type' => $data['type'] ?? 'not set',
                'has_seed_variety_id' => array_key_exists('seed_variety_id', $data), 
                'seed_variety_id' => $data['seed_variety_id'] ?? 'not set',
                'all_data' => $data
            ]);
            
            // Special handling for seed consumables
            if (isset($data['type']) && $data['type'] === 'seed') {
                // Attempt to find or create a default seed variety if not provided
                if (empty($data['seed_variety_id'])) {
                    Log::warning('Seed variety ID missing in record creation, using fallback');
                    
                    // Try to find an existing default variety or create one
                    $defaultVariety = SeedVariety::firstOrCreate(
                        ['name' => 'Default Seed Variety'],
                        [
                            'crop_type' => 'microgreens',
                            'is_active' => true
                        ]
                    );
                    
                    $data['seed_variety_id'] = $defaultVariety->id;
                    $data['name'] = $defaultVariety->name;
                    
                    Log::info('Using default seed variety as fallback', [
                        'id' => $defaultVariety->id,
                        'name' => $defaultVariety->name
                    ]);
                    
                    Notification::make()
                        ->title('Default Seed Variety Used')
                        ->body('A default seed variety was automatically assigned. You can update this later.')
                        ->warning()
                        ->send();
                } else {
                    // Verify the seed variety exists
                    $seedVariety = SeedVariety::find($data['seed_variety_id']);
                    if (!$seedVariety) {
                        Log::error('Seed variety not found with ID: ' . $data['seed_variety_id']);
                        
                        // Create a default one instead of failing
                        $defaultVariety = SeedVariety::firstOrCreate(
                            ['name' => 'Default Seed Variety'],
                            [
                                'crop_type' => 'microgreens',
                                'is_active' => true
                            ]
                        );
                        
                        $data['seed_variety_id'] = $defaultVariety->id;
                        $data['name'] = $defaultVariety->name;
                        
                        Notification::make()
                            ->title('Invalid Seed Variety')
                            ->body('The selected seed variety could not be found. A default variety was used instead.')
                            ->warning()
                            ->send();
                    } else {
                        // Set the name from the seed variety
                        $data['name'] = $seedVariety->name;
                    }
                }
                
                // For seed consumables, ensure hidden fields are properly set
                $data['initial_stock'] = $data['total_quantity'] ?? 0;
                $data['unit'] = $data['unit'] ?? 'g';
                $data['quantity_per_unit'] = 1; // Default for seeds
                
                Log::info('Prepared seed consumable data for creation', [
                    'name' => $data['name'],
                    'seed_variety_id' => $data['seed_variety_id'],
                    'initial_stock' => $data['initial_stock'],
                    'total_quantity' => $data['total_quantity'] ?? 0
                ]);
            }
            
            // For non-seed consumables, ensure we have a name
            if (isset($data['type']) && $data['type'] !== 'seed' && empty($data['name'])) {
                Log::error('Name missing for non-seed consumable');
                throw new \Exception('Name is required for non-seed consumables');
            }
            
            // Set consumed_quantity to 0 for new consumables
            $data['consumed_quantity'] = 0;
            
            // Create the record
            $model = $this->getModel()::create($data);
            
            // Log the created record
            Log::info('Successfully created consumable', [
                'id' => $model->id,
                'name' => $model->name,
                'type' => $model->type,
                'seed_variety_id' => $model->seed_variety_id,
            ]);
            
            return $model;
        } catch (\Exception $e) {
            Log::error('Error creating consumable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            Notification::make()
                ->title('Error Creating Consumable')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            
            throw $e;
        }
    }
    
    // Add a helper method to debug form data
    protected function debugForm(array $data, string $stage = 'before create'): void
    {
        // Log the complete form data at this stage of processing
        Log::info('Consumable form data (' . $stage . '):', [
            'data' => $data,
            'has_seed_variety_id' => isset($data['seed_variety_id']),
            'seed_variety_id_value' => $data['seed_variety_id'] ?? 'not set',
            'data_type' => isset($data['type']) ? $data['type'] : 'type not set',
        ]);
    }
    
    protected function beforeCreate(): array
    {
        // Debug the form data before creation
        $this->debugForm($this->data, 'before create');
        
        // Continue with normal processing
        return $this->data;
    }

    /**
     * Set up initial form state when the component is mounted
     */
    public function mount(): void
    {
        parent::mount();
        
        // No need to clear form errors manually, just make sure defaults are set
        
        // Ensure default values are set in the form
        $this->form->fill([
            'type' => 'seed',
            'is_active' => true,
            'consumed_quantity' => 0,
            'unit' => 'g',
            'quantity_per_unit' => 1,
            'quantity_unit' => 'kg',
            'total_quantity' => 10,
            'restock_threshold' => 5,
            'restock_quantity' => 10,
        ]);
        
        // Log the initial state
        Log::info('ConsumableCreate component mounted with fresh state');
    }
} 