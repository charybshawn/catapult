<?php

namespace App\Filament\Resources\Consumables\SeedConsumableResource\Pages;

use App\Filament\Resources\Consumables\SeedConsumableResource;
use App\Filament\Resources\ConsumableResource\Pages\ViewConsumable;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Log;

class ViewSeed extends ViewConsumable
{
    protected static string $resource = SeedConsumableResource::class;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('restock')
                ->label('Add Seed')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Add')
                        ->suffix(fn (Consumable $record) => $record->quantity_unit)
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step(0.001),
                ])
                ->action(function (Consumable $record, array $data): void {
                    $amountToAdd = (float) $data['amount'];
                    $record->update([
                        'total_quantity' => $record->total_quantity + $amountToAdd,
                        'remaining_quantity' => $record->remaining_quantity + $amountToAdd,
                    ]);
                    
                    Log::info('Seed restocked:', [
                        'consumable_id' => $record->id,
                        'amount_added' => $amountToAdd,
                        'new_total' => $record->total_quantity,
                        'new_remaining' => $record->remaining_quantity,
                    ]);
                    
                    $this->refreshFormData([
                        'total_quantity',
                        'remaining_quantity',
                    ]);
                }),
            Actions\Action::make('deduct')
                ->label('Use Seed')
                ->icon('heroicon-o-minus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Use')
                        ->suffix(fn (Consumable $record) => $record->quantity_unit)
                        ->numeric()
                        ->required()
                        ->minValue(0.001)
                        ->step(0.001)
                        ->maxValue(fn (Consumable $record) => $record->remaining_quantity),
                ])
                ->action(function (Consumable $record, array $data): void {
                    $amountToUse = (float) $data['amount'];
                    $newConsumed = $record->consumed_quantity + $amountToUse;
                    $newRemaining = max(0, $record->total_quantity - $newConsumed);
                    
                    $record->update([
                        'consumed_quantity' => $newConsumed,
                        'remaining_quantity' => $newRemaining,
                    ]);
                    
                    Log::info('Seed used:', [
                        'consumable_id' => $record->id,
                        'amount_used' => $amountToUse,
                        'new_consumed' => $newConsumed,
                        'new_remaining' => $newRemaining,
                    ]);
                    
                    $this->refreshFormData([
                        'consumed_quantity',
                        'remaining_quantity',
                    ]);
                })
                ->visible(fn (Consumable $record) => $record->remaining_quantity > 0),
        ];
    }
    
    /**
     * Format seed quantity display
     */
    protected function formatSeedQuantity(float $quantity, Consumable $record): string
    {
        $formattedQuantity = $quantity == floor($quantity) 
            ? number_format($quantity, 0) 
            : number_format($quantity, 3);
            
        return "{$formattedQuantity} {$record->quantity_unit}";
    }
    
    /**
     * Calculate percentage remaining
     */
    protected function getPercentageRemaining(Consumable $record): float
    {
        if (!$record->total_quantity || $record->total_quantity <= 0) {
            return 0;
        }
        
        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
        return round(($remaining / $record->total_quantity) * 100, 1);
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Seed Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('cultivar')
                            ->label('Cultivar'),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        Infolists\Components\TextEntry::make('masterSeedCatalog.common_name')
                            ->label('Master Catalog'),
                        Infolists\Components\TextEntry::make('lot_no')
                            ->label('Lot/Batch Number')
                            ->placeholder('Not specified'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),
                    
                Infolists\Components\Section::make('Inventory Information')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('total_quantity')
                                ->label('Initial Quantity')
                                ->formatStateUsing(fn ($state, $record) => $this->formatSeedQuantity($state, $record)),
                            Infolists\Components\TextEntry::make('consumed_quantity')
                                ->label('Amount Used')
                                ->formatStateUsing(fn ($state, $record) => $this->formatSeedQuantity($state, $record)),
                            Infolists\Components\TextEntry::make('remaining_quantity')
                                ->label('Remaining')
                                ->formatStateUsing(fn ($state, $record) => $this->formatSeedQuantity($state, $record)),
                            Infolists\Components\TextEntry::make('percentage_remaining')
                                ->label('% Remaining')
                                ->state(fn ($record) => $this->getPercentageRemaining($record) . '%')
                                ->badge()
                                ->color(function ($record): string {
                                    $percentage = $this->getPercentageRemaining($record);
                                    return match (true) {
                                        $percentage <= 10 => 'danger',
                                        $percentage <= 25 => 'warning',
                                        $percentage <= 50 => 'info',
                                        default => 'success',
                                    };
                                }),
                        ])->columnSpanFull()
                            ->columns(4),
                            
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('status')
                                ->state(function ($record) {
                                    $percentage = $this->getPercentageRemaining($record);
                                    if ($percentage <= 0) {
                                        return 'Out of Stock';
                                    } elseif ($percentage <= 10) {
                                        return 'Critical Low';
                                    } elseif ($percentage <= 25) {
                                        return 'Low Stock';
                                    } else {
                                        return 'In Stock';
                                    }
                                })
                                ->badge()
                                ->color(function ($record, $state) {
                                    return match ($state) {
                                        'Out of Stock' => 'danger',
                                        'Critical Low' => 'danger',
                                        'Low Stock' => 'warning',
                                        default => 'success',
                                    };
                                }),
                        ])->columnSpanFull()
                            ->columns(1),
                    ]),
                    
                Infolists\Components\Section::make('Cost Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('cost_per_unit')
                            ->label(fn ($record) => 'Cost per ' . $record->quantity_unit)
                            ->money('USD')
                            ->placeholder('Not specified'),
                        Infolists\Components\TextEntry::make('total_value')
                            ->label('Total Inventory Value')
                            ->state(function ($record) {
                                $costPerUnit = (float) $record->cost_per_unit;
                                $remaining = (float) $record->remaining_quantity;
                                return $remaining * $costPerUnit;
                            })
                            ->money('USD'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                    
                Infolists\Components\Section::make('Additional Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->hidden(fn ($record) => empty($record->notes)),
            ]);
    }
    
    public function getTitle(): string
    {
        return 'View Seed';
    }
}