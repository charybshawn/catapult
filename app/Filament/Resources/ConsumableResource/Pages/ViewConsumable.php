<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class ViewConsumable extends ViewRecord
{
    protected static string $resource = ConsumableResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('restock')
                ->label('Restock')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Add')
                        ->numeric()
                        ->required()
                        ->default(fn (Consumable $record) => $record->restock_quantity),
                ])
                ->action(function (Consumable $record, array $data): void {
                    $record->add($data['amount']);
                    
                    $this->refreshFormData([
                        'current_stock',
                        'total_quantity',
                    ]);
                }),
            Actions\Action::make('deduct')
                ->label('Deduct')
                ->icon('heroicon-o-minus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Deduct')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(fn (Consumable $record) => $record ? $record->current_stock : 0),
                ])
                ->action(function (Consumable $record, array $data): void {
                    if ($record) {
                        $record->deduct($data['amount']);
                        
                        $this->refreshFormData([
                            'current_stock',
                            'total_quantity',
                        ]);
                    }
                })
                ->visible(fn (Consumable $record) => $record && $record->current_stock > 0),
        ];
    }
    
    // Show seed cultivar information if available
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getHeaderWidgetsData(): array
    {
        $record = $this->getRecord();
        
        if ($record->type === 'seed' && $record->seedEntry) {
            return [
                'seedEntry' => $record->seedEntry,
            ];
        }
        
        return [];
    }
    
    // Create a custom widget for seed entry information
    public function getHeader(): ?View
    {
        $record = $this->getRecord();
        
        // Only show seed entry info for seed type consumables with a seed entry
        if ($record->type === 'seed' && $record->seedEntry) {
            return view('filament.widgets.seed-entry-overview', [
                'seedEntry' => $record->seedEntry,
            ]);
        }
        
        return parent::getHeader();
    }
    
    /**
     * Helper method to format quantity display
     * 
     * @param float $quantity The quantity to format
     * @param \App\Models\Consumable $record The consumable record
     * @param bool $showTotal Whether to show total quantity (for initial and consumed)
     * @return string The formatted quantity string
     */
    protected function formatQuantity(float $quantity, \App\Models\Consumable $record, bool $showTotal = false): string
    {
        // Debug information
        \Illuminate\Support\Facades\Log::debug("Formatting quantity for consumable {$record->id} ({$record->name}):", [
            'quantity' => $quantity,
            'showTotal' => $showTotal,
            'record_unit' => $record->unit,
            'quantity_per_unit' => $record->quantity_per_unit,
            'quantity_unit' => $record->quantity_unit,
        ]);
        
        // Unit map for displaying friendly names
        $unitMap = [
            'l' => 'litre(s)',
            'ml' => 'millilitre(s)',
            'g' => 'gram(s)',
            'kg' => 'kilogram(s)',
            'oz' => 'ounce(s)',
            'unit' => 'unit(s)',
            'box' => 'box(es)',
            'bag' => 'bag(s)',
        ];
        
        // Format numbers depending on their type
        $formatNumber = function($value) {
            if (is_null($value)) {
                return 'unit';
            }
            
            if (floor($value) == $value) {
                // No decimal places for whole numbers
                return number_format($value, 0);
            } else {
                // Up to 2 decimal places for fractional numbers
                return number_format($value, 2);
            }
        };
        
        // If quantity_per_unit is set and we want to show totals
        if (isset($record->quantity_per_unit) && $record->quantity_per_unit > 0) {
            $totalQuantity = $quantity * $record->quantity_per_unit;
            $displayUnit = $record->quantity_unit ?? $record->unit;
            $formattedUnit = $unitMap[$displayUnit] ?? $displayUnit;
            
            if ($showTotal) {
                // For initial and consumed: show both container count and total
                $containerStr = $formatNumber($quantity) . " " . ($unitMap[$record->unit] ?? $record->unit);
                return "{$containerStr} (Total: " . $formatNumber($totalQuantity) . " {$formattedUnit})";
            } else {
                // For available: show only the total
                return $formatNumber($totalQuantity) . " {$formattedUnit}";
            }
        }
        
        // Fall back to just container units if quantity_per_unit not set
        $displayUnit = $unitMap[$record->unit] ?? $record->unit;
        return $formatNumber($quantity) . " {$displayUnit}";
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Consumable Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'packaging' => 'success',
                                'label' => 'info',
                                'soil' => 'warning',
                                'seed' => 'emerald',
                                'other' => 'gray',
                                default => 'secondary',
                            }),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        Infolists\Components\TextEntry::make('packagingType')
                            ->label('Packaging Type')
                            ->formatStateUsing(fn ($record) => $record->packagingType?->display_name)
                            ->visible(fn ($record) => $record->type === 'packaging'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Stock Information')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('initial_stock')
                                ->label('Initial Quantity')
                                ->formatStateUsing(fn ($state, $record) => $this->formatQuantity($state, $record, true)),
                            Infolists\Components\TextEntry::make('consumed_quantity')
                                ->label('Consumed Quantity')
                                ->formatStateUsing(fn ($state, $record) => $this->formatQuantity($state, $record, true)),
                            Infolists\Components\TextEntry::make('current_stock')
                                ->label('Available Quantity')
                                ->formatStateUsing(function ($state, $record) {
                                    // Calculate current stock
                                    $currentStock = max(0, $record->initial_stock - $record->consumed_quantity);
                                    
                                    \Illuminate\Support\Facades\Log::debug("Current stock direct format:", [
                                        'consumable_id' => $record->id,
                                        'name' => $record->name,
                                        'current_stock' => $currentStock,
                                        'quantity_per_unit' => $record->quantity_per_unit,
                                        'quantity_unit' => $record->quantity_unit,
                                    ]);
                                    
                                    // Unit map for displaying friendly names
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'ml' => 'millilitre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                        'box' => 'box(es)',
                                        'bag' => 'bag(s)',
                                    ];
                                    
                                    // Format numbers 
                                    $formatNumber = function($value) {
                                        if (is_null($value)) return 'unit';
                                        return floor($value) == $value 
                                            ? number_format($value, 0) 
                                            : number_format($value, 2);
                                    };
                                    
                                    // Calculate total quantity based on units
                                    if ($record->quantity_per_unit && $record->quantity_per_unit > 0) {
                                        $totalQuantity = $currentStock * $record->quantity_per_unit;
                                        $displayUnit = $record->quantity_unit ?? $record->unit;
                                        $formattedUnit = $unitMap[$displayUnit] ?? $displayUnit;
                                        
                                        // For available quantity: show only the total amount
                                        return $formatNumber($totalQuantity) . " {$formattedUnit}";
                                    }
                                    
                                    // Fallback to container units
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    return $formatNumber($currentStock) . " {$displayUnit}";
                                }),
                            Infolists\Components\TextEntry::make('unit')
                                ->label('Unit Type')
                                ->formatStateUsing(function ($state) {
                                    // Map unit codes to their full names
                                    $unitMap = [
                                        'l' => 'Litre(s)',
                                        'g' => 'Gram(s)',
                                        'kg' => 'Kilogram(s)',
                                        'oz' => 'Ounce(s)',
                                        'unit' => 'Unit(s)',
                                    ];
                                    
                                    return $unitMap[$state] ?? $state;
                                }),
                        ])->columnSpanFull()
                            ->columns(4),
                            
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('restock_threshold')
                                ->label('Restock Threshold')
                                ->formatStateUsing(fn ($state, $record) => $this->formatQuantity($state, $record, false)),
                            Infolists\Components\TextEntry::make('restock_quantity')
                                ->label('Restock Quantity')
                                ->formatStateUsing(fn ($state, $record) => $this->formatQuantity($state, $record, false)),
                        ])->columnSpanFull()
                            ->columns(2),
                            
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('status')
                                ->state(function ($record) {
                                    if ($record->current_stock <= 0) {
                                        return 'Out of Stock';
                                    } elseif ($record->current_stock <= $record->restock_threshold) {
                                        return 'Reorder Needed';
                                    } else {
                                        return 'In Stock';
                                    }
                                })
                                ->badge()
                                ->color(function ($record, $state) {
                                    if ($state === 'Out of Stock') {
                                        return 'danger';
                                    } elseif ($state === 'Reorder Needed') {
                                        return 'warning';
                                    } else {
                                        return 'success';
                                    }
                                }),
                        ])->columnSpanFull()
                            ->columns(1),
                    ]),
                    
                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->hidden(fn ($record) => empty($record->notes)),
            ]);
    }
}
