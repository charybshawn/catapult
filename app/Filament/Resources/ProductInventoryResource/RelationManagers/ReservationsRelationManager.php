<?php

namespace App\Filament\Resources\ProductInventoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class ReservationsRelationManager extends RelationManager
{
    protected static string $relationship = 'reservations';

    protected static ?string $title = 'Reservations';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order #')
                    ->formatStateUsing(fn ($state) => '#' . $state)
                    ->url(fn ($record) => route('filament.admin.resources.orders.edit', $record->order_id)),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric(2)
                    ->alignEnd(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'fulfilled',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reserved At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->color(fn ($state, $record) => $record->isExpired() ? 'danger' : null),
                Tables\Columns\TextColumn::make('fulfilled_at')
                    ->label('Fulfilled At')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'fulfilled' => 'Fulfilled',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->label('Expired'),
            ])
            ->headerActions([
                // No manual creation of reservations
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
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
                Tables\Actions\Action::make('fulfill')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Fulfillment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'confirmed']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
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
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Cancellation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('cancel_expired')
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