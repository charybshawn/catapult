<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use App\Models\Consumable;
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
        
        // For seed types, ensure master_seed_catalog_id is required
        if (isset($this->data['type']) && $this->data['type'] === 'seed') {
            $rules['master_seed_catalog_id'] = ['required', 'exists:master_seed_catalog,id'];
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
                            
                        // For seed type - using master seed catalog
                        Select::make('master_seed_catalog_id')
                            ->label('Seed Variety')
                            ->helperText('Select a seed variety from the master catalog')
                            ->options(function () {
                                $options = [];
                                $catalogs = \App\Models\MasterSeedCatalog::where('is_active', true)
                                    ->orderBy('common_name')
                                    ->get();
                                
                                \Illuminate\Support\Facades\Log::info('Master catalog options generation', [
                                    'catalog_count' => $catalogs->count()
                                ]);
                                
                                foreach ($catalogs as $catalog) {
                                    $cultivars = is_array($catalog->cultivars) ? $catalog->cultivars : [];
                                    $commonName = ucwords(strtolower($catalog->common_name));
                                    
                                    \Illuminate\Support\Facades\Log::info('Processing catalog', [
                                        'id' => $catalog->id,
                                        'common_name' => $commonName,
                                        'cultivars' => $cultivars
                                    ]);
                                    
                                    if (empty($cultivars)) {
                                        // If no cultivars, show just the common name
                                        $options[$catalog->id] = $commonName . ' (No Cultivar)';
                                    } else {
                                        // Create separate options for each cultivar
                                        foreach ($cultivars as $index => $cultivar) {
                                            // Use a composite key: catalog_id:cultivar_index
                                            $key = $catalog->id . ':' . $index;
                                            $cultivarName = ucwords(strtolower($cultivar));
                                            $options[$key] = $commonName . ' (' . $cultivarName . ')';
                                            
                                            \Illuminate\Support\Facades\Log::info('Adding cultivar option', [
                                                'key' => $key,
                                                'value' => $commonName . ' (' . $cultivarName . ')'
                                            ]);
                                        }
                                    }
                                }
                                
                                \Illuminate\Support\Facades\Log::info('Final options array', [
                                    'total_options' => count($options),
                                    'first_10_keys' => array_slice(array_keys($options), 0, 10)
                                ]);
                                
                                return $options;
                            })
                            ->required(fn (Get $get): bool => $get('type') === 'seed')
                            ->hidden(fn (Get $get): bool => $get('type') !== 'seed')
                            ->searchable()
                            ->dehydrated() // Always include in form data
                            ->validationAttribute('Seed Variety')
                            ->createOptionForm([
                                TextInput::make('common_name')
                                    ->label('Common Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Sunflower'),
                                Forms\Components\TagsInput::make('cultivars')
                                    ->label('Cultivars')
                                    ->placeholder('Enter cultivar names')
                                    ->helperText('Add one or more cultivar names')
                                    ->default([]),
                                TextInput::make('category')
                                    ->label('Category')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Microgreens'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->maxLength(1000),
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $catalog = \App\Models\MasterSeedCatalog::create($data);
                                Log::info('Created new master seed catalog', [
                                    'id' => $catalog->id, 
                                    'common_name' => $catalog->common_name,
                                    'cultivars' => $catalog->cultivars
                                ]);
                                return $catalog->id;
                            })
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state && $state !== '') {
                                    // Parse composite key: catalog_id:cultivar_index or just catalog_id
                                    if (strpos($state, ':') !== false) {
                                        [$catalogId, $cultivarIndex] = explode(':', $state, 2);
                                        $catalog = \App\Models\MasterSeedCatalog::find($catalogId);
                                        if ($catalog) {
                                            $cultivars = is_array($catalog->cultivars) ? $catalog->cultivars : [];
                                            $cultivarName = isset($cultivars[$cultivarIndex]) ? ucwords(strtolower($cultivars[$cultivarIndex])) : 'Unknown Cultivar';
                                            $commonName = ucwords(strtolower($catalog->common_name));
                                            $set('name', $commonName . ' (' . $cultivarName . ')');
                                        }
                                    } else {
                                        // Fallback for simple catalog ID
                                        $catalog = \App\Models\MasterSeedCatalog::find($state);
                                        if ($catalog) {
                                            $cultivars = is_array($catalog->cultivars) ? $catalog->cultivars : [];
                                            $cultivarName = !empty($cultivars) ? ucwords(strtolower($cultivars[0])) : 'No Cultivar';
                                            $commonName = ucwords(strtolower($catalog->common_name));
                                            $set('name', $commonName . ' (' . $cultivarName . ')');
                                        }
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
            'has_master_seed_catalog_id' => isset($data['master_seed_catalog_id']),
            'master_seed_catalog_id' => $data['master_seed_catalog_id'] ?? 'not set',
            'has_name' => isset($data['name']),
            'name' => $data['name'] ?? 'not set',
            'has_total_quantity' => isset($data['total_quantity']),
        ]);
        
        try {
            // For seed consumables, ensure we have a master seed catalog and properly set related fields
            if (isset($data['type']) && $data['type'] === 'seed') {
                // If no master seed catalog, don't proceed
                if (empty($data['master_seed_catalog_id'])) {
                    Log::error('Master seed catalog ID missing in mutation');
                    
                    Notification::make()
                        ->title('Seed Variety Required')
                        ->body('A seed variety must be selected for seed consumables.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Master seed catalog is required for seed consumables');
                }
                
                // Parse composite key if present: catalog_id:cultivar_index
                $catalogId = $data['master_seed_catalog_id'];
                $cultivarIndex = null;
                $selectedCultivarName = null;
                
                if (strpos($data['master_seed_catalog_id'], ':') !== false) {
                    [$catalogId, $cultivarIndex] = explode(':', $data['master_seed_catalog_id'], 2);
                    $cultivarIndex = (int)$cultivarIndex;
                }
                
                // Get the master seed catalog and ensure it exists
                $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
                if (!$masterCatalog) {
                    Log::error('Master seed catalog not found with ID: ' . $catalogId);
                    
                    Notification::make()
                        ->title('Seed Variety Not Found')
                        ->body('The selected seed variety could not be found.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Selected master seed catalog not found');
                }
                
                // Get the specific cultivar if an index was provided
                $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                if ($cultivarIndex !== null && isset($cultivars[$cultivarIndex])) {
                    $selectedCultivarName = ucwords(strtolower($cultivars[$cultivarIndex]));
                } else {
                    $selectedCultivarName = !empty($cultivars) ? ucwords(strtolower($cultivars[0])) : 'Unknown Cultivar';
                }
                
                // Store the actual catalog ID (not the composite key) in the database
                $data['master_seed_catalog_id'] = $catalogId;
                
                // Set name from the master catalog with the specific cultivar
                $commonName = ucwords(strtolower($masterCatalog->common_name));
                $data['name'] = $commonName . ' (' . $selectedCultivarName . ')';
                
                Log::info('Setting consumable name from master seed catalog', [
                    'original_selection' => $catalogId . ($cultivarIndex !== null ? ':' . $cultivarIndex : ''),
                    'master_seed_catalog_id' => $data['master_seed_catalog_id'],
                    'name' => $data['name'],
                    'common_name' => $masterCatalog->common_name,
                    'selected_cultivar' => $selectedCultivarName,
                    'cultivar_index' => $cultivarIndex
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
            
            // For seed consumables, calculate consumed_quantity from remaining_quantity if provided
            if (isset($data['type']) && $data['type'] === 'seed' && isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
                $total = (float) $data['total_quantity'];
                $remaining = (float) $data['remaining_quantity'];
                $data['consumed_quantity'] = max(0, $total - $remaining);
                
                Log::info('Calculated consumed quantity for seed:', [
                    'total_quantity' => $total,
                    'remaining_quantity' => $remaining,
                    'consumed_quantity' => $data['consumed_quantity']
                ]);
            } else {
                // Set consumed_quantity to 0 for new non-seed consumables
                $data['consumed_quantity'] = 0;
            }
            
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
                'master_seed_catalog_id' => $data['master_seed_catalog_id'] ?? 'not set',
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
            if ($data['type'] === 'seed' && empty($data['master_seed_catalog_id'])) {
                $this->sendCustomNotification(
                    Notification::make()
                        ->title('Missing Seed Variety')
                        ->body('You must select a seed variety when creating a seed consumable.')
                        ->danger()
                );
                
                return null;
            }
            
            return parent::onCreate($data);
        } catch (\Exception $e) {
            Log::error('Error creating consumable:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            $this->sendCustomNotification(
                Notification::make()
                    ->title('Error Creating Consumable')
                    ->body($e->getMessage())
                    ->danger()
            );
            
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
                'has_master_seed_catalog_id' => array_key_exists('master_seed_catalog_id', $data), 
                'master_seed_catalog_id' => $data['master_seed_catalog_id'] ?? 'not set',
                'all_data' => $data
            ]);
            
            // Special handling for seed consumables
            if (isset($data['type']) && $data['type'] === 'seed') {
                // Ensure we have a master seed catalog ID
                if (empty($data['master_seed_catalog_id'])) {
                    Log::error('Master seed catalog ID missing in record creation');
                    throw new \Exception('Master seed catalog ID is required for seed consumables');
                }
                
                // Parse composite key if present: catalog_id:cultivar_index (should already be parsed in mutation, but double-check)
                $catalogId = $data['master_seed_catalog_id'];
                if (strpos($data['master_seed_catalog_id'], ':') !== false) {
                    [$catalogId, $cultivarIndex] = explode(':', $data['master_seed_catalog_id'], 2);
                    $data['master_seed_catalog_id'] = $catalogId; // Store the actual catalog ID
                }
                
                // Verify the master seed catalog exists
                $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
                if (!$masterCatalog) {
                    Log::error('Master seed catalog not found with ID: ' . $catalogId);
                    throw new \Exception('Selected seed variety not found');
                }
                
                // Name should already be set correctly in mutation, but ensure it's not empty
                if (empty($data['name'])) {
                    $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                    $cultivarName = !empty($cultivars) ? ucwords(strtolower($cultivars[0])) : 'Unknown Cultivar';
                    $commonName = ucwords(strtolower($masterCatalog->common_name));
                    $data['name'] = $commonName . ' (' . $cultivarName . ')';
                }
                
                // For seed consumables, ensure hidden fields are properly set
                $data['initial_stock'] = $data['total_quantity'] ?? 0;
                $data['unit'] = $data['unit'] ?? 'g';
                $data['quantity_per_unit'] = 1; // Default for seeds
                
                Log::info('Prepared seed consumable data for creation', [
                    'name' => $data['name'],
                    'master_seed_catalog_id' => $data['master_seed_catalog_id'],
                    'initial_stock' => $data['initial_stock'],
                    'total_quantity' => $data['total_quantity'] ?? 0
                ]);
            }
            
            // For non-seed consumables, ensure we have a name
            if (isset($data['type']) && $data['type'] !== 'seed' && empty($data['name'])) {
                Log::error('Name missing for non-seed consumable');
                throw new \Exception('Name is required for non-seed consumables');
            }
            
            // For seed consumables, calculate consumed_quantity from remaining_quantity if provided
            if (isset($data['type']) && $data['type'] === 'seed' && isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
                $total = (float) $data['total_quantity'];
                $remaining = (float) $data['remaining_quantity'];
                $data['consumed_quantity'] = max(0, $total - $remaining);
                
                Log::info('Calculated consumed quantity for seed:', [
                    'total_quantity' => $total,
                    'remaining_quantity' => $remaining,
                    'consumed_quantity' => $data['consumed_quantity']
                ]);
            } else {
                // Set consumed_quantity to 0 for new non-seed consumables
                $data['consumed_quantity'] = 0;
            }
            
            // Create the record
            $model = $this->getModel()::create($data);
            
            // Log the created record
            Log::info('Successfully created consumable', [
                'id' => $model->id,
                'name' => $model->name,
                'type' => $model->type,
                'master_seed_catalog_id' => $model->master_seed_catalog_id,
            ]);
            
            // Send success notification if not already sent
            if (!$this->customNotificationSent) {
                $typeLabel = ucfirst($model->type);
                
                $this->sendCustomNotification(
                    Notification::make()
                        ->title("{$typeLabel} Created Successfully")
                        ->body("'{$model->name}' has been created and added to your inventory.")
                        ->success()
                );
            }
            
            return $model;
        } catch (\Exception $e) {
            Log::error('Error creating consumable', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            
            $this->sendCustomNotification(
                Notification::make()
                    ->title('Error Creating Consumable')
                    ->body('An error occurred: ' . $e->getMessage())
                    ->danger()
                    ->persistent()
            );
            
            throw $e;
        }
    }
    
    // Add a helper method to debug form data
    protected function debugForm(array $data, string $stage = 'before create'): void
    {
        // Log the complete form data at this stage of processing
        Log::info('Consumable form data (' . $stage . '):', [
            'data' => $data,
            'has_master_seed_catalog_id' => isset($data['master_seed_catalog_id']),
            'master_seed_catalog_id_value' => $data['master_seed_catalog_id'] ?? 'not set',
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