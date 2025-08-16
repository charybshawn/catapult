<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedVariationResource\Pages;
use App\Filament\Resources\SeedVariationResource\RelationManagers;
use App\Models\SeedVariation;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;

class SeedVariationResource extends BaseResource
{
    protected static ?string $model = SeedVariation::class;

    // Hide from navigation since variations are managed within SeedEntryResource
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Seed Variations';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seed_entry_id')
                    ->relationship('seedEntry', 'supplier_product_title')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('size')
                            ->label('Size Description')
                            ->placeholder('e.g., 25g, 1 oz, Large packet')
                            ->helperText('Descriptive size as shown to customers')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->placeholder('SKU-001'),
                        Forms\Components\TextInput::make('weight_kg')
                            ->numeric()
                            ->step('0.0001')
                            ->label('Weight (kg)')
                            ->placeholder('0.025')
                            ->helperText('Weight in kilograms for calculations'),
                    ]),
                Forms\Components\Select::make('unit')
                    ->label('Unit')
                    ->options(\App\Models\WeightUnit::options())
                    ->default('g')
                    ->required()
                    ->helperText('Unit for database storage'),
                Forms\Components\TextInput::make('current_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('currency')
                    ->options(\App\Models\Currency::options())
                    ->default('USD')
                    ->required(),
                Forms\Components\Toggle::make('is_available')
                    ->required()
                    ->default(true),
                Forms\Components\DateTimePicker::make('last_checked_at')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('consumable_id')
                    ->relationship('consumable', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->helperText('Note: Integration with the inventory system is temporarily disabled.')
                    ->label('Connected Inventory Item (Disabled)'),
                Forms\Components\Placeholder::make('consumable_notice')
                    ->content('Integration with the Consumables inventory system is temporarily disabled to prevent SQL errors. This feature will be re-enabled in a future update.')
                    ->extraAttributes(['class' => 'text-orange-500']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('seedEntry.cultivar_name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('seedEntry.supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('size')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->numeric()
                    ->label('Weight (kg)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_price')
                    ->label('Price (Original)')
                    ->getStateUsing(fn (SeedVariation $record): string => 
                        ($record->currency === 'CAD' ? 'CDN$' : 'USD$') . number_format($record->current_price, 2) . ' ' . $record->currency
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_cad')
                    ->label('Price (CAD)')
                    ->getStateUsing(fn (SeedVariation $record): string => 
                        'CDN$' . number_format($record->price_in_cad, 2) . ' CAD'
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label('Price per kg (CAD)')
                    ->getStateUsing(fn (SeedVariation $record): string => 
                        $record->price_per_kg_in_cad ? 'CDN$' . number_format($record->price_per_kg_in_cad, 2) . ' CAD' : 'N/A'
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('
                            CASE 
                                WHEN currency = "CAD" THEN current_price / NULLIF(weight_kg, 0)
                                WHEN currency = "USD" THEN (current_price * 1.35) / NULLIF(weight_kg, 0)
                                ELSE current_price / NULLIF(weight_kg, 0)
                            END ' . $direction
                        );
                    }),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('In Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('seedEntry.cultivar_name')
            ->filters([
                Tables\Filters\SelectFilter::make('cultivar')
                    ->options(function () {
                        return \App\Models\SeedEntry::whereNotNull('cultivar_name')
                            ->distinct()
                            ->pluck('cultivar_name', 'cultivar_name')
                            ->filter()
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'], function (Builder $query, $value) {
                            return $query->whereHas('seedEntry', function (Builder $query) use ($value) {
                                $query->where('cultivar_name', $value);
                            });
                        });
                    })
                    ->searchable()
                    ->label('Cultivar'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('seedEntry.supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        '1' => 'In Stock',
                        '0' => 'Out of Stock',
                    ])
                    ->attribute('is_available'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedVariations::route('/'),
            'create' => Pages\CreateSeedVariation::route('/create'),
            'edit' => Pages\EditSeedVariation::route('/{record}/edit'),
        ];
    }
    
    /**
     * Convert weight to kilograms
     */
    public static function convertToKg(float $value, string $unit): float
    {
        return match (strtolower($unit)) {
            'kg', 'kilogram', 'kilograms' => $value,
            'g', 'gram', 'grams' => $value / 1000,
            'mg', 'milligram', 'milligrams' => $value / 1000000,
            'oz', 'ounce', 'ounces' => $value * 0.0283495,
            'lb', 'lbs', 'pound', 'pounds' => $value * 0.453592,
            default => $value / 1000, // Default to grams
        };
    }
}
