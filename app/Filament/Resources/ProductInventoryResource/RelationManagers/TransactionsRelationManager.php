<?php

namespace App\Filament\Resources\ProductInventoryResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * TransactionsRelationManager for Agricultural Inventory Transaction History
 * 
 * Provides read-only transaction history display for agricultural product inventory
 * showing all stock movements including additions, deductions, adjustments, and
 * reservations. Critical for audit trails and inventory reconciliation in
 * microgreens operations where stock accuracy is essential.
 * 
 * @filament_relation_manager Inventory transaction history for ProductInventoryResource
 * @business_domain Agricultural inventory with comprehensive transaction auditing
 * @relationship_type One-to-many: ProductInventory -> InventoryTransactions
 * 
 * @transaction_types Stock in, stock out, adjustments, reservations, fulfillments
 * @agricultural_context Movement tracking for perishable microgreens with batch history
 * @audit_trail Complete inventory movement history for business compliance
 * 
 * @read_only_interface View-only transaction history without modification capabilities
 * @business_operations Automated transactions from orders, manual adjustments, system operations
 * @related_models InventoryTransaction, ProductInventory for complete transaction context
 */
class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transaction History';

    /**
     * Configure inventory transaction history table for agricultural operations.
     * 
     * Provides comprehensive transaction history display with type-specific formatting,
     * quantity tracking, and reference linking. Essential for maintaining audit trails
     * and understanding stock movement patterns in agricultural operations.
     * 
     * @param Table $table Filament table instance for configuration
     * @return Table Configured table with agricultural transaction history features
     * 
     * @columns Date/time, transaction type, quantities, references, user tracking
     * @read_only No creation or editing actions - pure audit trail display
     * @filtering Transaction type and date filtering for agricultural inventory analysis
     * @sorting Chronological display with most recent transactions first
     */
    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => ['production', 'purchase', 'return'],
                        'danger' => ['sale', 'damage', 'expiration'],
                        'warning' => ['adjustment', 'transfer'],
                        'info' => ['reservation', 'release'],
                    ]),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+' . number_format($state, 2) : number_format($state, 2))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->alignEnd(),
                TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric(2)
                    ->alignEnd(),
                TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->toggleable(),
                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('USD')
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'production' => 'Production',
                        'purchase' => 'Purchase',
                        'sale' => 'Sale',
                        'return' => 'Return',
                        'adjustment' => 'Adjustment',
                        'damage' => 'Damage',
                        'expiration' => 'Expiration',
                        'transfer' => 'Transfer',
                        'reservation' => 'Reservation',
                        'release' => 'Release',
                    ]),
            ])
            ->headerActions([
                // No manual creation of transactions
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Transaction Details')
                    ->modalContent(fn ($record) => view('filament.resources.product-inventory-resource.transaction-details', ['transaction' => $record])),
            ])
            ->toolbarActions([
                // No bulk actions for transactions
            ]);
    }
}