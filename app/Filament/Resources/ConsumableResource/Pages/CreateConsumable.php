<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\PackagingType;
use App\Models\SeedVariety;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;

class CreateConsumable extends CreateRecord
{
    protected static string $resource = ConsumableResource::class;
    
    // Add debug button
    protected function getHeaderActions(): array
    {
        return [
            Action::make('debugForm')
                ->label('Debug Form')
                ->color('gray')
                ->icon('heroicon-o-bug-ant')
                ->action(function () {
                    $data = $this->form->getState();
                    Log::info('Form state in debug action:', ['data' => $data]);
                    
                    $message = 'Current form data: ' . json_encode($data, JSON_PRETTY_PRINT);
                    $message .= "\n\nIf you're creating a seed consumable, make sure to select a seed variety.";
                    
                    Notification::make()
                        ->title('Form Debug Info')
                        ->body($message)
                        ->success()
                        ->send();
                }),
        ];
    }
    
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
                
                throw new Halt();
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
                
                // Throw validation exception
                throw new Halt();
            }
        }
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Log the form data for debugging
        Log::info('Consumable data before mutation:', ['data' => $data]);
        
        // If this is a packaging type consumable but name is empty, set it from the packaging type
        if ($data['type'] === 'packaging' && empty($data['name']) && !empty($data['packaging_type_id'])) {
            $packagingType = PackagingType::find($data['packaging_type_id']);
            if ($packagingType) {
                $data['name'] = $packagingType->display_name ?? $packagingType->name;
            }
        }
        
        // If this is a seed type consumable, ensure name is set from the seed variety
        if ($data['type'] === 'seed' && !empty($data['seed_variety_id'])) {
            try {
                $seedVariety = SeedVariety::findOrFail($data['seed_variety_id']);
                $data['name'] = $seedVariety->name;
                Log::info('Set seed name from variety:', ['seed_variety_id' => $data['seed_variety_id'], 'name' => $data['name']]);
            } catch (ModelNotFoundException $e) {
                Log::error('Error finding seed variety:', ['seed_variety_id' => $data['seed_variety_id'], 'error' => $e->getMessage()]);
                // If we can't find the seed variety but need to continue, fallback to a default name
                $data['name'] = 'Seed ID: ' . $data['seed_variety_id'];
            }
        }
        
        // Set a default value for consumed_quantity if not set
        if (!isset($data['consumed_quantity'])) {
            $data['consumed_quantity'] = 0;
        }
        
        // Calculate total_quantity if quantity_per_unit is set
        if (isset($data['quantity_per_unit']) && $data['quantity_per_unit'] > 0) {
            $availableStock = max(0, $data['initial_stock'] - $data['consumed_quantity']);
            $data['total_quantity'] = $availableStock * $data['quantity_per_unit'];
        } else {
            // Default to initial_stock if quantity_per_unit is not set
            $data['total_quantity'] = $data['initial_stock'] ?? 0;
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
        parent::configureForm();
        
        $this->form->statePath('data');
        
        // Add a failsafe to catch potential issues with data hydration
        $this->form->beforeStateDehydrated(function() {
            $data = $this->form->getState();
            Log::info('Before dehydration - form data:', ['data' => $data]);
            
            // If this is a seed type, ensure seed_variety_id is present
            if (isset($data['type']) && $data['type'] === 'seed') {
                if (empty($data['seed_variety_id'])) {
                    Log::warning('Seed variety ID is missing during dehydration - attempting to fix');
                    
                    // Check if there's a value in the request
                    $requestData = request()->all();
                    Log::info('Checking request data:', ['request' => $requestData]);
                    
                    // Try to get the data from the request
                    if (isset($requestData['data']['seed_variety_id']) && !empty($requestData['data']['seed_variety_id'])) {
                        $seedVarietyId = $requestData['data']['seed_variety_id'];
                        $this->form->fill(array_merge($data, ['seed_variety_id' => $seedVarietyId]));
                        Log::info('Fixed seed_variety_id from request', ['seed_variety_id' => $seedVarietyId]);
                    } else {
                        Log::error('Could not fix missing seed_variety_id');
                        
                        Notification::make()
                            ->title('Form Error')
                            ->body('There was an issue with the seed variety selection. Please try selecting from the dropdown again.')
                            ->danger()
                            ->send();
                        
                        throw new Halt();
                    }
                }
            }
        });
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