<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                        'e-transfer' => 'E-Transfer',
                        'invoice' => 'Invoice',
                        'other' => 'Other',
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\TextInput::make('transaction_id')
                    ->label('Transaction ID')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Paid At')
                    ->default(function (string $operation) {
                        return $operation === 'create' && $this->data['status'] === 'completed' ? now() : null;
                    })
                    ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                Forms\Components\Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'stripe' => 'success',
                        'cash' => 'warning',
                        'e-transfer' => 'info',
                        'invoice' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->options([
                        'stripe' => 'Stripe',
                        'cash' => 'Cash',
                        'e-transfer' => 'E-Transfer',
                        'invoice' => 'Invoice',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if ($data['status'] === 'completed' && empty($data['paid_at'])) {
                            $data['paid_at'] = now();
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record): void {
                        $record->markAsCompleted();
                    })
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\Action::make('mark_failed')
                    ->label('Mark as Failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function ($record): void {
                        $record->markAsFailed();
                    })
                    ->visible(fn ($record) => $record->status === 'pending'),
                Tables\Actions\Action::make('mark_refunded')
                    ->label('Mark as Refunded')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('info')
                    ->action(function ($record): void {
                        $record->markAsRefunded();
                    })
                    ->visible(fn ($record) => $record->status === 'completed'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 