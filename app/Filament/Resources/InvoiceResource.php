<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Finance';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Details')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id', function (Builder $query) {
                                return $query->whereNotIn('id', function ($query) {
                                    $query->select('order_id')
                                        ->from('invoices');
                                })->where('customer_type', 'wholesale');
                            })
                            ->preload()
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\DatePicker::make('harvest_date')
                                    ->required(),
                                Forms\Components\DatePicker::make('delivery_date')
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('pending')
                                    ->required(),
                                Forms\Components\Select::make('customer_type')
                                    ->options([
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default('wholesale')
                                    ->required(),
                            ]),
                            
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'INV-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT)),
                            
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0)
                            ->step(0.01),
                            
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'paid' => 'Paid',
                                'overdue' => 'Overdue',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                            
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Sent At')
                            ->visible(fn (Forms\Get $get) => in_array($get('status'), ['sent', 'paid', 'overdue'])),
                            
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'paid'),
                            
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(fn () => now()->addDays(30))
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->money('USD')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'sent',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'gray' => 'cancelled',
                    ]),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Due Date'),
                    
                Tables\Columns\TextColumn::make('sent_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'paid' => 'Paid',
                        'overdue' => 'Overdue',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('due_from'),
                        Forms\Components\DatePicker::make('due_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['due_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['due_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('Mark as Sent')
                    ->action(fn (Invoice $record) => $record->markAsSent())
                    ->requiresConfirmation()
                    ->color('primary')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Invoice $record) => $record->status === 'draft'),
                Tables\Actions\Action::make('Mark as Paid')
                    ->action(fn (Invoice $record) => $record->markAsPaid())
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Invoice $record) => in_array($record->status, ['sent', 'overdue'])),
                Tables\Actions\Action::make('Mark as Overdue')
                    ->action(fn (Invoice $record) => $record->markAsOverdue())
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (Invoice $record) => $record->status === 'sent' && $record->due_date < now()),
                Tables\Actions\Action::make('Cancel Invoice')
                    ->action(fn (Invoice $record) => $record->markAsCancelled())
                    ->requiresConfirmation()
                    ->color('gray')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Invoice $record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
                Tables\Actions\Action::make('Download PDF')
                    ->url(fn (Invoice $record): string => route('invoices.download', $record))
                    ->openUrlInNewTab()
                    ->color('info')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (Invoice $record) => in_array($record->status, ['sent', 'paid', 'overdue'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_sent')
                        ->label('Mark as Sent')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn (Collection $records) => $records->each->markAsSent())
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('mark_paid')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->markAsPaid())
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
