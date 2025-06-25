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
                            ->reactive(),
                        Forms\Components\Select::make('price_variation_id')
                            ->label('Price Variation')
                            ->relationship('priceVariation', 'name', function ($query, Forms\Get $get) {
                                $productId = $get('product_id');
                                if ($productId) {
                                    return $query->where('product_id', $productId)
                                        ->where('is_active', true);
                                }
                                return $query->where('is_active', true);
                            })
                            ->visible(fn (Forms\Get $get) => $get('product_id'))
                            ->required()
                            ->helperText('Select the specific price variation for this inventory batch')
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Validate that the selected variation belongs to the selected product
                                if ($state && $get('product_id')) {
                                    $variation = \App\Models\PriceVariation::find($state);
                                    if ($variation && $variation->product_id != $get('product_id')) {
                                        $set('price_variation_id', null);
                                        Notification::make()
                                            ->title('Invalid Selection')
                                            ->body('The selected price variation does not belong to the selected product.')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            }),
                    ])
                    ->columns(2),

                Section::make('Inventory Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
                                    ->step(function (Forms\Get $get) {
                                        $priceVariationId = $get('price_variation_id');
                                        if ($priceVariationId) {
                                            $priceVariation = \App\Models\PriceVariation::find($priceVariationId);
                                            $packagingType = $priceVariation?->packagingType;
                                            return $packagingType && $packagingType->allowsDecimalQuantity() ? 0.01 : 1;
                                        }
                                        return 1;
                                    })
                                    ->suffix(function (Forms\Get $get) {
                                        $priceVariationId = $get('price_variation_id');
                                        if ($priceVariationId) {
                                            $priceVariation = \App\Models\PriceVariation::find($priceVariationId);
                                            $packagingType = $priceVariation?->packagingType;
                                            return $packagingType ? $packagingType->getQuantityUnit() : 'units';
                                        }
                                        return 'units';
                                    })
                                    ->reactive(),
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
                Tables\Columns\TextColumn::make('priceVariation.name')
                    ->label('Variation')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->priceVariation) {
                            return 'gray';
                        }
                        
                        return match($record->priceVariation->name) {
                            'Default' => 'primary',
                            'Wholesale' => 'info',
                            'Bulk' => 'success',
                            'Special' => 'warning',
                            default => 'gray'
                        };
                    }),
                Tables\Columns\TextColumn::make('priceVariation.price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('priceVariation.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No SKU')
                    ->copyable()
                    ->copyMessage('SKU copied')
                    ->toggleable(),
                Tables\Columns\TextInputColumn::make('quantity')
                    ->label('Total Qty')
                    ->type('number')
                    ->step(function ($record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        return $packagingType && $packagingType->allowsDecimalQuantity() ? 0.01 : 1;
                    })
                    ->rules(function ($record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        if ($packagingType && $packagingType->allowsDecimalQuantity()) {
                            return ['numeric', 'min:0'];
                        }
                        return ['integer', 'min:0'];
                    })
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
                SelectFilter::make('packaging_type')
                    ->label('Packaging Type')
                    ->relationship('priceVariation.packagingType', 'name', function ($query) {
                        return $query->where('is_active', true);
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->display_name)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('price_variation')
                    ->label('Price Variation')
                    ->relationship('priceVariation', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('low_stock')
                    ->query(fn (Builder $query): Builder => $query->where('available_quantity', '>', 0)->where('available_quantity', '<=', 10))
                    ->label('Low Stock'),
                Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon(30))
                    ->label('Expiring Soon (30 days)'),
            ])
            ->actions([
                Action::make('quick_add')
                    ->label(function (ProductInventory $record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        if ($packagingType && $packagingType->allowsDecimalQuantity()) {
                            return '+100g';
                        }
                        return '+10';
                    })
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->size('sm')
                    ->action(function (ProductInventory $record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        $increment = ($packagingType && $packagingType->allowsDecimalQuantity()) ? 100 : 10;
                        $unit = ($packagingType && $packagingType->allowsDecimalQuantity()) ? 'grams' : 'units';
                        
                        $record->update(['quantity' => $record->quantity + $increment]);
                        Notification::make()
                            ->title('Stock Added')
                            ->body("Added {$increment} {$unit} to inventory")
                            ->success()
                            ->send();
                    }),
                Action::make('quick_subtract')
                    ->label(function (ProductInventory $record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        if ($packagingType && $packagingType->allowsDecimalQuantity()) {
                            return '-100g';
                        }
                        return '-10';
                    })
                    ->icon('heroicon-o-minus')
                    ->color('warning')
                    ->size('sm')
                    ->action(function (ProductInventory $record) {
                        $packagingType = $record->priceVariation?->packagingType;
                        $decrement = ($packagingType && $packagingType->allowsDecimalQuantity()) ? 100 : 10;
                        $unit = ($packagingType && $packagingType->allowsDecimalQuantity()) ? 'grams' : 'units';
                        
                        $newQuantity = max(0, $record->quantity - $decrement);
                        $record->update(['quantity' => $newQuantity]);
                        Notification::make()
                            ->title('Stock Removed')
                            ->body("Removed {$decrement} {$unit} from inventory")
                            ->success()
                            ->send();
                    }),
                Action::make('adjust')
                    ->label('Adjust')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('gray')
                    ->size('sm')
                    ->form([
                        Forms\Components\TextInput::make('new_quantity')
                            ->label('Set Quantity To')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(function (ProductInventory $record) {
                                $packagingType = $record->priceVariation?->packagingType;
                                return $packagingType && $packagingType->allowsDecimalQuantity() ? 0.01 : 1;
                            })
                            ->suffix(function (ProductInventory $record) {
                                $packagingType = $record->priceVariation?->packagingType;
                                return $packagingType ? $packagingType->getQuantityUnit() : 'units';
                            })
                            ->default(fn (ProductInventory $record) => $record->quantity),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason (Optional)')
                            ->rows(2),
                    ])
                    ->action(function (ProductInventory $record, array $data) {
                        $oldQuantity = $record->quantity;
                        $record->update(['quantity' => $data['new_quantity']]);
                        
                        $change = $data['new_quantity'] - $oldQuantity;
                        $changeText = $change > 0 ? "Added " . abs($change) : "Removed " . abs($change);
                        
                        Notification::make()
                            ->title('Quantity Updated')
                            ->body("{$changeText} units in inventory")
                            ->success()
                            ->send();
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
        return ['lot_number', 'product.name'];
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