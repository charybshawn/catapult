<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedEntryResource\Pages;
use App\Filament\Resources\SeedEntryResource\RelationManagers;
use App\Models\SeedEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;

class SeedEntryResource extends Resource
{
    protected static ?string $model = SeedEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationLabel = 'Seed Entries';
    
    protected static ?string $navigationGroup = 'Seed Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seed Entry Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('common_name')
                                    ->label('Common Name')
                                    ->options(function () {
                                        return \App\Models\SeedEntry::whereNotNull('common_name')
                                            ->where('common_name', '<>', '')
                                            ->distinct()
                                            ->orderBy('common_name')
                                            ->pluck('common_name', 'common_name')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->allowHtml()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('common_name')
                                            ->label('New Common Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['common_name'];
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        // When common name changes, filter cultivar options
                                        $set('cultivar_name', null); // Reset cultivar selection
                                    }),
                                Forms\Components\Select::make('cultivar_name')
                                    ->required()
                                    ->label('Cultivar Name')
                                    ->options(function (Forms\Get $get) {
                                        $commonName = $get('common_name');
                                        
                                        $query = \App\Models\SeedEntry::whereNotNull('cultivar_name')
                                            ->where('cultivar_name', '<>', '');
                                            
                                        if ($commonName) {
                                            $query->where('common_name', $commonName);
                                        }
                                        
                                        return $query->distinct()
                                            ->orderBy('cultivar_name')
                                            ->pluck('cultivar_name', 'cultivar_name')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->allowHtml()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('cultivar_name')
                                            ->label('New Cultivar Name')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data): string {
                                        return $data['cultivar_name'];
                                    })
                                    ->placeholder(function (Forms\Get $get) {
                                        $commonName = $get('common_name');
                                        return $commonName ? "Select or create cultivar for {$commonName}" : 'Select common name first';
                                    })
                                    ->disabled(fn (Forms\Get $get): bool => empty($get('common_name')))
                                    ->helperText('Cultivar options will filter based on your common name selection'),
                            ]),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('website')
                                    ->url()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('notes')
                                    ->maxLength(65535),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('supplier_product_title')
                                    ->maxLength(255)
                                    ->label('Product Title'),
                                Forms\Components\TextInput::make('supplier_product_url')
                                    ->url()
                                    ->maxLength(255)
                                    ->label('Product URL'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('image_url')
                                    ->url()
                                    ->maxLength(255)
                                    ->label('Image URL'),
                                Forms\Components\DateTimePicker::make('cataloged_at')
                                    ->label('Cataloged Date')
                                    ->default(now())
                                    ->required()
                                    ->helperText('When this seed was first cataloged or listed'),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TagsInput::make('tags')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Pricing Variations')
                    ->schema([
                        Repeater::make('variations')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('size_description')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Size Description')
                                            ->placeholder('e.g., 25 grams, 1 kg, 5 lb bag'),
                                        Forms\Components\TextInput::make('weight_kg')
                                            ->numeric()
                                            ->step('0.0001')
                                            ->label('Weight (kg)')
                                            ->placeholder('0.025')
                                            ->helperText('Common conversions: 25g = 0.025kg, 100g = 0.1kg, 1lb = 0.454kg')
                                            ->live(),
                                        Forms\Components\TextInput::make('sku')
                                            ->maxLength(255)
                                            ->label('SKU')
                                            ->placeholder('Optional'),
                                    ]),
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\TextInput::make('current_price')
                                            ->required()
                                            ->numeric()
                                            ->prefix('$')
                                            ->label('Current Price')
                                            ->live(),
                                        Forms\Components\Select::make('currency')
                                            ->options([
                                                'USD' => 'USD',
                                                'CAD' => 'CAD',
                                                'EUR' => 'EUR',
                                                'GBP' => 'GBP',
                                            ])
                                            ->default('CAD')
                                            ->required(),
                                        Forms\Components\Toggle::make('is_in_stock')
                                            ->label('In Stock')
                                            ->default(true)
                                            ->inline(false),
                                        Forms\Components\Placeholder::make('price_per_kg_display')
                                            ->label('Price per kg')
                                            ->content(function (Forms\Get $get): string {
                                                $price = $get('current_price');
                                                $weight = $get('weight_kg');
                                                $currency = $get('currency') ?? 'CAD';
                                                
                                                if ($price && $weight && $weight > 0) {
                                                    $pricePerKg = $price / $weight;
                                                    return $currency . ' $' . number_format($pricePerKg, 2);
                                                }
                                                
                                                return 'Enter price and weight';
                                            })
                                            ->live(),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('original_weight_value')
                                            ->numeric()
                                            ->label('Original Weight Value')
                                            ->placeholder('25'),
                                        Forms\Components\TextInput::make('original_weight_unit')
                                            ->maxLength(255)
                                            ->label('Original Weight Unit')
                                            ->placeholder('grams'),
                                    ]),
                            ])
                            ->collapsible()
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->addActionLabel('Add Another Variation')
                            ->defaultItems(1)
                            ->columnSpanFull()
                            ->live(),
                    ])
                    ->collapsible()
                    ->description('Add different sizes and pricing options for this seed entry. You can add as many variations as needed.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('common_name')
                    ->label('Common Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('cultivar_name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier_product_title')
                    ->label('Product Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular(),
                Tables\Columns\TextColumn::make('variations_count')
                    ->counts('variations')
                    ->label('Variations')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cataloged_at')
                    ->label('Cataloged')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('common_name')
                    ->options(function () {
                        return \App\Models\SeedEntry::whereNotNull('common_name')
                            ->where('common_name', '<>', '')
                            ->distinct()
                            ->orderBy('common_name')
                            ->pluck('common_name', 'common_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Common Name'),
                Tables\Filters\SelectFilter::make('cultivar_name')
                    ->options(function () {
                        return \App\Models\SeedEntry::whereNotNull('cultivar_name')
                            ->where('cultivar_name', '<>', '')
                            ->distinct()
                            ->orderBy('cultivar_name')
                            ->pluck('cultivar_name', 'cultivar_name')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Cultivar'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\Filter::make('cataloged_at')
                    ->form([
                        Forms\Components\DatePicker::make('cataloged_from')
                            ->label('Cataloged From'),
                        Forms\Components\DatePicker::make('cataloged_until')
                            ->label('Cataloged Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['cataloged_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('cataloged_at', '>=', $date),
                            )
                            ->when(
                                $data['cataloged_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('cataloged_at', '<=', $date),
                            );
                    })
                    ->label('Cataloged Date Range'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('visit_url')
                    ->label('Visit URL')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (SeedEntry $record) => $record->supplier_product_url)
                    ->openUrlInNewTab(),
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
            RelationManagers\VariationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedEntries::route('/'),
            'create' => Pages\CreateSeedEntry::route('/create'),
            'view' => Pages\ViewSeedEntry::route('/{record}'),
            'edit' => Pages\EditSeedEntry::route('/{record}/edit'),
        ];
    }
} 