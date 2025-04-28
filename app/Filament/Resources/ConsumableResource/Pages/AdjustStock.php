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
                            
                        Forms\Components\Placeholder::make('seed_variety_info')
                            ->label('Seed Variety Information')
                            ->content(function () use ($record) {
                                if ($record->type === 'seed' && $record->seedVariety) {
                                    $variety = $record->seedVariety;
                                    $info = [];
                                    
                                    if ($variety->crop_type) {
                                        $info[] = "Crop Type: {$variety->crop_type}";
                                    }
                                    
                                    if ($variety->germination_rate) {
                                        $info[] = "Germination Rate: {$variety->germination_rate}%";
                                    }
                                    
                                    if ($variety->days_to_maturity) {
                                        $info[] = "Days to Maturity: {$variety->days_to_maturity}";
                                    }
                                    
                                    if (empty($info)) {
                                        return 'No variety details available';
                                    }
                                    
                                    return implode(' | ', $info);
                                }
                                
                                return null;
                            })
                            ->visible(fn () => $record->type === 'seed' && $record->seedVariety),
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
                $record->add((float)$data['add_amount'], $data['add_unit'] ?? null);
                $this->notify('success', 'Stock added successfully');
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