<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

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
                                ->formatStateUsing(function ($state, $record) {
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                    ];
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    return "{$state} {$displayUnit}";
                                }),
                            Infolists\Components\TextEntry::make('consumed_quantity')
                                ->label('Consumed Quantity')
                                ->formatStateUsing(function ($state, $record) {
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                    ];
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    return "{$state} {$displayUnit}";
                                }),
                            Infolists\Components\TextEntry::make('current_stock')
                                ->label('Available Quantity')
                                ->state(fn ($record) => max(0, $record->initial_stock - $record->consumed_quantity))
                                ->formatStateUsing(function ($state, $record) {
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                    ];
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    return "{$state} {$displayUnit}";
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
                                ->formatStateUsing(function ($state, $record) {
                                    // Map unit codes to their full names
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                    ];
                                    
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    
                                    return "{$state} {$displayUnit}";
                                }),
                            Infolists\Components\TextEntry::make('restock_quantity')
                                ->label('Restock Quantity')
                                ->formatStateUsing(function ($state, $record) {
                                    // Map unit codes to their full names
                                    $unitMap = [
                                        'l' => 'litre(s)',
                                        'g' => 'gram(s)',
                                        'kg' => 'kilogram(s)',
                                        'oz' => 'ounce(s)',
                                        'unit' => 'unit(s)',
                                    ];
                                    
                                    $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                                    
                                    return "{$state} {$displayUnit}";
                                }),
                        ])->columnSpanFull()
                            ->columns(2),
                            
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('cost_per_unit')
                                ->label('Cost Per Unit')
                                ->money('USD'),
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
                            ->columns(2),
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
