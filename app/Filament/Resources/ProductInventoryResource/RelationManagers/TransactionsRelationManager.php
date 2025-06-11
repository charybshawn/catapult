<?php

namespace App\Filament\Resources\ProductInventoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transaction History';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => ['production', 'purchase', 'return'],
                        'danger' => ['sale', 'damage', 'expiration'],
                        'warning' => ['adjustment', 'transfer'],
                        'info' => ['reservation', 'release'],
                    ]),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '+' . number_format($state, 2) : number_format($state, 2))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->numeric(2)
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->money('USD')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Transaction Details')
                    ->modalContent(fn ($record) => view('filament.resources.product-inventory-resource.transaction-details', ['transaction' => $record])),
            ])
            ->bulkActions([
                // No bulk actions for transactions
            ]);
    }
}