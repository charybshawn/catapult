<?php

namespace App\Filament\Traits;

use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

trait HasInventoryStatus
{
    /**
     * Get inventory status column
     */
    public static function getInventoryStatusColumn(string $label = 'Status'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('status')
            ->label($label)
            ->badge()
            ->color(fn ($record): string => $record ? match (true) {
                $record->isOutOfStock() => 'danger',
                $record->needsRestock() => 'warning',
                default => 'success',
            } : 'gray')
            ->formatStateUsing(fn ($record): string => $record ? match (true) {
                $record->isOutOfStock() => 'Out of Stock',
                $record->needsRestock() => 'Reorder Needed',
                default => 'In Stock',
            } : 'Unknown')
            ->toggleable();
    }
    
    /**
     * Get current stock column
     */
    public static function getCurrentStockColumn(string $label = 'Available Quantity'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('current_stock')
            ->label($label)
            ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
            ->numeric()
            ->sortable(query: fn (Builder $query, string $direction): Builder => 
                $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
            )
            ->formatStateUsing(function ($state, $record) {
                if (!$record) return $state;
                
                $displayUnit = $record->consumableUnit ? $record->consumableUnit->symbol : 'unit(s)';
                
                return "{$state} {$displayUnit}";
            })
            ->toggleable();
    }
    
    /**
     * Get inventory filters
     */
    public static function getInventoryFilters(): array
    {
        return [
            Tables\Filters\Filter::make('needs_restock')
                ->label('Needs Restock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= restock_threshold'))
                ->toggle(),
                
            Tables\Filters\Filter::make('out_of_stock')
                ->label('Out of Stock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= 0'))
                ->toggle(),
                
            Tables\Filters\Filter::make('low_stock')
                ->label('Low Stock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) > 0 AND (total_quantity - consumed_quantity) <= restock_threshold'))
                ->toggle(),
        ];
    }
    
    /**
     * Get inventory bulk actions
     */
    public static function getInventoryBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('bulk_add_stock')
                ->label('Add Stock')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Add')
                        ->numeric()
                        ->step(0.001)
                        ->minValue(0.001)
                        ->required()
                        ->default(10),
                ])
                ->action(function ($records, array $data) {
                    foreach ($records as $record) {
                        $record->add((float) $data['amount']);
                    }
                })
                ->deselectRecordsAfterCompletion(),
                
            Tables\Actions\BulkAction::make('bulk_consume_stock')
                ->label('Consume Stock')
                ->icon('heroicon-o-minus')
                ->form([
                    Forms\Components\TextInput::make('amount')
                        ->label('Amount to Consume')
                        ->numeric()
                        ->step(0.001)
                        ->minValue(0.001)
                        ->required()
                        ->default(1),
                ])
                ->action(function ($records, array $data) {
                    foreach ($records as $record) {
                        $record->deduct((float) $data['amount']);
                    }
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }
    
    /**
     * Get restock settings fields for forms
     */
    public static function getRestockSettingsFields(): array
    {
        return [
            Forms\Components\TextInput::make('restock_threshold')
                ->label('Restock Threshold')
                ->helperText('When stock falls below this number, reorder')
                ->numeric()
                ->required()
                ->default(5),
                
            Forms\Components\TextInput::make('restock_quantity')
                ->label('Restock Quantity')
                ->helperText('How many to order when restocking')
                ->numeric()
                ->required()
                ->default(10),
        ];
    }
    
    /**
     * Get inventory section for forms
     */
    public static function getInventorySection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Inventory Settings')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema(static::getRestockSettingsFields()),
            ]);
    }
}