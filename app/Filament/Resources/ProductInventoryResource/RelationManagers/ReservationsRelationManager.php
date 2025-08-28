<?php

namespace App\Filament\Resources\ProductInventoryResource\RelationManagers;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Exception;
use Filament\Forms\Components\Textarea;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

/**
 * ReservationsRelationManager for Agricultural Inventory Reservation Management
 * 
 * Manages inventory reservations for agricultural products with comprehensive
 * reservation lifecycle operations including confirmation, fulfillment, and
 * cancellation. Critical for maintaining accurate available inventory levels
 * in microgreens operations where stock must be reserved for pending orders.
 * 
 * @filament_relation_manager Inventory reservation management for ProductInventoryResource
 * @business_domain Agricultural inventory with reservation tracking and fulfillment
 * @relationship_type One-to-many: ProductInventory -> InventoryReservations
 * 
 * @reservation_lifecycle pending -> confirmed -> fulfilled, with cancellation support
 * @agricultural_context Stock reservation for perishable microgreens with expiration tracking
 * @inventory_integrity Ensures accurate available stock calculations through reservation management
 * 
 * @status_workflow pending (awaiting confirmation), confirmed (locked stock), fulfilled (stock deducted)
 * @business_operations Order-driven reservations with manual override capabilities
 * @related_models InventoryReservation, Order, ProductInventory for complete reservation context
 */
class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $title = 'Reservations';

    /**
     * Configure inventory reservation table for agricultural operations.
     * 
     * Provides comprehensive reservation management interface with status tracking,
     * order integration, expiration monitoring, and lifecycle actions. Essential
     * for managing stock reservations in time-sensitive agricultural operations.
     * 
     * @param Table $table Filament table instance for configuration
     * @return Table Configured table with agricultural reservation management features
     * 
     * @columns Order linking, quantity tracking, status badges, timestamps with expiration alerts
     * @actions Confirm, fulfill, cancel operations for complete reservation lifecycle management
     * @filtering Status and expiration filters for agricultural inventory operations
     * @bulk_operations Expired reservation cleanup for inventory maintenance
     */
    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.id')
                    ->label('Order #')
                    ->formatStateUsing(fn ($state) => '#' . $state)
                    ->url(fn ($record) => route('filament.admin.resources.orders.edit', $record->order_id)),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(2)
                    ->alignEnd(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'fulfilled',
                        'danger' => 'cancelled',
                    ]),
                TextColumn::make('created_at')
                    ->label('Reserved At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->color(fn ($state, $record) => $record->isExpired() ? 'danger' : null),
                TextColumn::make('fulfilled_at')
                    ->label('Fulfilled At')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'fulfilled' => 'Fulfilled',
                        'cancelled' => 'Cancelled',
                    ]),
                Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->label('Expired'),
            ])
            ->headerActions([
                // No manual creation of reservations
            ])
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->confirm();
                        Notification::make()
                            ->title('Reservation Confirmed')
                            ->success()
                            ->send();
                    }),
                Action::make('fulfill')
                    ->label('Fulfill')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->visible(fn ($record) => $record->isActive())
                    ->requiresConfirmation()
                    ->modalHeading('Fulfill Reservation')
                    ->modalDescription('This will deduct the reserved stock from inventory.')
                    ->action(function ($record) {
                        try {
                            $record->fulfill();
                            Notification::make()
                                ->title('Reservation Fulfilled')
                                ->body('Stock has been deducted from inventory.')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Fulfillment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $record->cancel($data['reason']);
                            Notification::make()
                                ->title('Reservation Cancelled')
                                ->body('Reserved stock has been released.')
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('cancel_expired')
                    ->label('Cancel Expired')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->isExpired() && $record->status !== 'cancelled') {
                                $record->cancel('Reservation expired');
                                $count++;
                            }
                        }
                        Notification::make()
                            ->title('Expired Reservations Cancelled')
                            ->body("$count expired reservations have been cancelled.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}