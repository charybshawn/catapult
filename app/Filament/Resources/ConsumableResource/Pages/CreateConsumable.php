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

class CreateConsumable extends BaseCreateRecord
{
    protected static string $resource = ConsumableResource::class;
    
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
    
    // Add validation to ensure seed variety is selected for seed types
    protected function beforeValidate(): void
    {
        $data = $this->data;
        
        // If this is a seed type, ensure seed_variety_id is present
        if (isset($data['type']) && $data['type'] === 'seed') {
            // Log all the form data for debugging
            Log::info('Seed consumable creation - complete form data:', ['data' => $data]);
            
            // Check if seed_variety_id exists but is null
            if (array_key_exists('seed_variety_id', $data) && $data['seed_variety_id'] === null) {
                Log::warning('Seed variety ID exists but is null');
                
                Notification::make()
                    ->title('Seed Variety Required')
                    ->body('Please select a seed variety from the dropdown before creating a seed consumable.')
                    ->danger()
                    ->send();
                
                throw new \Exception('Seed variety is required');
            }
            
            // If seed_variety_id key doesn't exist or is empty
            if (!isset($data['seed_variety_id']) || (is_string($data['seed_variety_id']) && trim($data['seed_variety_id']) === '')) {
                Log::warning('Seed variety ID is missing for seed consumable');
                
                // Create a more visible notification in addition to validation
                Notification::make()
                    ->title('Seed Variety Required')
                    ->body('Please select a seed variety before creating a seed consumable.')
                    ->danger()
                    ->send();
                
                throw new \Exception('Seed variety is required');
            }
        }
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Log the initial data
        Log::info('Consumable data before mutation:', ['data' => $data]);
        
        // For seed consumables, ensure we have a seed variety
        if ($data['type'] === 'seed' && empty($data['seed_variety_id'])) {
            Log::error('Seed variety ID missing in mutation');
            throw new \Exception('Seed variety is required for seed consumables');
        }
        
        // For seed consumables, set the name from the seed variety
        if ($data['type'] === 'seed' && !empty($data['seed_variety_id'])) {
            $seedVariety = SeedVariety::find($data['seed_variety_id']);
            if ($seedVariety) {
                $data['name'] = $seedVariety->name;
                Log::info('Updated consumable name from seed variety', [
                    'seed_variety_id' => $data['seed_variety_id'],
                    'name' => $data['name']
                ]);
            }
        }
        
        // For non-seed consumables, ensure we have a name
        if ($data['type'] !== 'seed' && empty($data['name'])) {
            Log::error('Name missing for non-seed consumable');
            throw new \Exception('Name is required for non-seed consumables');
        }
        
        // Set initial stock based on type
        if ($data['type'] === 'seed') {
            // For seeds, use total_quantity as initial_stock
            $data['initial_stock'] = $data['total_quantity'] ?? 0;
        } else {
            // For non-seeds, ensure initial_stock is set
            $data['initial_stock'] = $data['initial_stock'] ?? 0;
        }
        
        // Set consumed_quantity to 0 for new consumables
        $data['consumed_quantity'] = 0;
        
        // Calculate total_quantity for non-seed consumables
        if ($data['type'] !== 'seed') {
            if (isset($data['quantity_per_unit']) && $data['quantity_per_unit'] > 0) {
                $data['total_quantity'] = $data['initial_stock'] * $data['quantity_per_unit'];
            } else {
                // Default to initial_stock if quantity_per_unit is not set
                $data['total_quantity'] = $data['initial_stock'] ?? 0;
            }
        }
        
        // Log the final data
        Log::info('Consumable data after mutation:', ['data' => $data]);
        
        return $data;
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
    
    // Add this method to fix potential issues with form submission
    protected function configureForm(): void
    {
        $this->form->schema([
            Tabs::make('Consumable Details')
                ->tabs([
                    Tab::make('Basic Information')
                        ->schema([
                            Select::make('type')
                                ->label('Type')
                                ->options([
                                    'seed' => 'Seed',
                                    'other' => 'Other Consumable',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    // Reset fields when type changes
                                    $set('name', null);
                                    $set('seed_variety_id', null);
                                    $set('initial_stock', 0);
                                    $set('total_quantity', 0);
                                    $set('quantity_per_unit', null);
                                    $set('unit', null);
                                }),
                            
                            // Seed variety selector (only shown for seed type)
                            Select::make('seed_variety_id')
                                ->label('Seed Variety')
                                ->options(function () {
                                    return SeedVariety::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(fn (Get $get) => $get('type') === 'seed')
                                ->visible(fn (Get $get) => $get('type') === 'seed')
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    if ($get('type') === 'seed' && $get('seed_variety_id')) {
                                        $variety = SeedVariety::find($get('seed_variety_id'));
                                        if ($variety) {
                                            $set('name', $variety->name);
                                        }
                                    }
                                }),
                            
                            // Name field (only shown for non-seed type)
                            TextInput::make('name')
                                ->label('Name')
                                ->required(fn (Get $get) => $get('type') !== 'seed')
                                ->visible(fn (Get $get) => $get('type') !== 'seed')
                                ->maxLength(255),
                            
                            // Lot number field (only shown for seed type)
                            TextInput::make('lot_no')
                                ->label('Lot/Batch Number')
                                ->maxLength(100)
                                ->visible(fn (Get $get) => $get('type') === 'seed'),
                            
                            // Quantity fields
                            Grid::make(2)
                                ->schema([
                                    // For seed consumables - simplified approach with direct total quantity
                                    TextInput::make('total_quantity')
                                        ->label('Total Quantity')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required()
                                        ->default(0)
                                        ->step(0.001)
                                        ->visible(fn (Get $get) => $get('type') === 'seed'),
                                    
                                    Select::make('quantity_unit')
                                        ->label('Unit of Measurement')
                                        ->options([
                                            'g' => 'Grams',
                                            'kg' => 'Kilograms',
                                            'oz' => 'Ounces',
                                            'lb' => 'Pounds',
                                        ])
                                        ->required()
                                        ->default('g')
                                        ->visible(fn (Get $get) => $get('type') === 'seed'),
                                    
                                    // For non-seed consumables - traditional approach
                                    TextInput::make('initial_stock')
                                        ->label('Initial Stock')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required()
                                        ->default(0)
                                        ->visible(fn (Get $get) => $get('type') !== 'seed'),
                                    
                                    TextInput::make('quantity_per_unit')
                                        ->label('Quantity Per Unit')
                                        ->numeric()
                                        ->minValue(0)
                                        ->required()
                                        ->default(1)
                                        ->visible(fn (Get $get) => $get('type') !== 'seed'),
                                    
                                    Select::make('unit')
                                        ->label('Unit')
                                        ->options([
                                            'each' => 'Each',
                                            'g' => 'Grams',
                                            'kg' => 'Kilograms',
                                            'oz' => 'Ounces',
                                            'lb' => 'Pounds',
                                            'ml' => 'Milliliters',
                                            'l' => 'Liters',
                                            'gal' => 'Gallons',
                                        ])
                                        ->required()
                                        ->default('each')
                                        ->visible(fn (Get $get) => $get('type') !== 'seed'),
                                ]),
                            
                            // Supplier field
                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('email')
                                        ->email()
                                        ->maxLength(255),
                                    TextInput::make('phone')
                                        ->tel()
                                        ->maxLength(255),
                                ]),
                            
                            // Last ordered date
                            DateTimePicker::make('last_ordered_at')
                                ->label('Last Ordered Date')
                                ->nullable(),
                            
                            // Notes field
                            TextInput::make('notes')
                                ->label('Notes')
                                ->maxLength(1000),
                            
                            // Hidden fields for compatibility
                            Hidden::make('initial_stock')
                                ->default(0)
                                ->visible(fn (Get $get) => $get('type') === 'seed'),
                            Hidden::make('unit')
                                ->default('g')
                                ->visible(fn (Get $get) => $get('type') === 'seed'),
                            Hidden::make('quantity_per_unit')
                                ->default(1)
                                ->visible(fn (Get $get) => $get('type') === 'seed'),
                        ]),
                ]),
        ]);
    }
    
    /**
     * Override to provide better validation for seed varieties
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            // Special handling for seed consumables
            if (isset($data['type']) && $data['type'] === 'seed') {
                // Double-check seed_variety_id is present and valid
                if (empty($data['seed_variety_id'])) {
                    Log::error('Seed variety ID still missing in handleRecordCreation');
                    
                    Notification::make()
                        ->title('Seed Variety Required')
                        ->body('Please make sure to select a valid seed variety from the dropdown. If you tried creating a new variety, please ensure it was created successfully.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Seed variety is required');
                }
                
                // Verify the seed variety exists
                $seedVariety = \App\Models\SeedVariety::find($data['seed_variety_id']);
                if (!$seedVariety) {
                    Log::error('Seed variety not found with ID: ' . $data['seed_variety_id']);
                    
                    Notification::make()
                        ->title('Invalid Seed Variety')
                        ->body('The selected seed variety could not be found. Please try selecting a different variety.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    throw new \Exception('Invalid seed variety ID');
                }
                
                // Set the name from the seed variety
                $data['name'] = $seedVariety->name;
                Log::info('Updated consumable name from seed variety', [
                    'seed_variety_id' => $data['seed_variety_id'],
                    'name' => $data['name']
                ]);
            }
            
            // Create the record
            return $this->getModel()::create($data);
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
} 