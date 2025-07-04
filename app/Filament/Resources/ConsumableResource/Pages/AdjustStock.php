<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class AdjustStock extends Page
{
    protected static string $resource = ConsumableResource::class;
    
    protected static string $view = 'filament.resources.consumable-resource.pages.adjust-stock';
    
    public ?Consumable $record = null;
    
    public function mount(Consumable $record): void
    {
        $this->record = $record;
        $this->form->fill([
            'current_stock' => $record->current_stock,
            'record_id' => $record->id,
            'adjustment_type' => 'add',
        ]);
    }
    
    public function form(Form $form): Form
    {
        $record = $this->record;
        
        return $form
            ->schema([
                Forms\Components\Section::make('Stock Adjustment')
                    ->description('Adjust the stock level for this consumable')
                    ->schema([
                        Forms\Components\Select::make('adjustment_type')
                            ->label('Action')
                            ->options([
                                'add' => 'Add Stock',
                                'consume' => 'Consume Stock',
                            ])
                            ->default('add')
                            ->selectablePlaceholder(false)
                            ->required()
                            ->live()
                            ->dehydrated(true),
                            
                        Forms\Components\TextInput::make('current_stock')
                            ->label('Current Stock')
                            ->suffix(function () use ($record) {
                                return $record->unit;
                            })
                            ->required()
                            ->disabled(),
                            
                        Forms\Components\Hidden::make('record_id')
                            ->default($record->id),
                            
                        Forms\Components\Placeholder::make('seed_info')
                            ->label('Seed Information')
                            ->content(function () use ($record) {
                                if ($record->type === 'seed') {
                                    $info = [];
                                    
                                    // Add master cultivar info if available
                                    if ($record->masterCultivar) {
                                        $info[] = "Cultivar: {$record->masterCultivar->name}";
                                        if ($record->masterCultivar->crop_type) {
                                            $info[] = "Crop Type: {$record->masterCultivar->crop_type}";
                                        }
                                    }
                                    
                                    // Add seed entry info if available
                                    if ($record->seedEntry) {
                                        if ($record->seedEntry->germination_rate) {
                                            $info[] = "Germination Rate: {$record->seedEntry->germination_rate}%";
                                        }
                                        if ($record->seedEntry->days_to_maturity) {
                                            $info[] = "Days to Maturity: {$record->seedEntry->days_to_maturity}";
                                        }
                                    }
                                    
                                    // Add cultivar name if set
                                    if ($record->cultivar) {
                                        $info[] = "Variety: {$record->cultivar}";
                                    }
                                    
                                    if (empty($info)) {
                                        return 'No seed details available';
                                    }
                                    
                                    return implode(' | ', $info);
                                }
                                
                                return null;
                            })
                            ->visible(fn () => $record->type === 'seed'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make()
                    ->schema([
                        // Add stock fields - visible when adjustment_type is 'add'
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('add_amount')
                                    ->label('Amount to Add')
                                    ->numeric()
                                    ->step(0.001)
                                    ->minValue(0.001)
                                    ->required()
                                    ->default(fn () => $record->restock_quantity),
                                Forms\Components\Select::make('add_unit')
                                    ->label('Unit')
                                    ->options(fn () => ConsumableResource::getCompatibleUnits($record))
                                    ->default(fn () => $record->unit)
                                    ->required(),
                                Forms\Components\TextInput::make('lot_number')
                                    ->label('Lot/Batch Number')
                                    ->helperText('Required for seed inventory. Different lot numbers create separate inventory records.')
                                    ->maxLength(100)
                                    ->required(fn () => $record->type === 'seed')
                                    ->visible(fn () => $record->type === 'seed'),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('adjustment_type') === 'add')
                            ->columns(2),
                        
                        // Consume stock fields - visible when adjustment_type is 'consume'
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('consume_amount')
                                    ->label('Amount to Consume')
                                    ->numeric()
                                    ->step(0.001)
                                    ->minValue(0.001)
                                    ->required()
                                    ->default(1),
                                Forms\Components\Select::make('consume_unit')
                                    ->label('Unit')
                                    ->options(fn () => ConsumableResource::getCompatibleUnits($record))
                                    ->default(fn () => $record->unit)
                                    ->required(),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('adjustment_type') === 'consume')
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }
    
    public function adjustStock(): void
    {
        $data = $this->form->getState();
        
        $record = Consumable::findOrFail($data['record_id']);
        
        // Determine action based on selected type
        $adjustmentType = $data['adjustment_type'] ?? 'add';
        
        try {
            DB::beginTransaction();
            
            if ($adjustmentType === 'add' && isset($data['add_amount'])) {
                // For seed consumables, check if we need to create a new inventory record based on lot number
                if ($record->type === 'seed' && !empty($data['lot_number'])) {
                    $result = $record->add((float)$data['add_amount'], $data['add_unit'] ?? null, $data['lot_number'] ?? null);
                    
                    // If add() returns false, it means the lot numbers don't match and we need to create a new record
                    if ($result === false) {
                        // Create a new consumable record with the same properties but different lot number
                        $newConsumable = $record->replicate(['id', 'consumed_quantity', 'initial_stock', 'total_quantity', 'lot_no']);
                        $newConsumable->lot_no = $data['lot_number'];
                        $newConsumable->initial_stock = (float)$data['add_amount'];
                        $newConsumable->consumed_quantity = 0;
                        
                        if ($record->quantity_per_unit) {
                            $newConsumable->total_quantity = (float)$data['add_amount'] * $record->quantity_per_unit;
                        } else {
                            $newConsumable->total_quantity = (float)$data['add_amount'];
                        }
                        
                        $newConsumable->last_ordered_at = now();
                        $newConsumable->save();
                        
                        $this->notify('success', 'New seed inventory record created with different lot number');
                    } else {
                        $this->notify('success', 'Stock added successfully');
                    }
                } else {
                    // For non-seed consumables or seeds without lot number, just add to existing stock
                    $record->add((float)$data['add_amount'], $data['add_unit'] ?? null);
                    $this->notify('success', 'Stock added successfully');
                }
            } elseif ($adjustmentType === 'consume' && isset($data['consume_amount'])) {
                $record->deduct((float)$data['consume_amount'], $data['consume_unit'] ?? null);
                $this->notify('success', 'Stock consumed successfully');
            } else {
                $this->notify('danger', 'Invalid stock adjustment data');
                DB::rollBack();
                return;
            }
            
            DB::commit();
            
            // Redirect back to consumable list
            $this->redirect(ConsumableResource::getUrl());
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notify('danger', 'Error adjusting stock: ' . $e->getMessage());
        }
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Save')
                ->submit('adjustStock'),
                
            Actions\Action::make('cancel')
                ->label('Cancel')
                ->url(ConsumableResource::getUrl()),
        ];
    }
} 