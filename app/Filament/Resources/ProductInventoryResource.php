<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductInventoryResource\Pages;
use App\Filament\Resources\ProductInventoryResource\RelationManagers;
use App\Models\ProductInventory;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ProductInventoryResource extends Resource
{
    protected static ?string $model = ProductInventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Product Inventory';

    protected static ?string $pluralLabel = 'Product Inventory';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationGroup = 'Products';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Product Information')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $product = Product::find($state);
                                    if ($product) {
                                        $set('batch_number', $product->getNextBatchNumber());
                                    }
                                }
                            }),
                        Forms\Components\Select::make('price_variation_id')
                            ->label('Price Variation')
                            ->relationship('priceVariation', 'name', function ($query, Forms\Get $get) {
                                $productId = $get('product_id');
                                if ($productId) {
                                    return $query->where('product_id', $productId);
                                }
                                return $query;
                            })
                            ->visible(fn (Forms\Get $get) => $get('product_id'))
                            ->helperText('Optional: Link to specific price variation'),
                    ])
                    ->columns(2),

                Section::make('Batch Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('batch_number')
                                    ->label('Batch Number')
                                    ->helperText('Auto-generated or enter custom'),
                                Forms\Components\TextInput::make('lot_number')
                                    ->label('Lot Number')
                                    ->helperText('Optional supplier lot number'),
                                Forms\Components\TextInput::make('location')
                                    ->label('Storage Location')
                                    ->placeholder('e.g., Warehouse A, Shelf 3'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('production_date')
                                    ->label('Production Date')
                                    ->default(now()),
                                Forms\Components\DatePicker::make('expiration_date')
                                    ->label('Expiration Date')
                                    ->after('production_date'),
                            ]),
                    ]),

                Section::make('Quantity & Cost')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->suffix('units'),
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label('Cost per Unit')
                                    ->numeric()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->helperText('For calculating inventory value'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'depleted' => 'Depleted',
                                        'expired' => 'Expired',
                                        'damaged' => 'Damaged',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('batch_number')
                    ->label('Batch #')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Batch number copied'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Total Qty')
                    ->numeric(2)
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('reserved_quantity')
                    ->label('Reserved')
                    ->numeric(2)
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'warning' : null),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available')
                    ->numeric(2)
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state <= now()->addDays(30) ? 'danger' : null)
                    ->icon(fn ($state) => $state && $state <= now()->addDays(30) ? 'heroicon-o-exclamation-triangle' : null),
                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'depleted',
                        'warning' => 'expired',
                        'secondary' => 'damaged',
                    ]),
                Tables\Columns\TextColumn::make('value')
                    ->label('Total Value')
                    ->getStateUsing(fn ($record) => $record->getValue())
                    ->money('USD')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'depleted' => 'Depleted',
                        'expired' => 'Expired',
                        'damaged' => 'Damaged',
                    ]),
                Filter::make('low_stock')
                    ->query(fn (Builder $query): Builder => $query->where('available_quantity', '>', 0)->where('available_quantity', '<=', 10))
                    ->label('Low Stock'),
                Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon(30))
                    ->label('Expiring Soon (30 days)'),
            ])
            ->actions([
                Action::make('adjust')
                    ->label('Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        Forms\Components\Radio::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->options([
                                'add' => 'Add Stock',
                                'remove' => 'Remove Stock',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->step(0.01),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (ProductInventory $record, array $data) {
                        try {
                            if ($data['adjustment_type'] === 'add') {
                                $record->addStock($data['quantity'], [
                                    'type' => 'adjustment',
                                    'notes' => $data['reason'],
                                ]);
                                Notification::make()
                                    ->title('Stock Added')
                                    ->body("Added {$data['quantity']} units to batch {$record->batch_number}")
                                    ->success()
                                    ->send();
                            } else {
                                $record->removeStock($data['quantity'], [
                                    'type' => 'adjustment',
                                    'notes' => $data['reason'],
                                ]);
                                Notification::make()
                                    ->title('Stock Removed')
                                    ->body("Removed {$data['quantity']} units from batch {$record->batch_number}")
                                    ->success()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Adjustment Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_damaged')
                        ->label('Mark as Damaged')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'damaged'])),
                ]),
            ])
            ->emptyStateHeading('No inventory batches')
            ->emptyStateDescription('Start by adding inventory for your products.')
            ->emptyStateIcon('heroicon-o-cube');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductInventories::route('/'),
            'create' => Pages\CreateProductInventory::route('/create'),
            'view' => Pages\ViewProductInventory::route('/{record}'),
            'edit' => Pages\EditProductInventory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'priceVariation']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['batch_number', 'lot_number', 'product.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $lowStock = static::getModel()::active()
            ->where('available_quantity', '>', 0)
            ->where('available_quantity', '<=', 10)
            ->count();

        return $lowStock > 0 ? 'warning' : 'success';
    }
}