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
                        Infolists\Components\TextEntry::make('packagingType.display_name')
                            ->label('Packaging Type')
                            ->visible(fn ($record) => $record->type === 'packaging'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Stock Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('current_stock')
                            ->label('Current Stock')
                            ->suffix(fn ($record) => ' ' . $record->unit),
                        Infolists\Components\TextEntry::make('total_quantity')
                            ->label('Total Quantity')
                            ->visible(fn ($record) => in_array($record->type, ['soil', 'seed']))
                            ->formatStateUsing(fn ($record) => 
                                $record->total_quantity 
                                    ? number_format($record->total_quantity, 2) . ' ' . ($record->quantity_unit ?? '') 
                                    : null),
                        Infolists\Components\TextEntry::make('quantity_per_unit')
                            ->label('Quantity Per Unit')
                            ->visible(fn ($record) => in_array($record->type, ['soil', 'seed']))
                            ->suffix(fn ($record) => ' ' . ($record->quantity_unit ?? '') . ' per ' . $record->unit),
                        Infolists\Components\TextEntry::make('restock_threshold')
                            ->label('Restock Threshold')
                            ->suffix(fn ($record) => ' ' . $record->unit),
                        Infolists\Components\TextEntry::make('restock_quantity')
                            ->label('Restock Quantity')
                            ->suffix(fn ($record) => ' ' . $record->unit),
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
                    ])
                    ->columns(2),
                    
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
