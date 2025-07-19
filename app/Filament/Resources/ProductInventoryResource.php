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
use Illuminate\Support\Facades\Log;
use App\Filament\Resources\BaseResource;

class ProductInventoryResource extends BaseResource
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
                                Forms\Components\Select::make('product_inventory_status_id')
                                    ->label('Status')
                                    ->relationship('productInventoryStatus', 'name')
                                    ->default(fn () => \App\Models\ProductInventoryStatus::where('code', 'active')->first()?->id)
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
            ->columns([
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
                Tables\Columns\BadgeColumn::make('productInventoryStatus.name')
                    ->label('Status')
                    ->color(fn ($state) => match($state) {
                        'Active' => 'success',
                        'Depleted' => 'danger',
                        'Expired' => 'warning',
                        'Damaged' => 'secondary',
                        default => 'gray'
                    }),
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
                    ->relationship('product', 'name'),
                SelectFilter::make('status')
                    ->relationship('productInventoryStatus', 'name'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            ->with([
                'product',
                'priceVariation.packagingType.unitType'
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['lot_number', 'product.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            return static::getModel()::active()->count();
        } catch (\Exception $e) {
            Log::error('Navigation badge error: ' . $e->getMessage());
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        try {
            $lowStock = static::getModel()::active()
                ->where('available_quantity', '>', 0)
                ->where('available_quantity', '<=', 10)
                ->count();

            return $lowStock > 0 ? 'warning' : 'success';
        } catch (\Exception $e) {
            Log::error('Navigation badge color error: ' . $e->getMessage());
            return null;
        }
    }
}