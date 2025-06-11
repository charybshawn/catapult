<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceVariationResource\Pages;
use App\Filament\Resources\PriceVariationResource\RelationManagers;
use App\Models\PriceVariation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriceVariationResource extends Resource
{
    protected static ?string $model = PriceVariation::class;

    // Hide from navigation since price variations are managed within ProductResource
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Price Variation Details')
                    ->schema([
                        Forms\Components\Placeholder::make('global_info')
                            ->content('This is a global price variation that can be applied to any product.')
                            ->visible(fn (Forms\Get $get): bool => $get('is_global')),
                            
                        Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->label('Product')
                            ->required(fn (Forms\Get $get): bool => !$get('is_global'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->visible(fn (Forms\Get $get): bool => !$get('is_global')),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\Select::make('packaging_type_id')
                                    ->relationship('packagingType', 'name')
                                    ->label('Packaging Type')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU/UPC Code')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('fill_weight_grams')
                                    ->label('Fill Weight / Quantity')
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix('g / trays')
                                    ->helperText('Fill weight in grams (for packaged products) or quantity in trays (for live trays)')
                                    ->required(fn (Forms\Get $get): bool => !$get('is_global'))
                                    ->reactive(),
                            ]),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->required(),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Price for Product')
                                    ->default(false)
                                    ->visible(fn (Forms\Get $get): bool => !$get('is_global'))
                                    ->disabled(fn (Forms\Get $get): bool => $get('is_global')),
                                    
                                Forms\Components\Toggle::make('is_global')
                                    ->label('Global Template')
                                    ->helperText('Creates a pricing template that can be applied to any product with customizable fill weights')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            // If making global, clear the product association and default status
                                            $set('is_default', false);
                                            $set('product_id', null);
                                        }
                                    }),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Global Template'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->sortable()
                    ->placeholder('N/A'),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fill_weight_grams')
                    ->label('Weight/Qty')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->is_global && !$state) {
                            return 'Template';
                        }
                        
                        if (!$state) {
                            return 'N/A';
                        }
                        
                        // Special formatting for different packaging types
                        if ($record->packagingType) {
                            if ($record->packagingType->name === 'Live Tray') {
                                return $state . ' tray' . ($state != 1 ? 's' : '');
                            }
                            if ($record->packagingType->name === 'Bulk') {
                                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
                            }
                        }
                        
                        return $state . 'g';
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Template')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
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
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Product'),
                Tables\Filters\SelectFilter::make('packagingType')
                    ->relationship('packagingType', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Packaging Type'),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\TernaryFilter::make('is_global')
                    ->label('Global Templates'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit price variation'),
                Tables\Actions\Action::make('apply_template')
                    ->label('Apply to Product')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_global)
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Variation Name')
                            ->required()
                            ->default(fn ($record) => $record->name),
                        Forms\Components\TextInput::make('fill_weight_grams')
                            ->label('Fill Weight (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->helperText('Specify the actual fill weight for this product')
                            ->required(),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU/UPC Code')
                            ->maxLength(255)
                            ->default(fn ($record) => $record->sku),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Custom Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->minValue(0)
                                    ->default(fn ($record) => $record->price)
                                    ->required()
                                    ->helperText(fn ($record) => 'Template price: $' . number_format($record->price, 2)),
                                Forms\Components\Placeholder::make('price_comparison')
                                    ->label('Price Override')
                                    ->content('Enter a custom price above to override the template pricing')
                                    ->extraAttributes(['class' => 'prose text-sm']),
                            ]),
                        Forms\Components\Toggle::make('is_default')
                            ->label('Make this the default price for the product')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->action(function ($record, array $data) {
                        // Create a new product-specific variation based on the template
                        \App\Models\PriceVariation::create([
                            'product_id' => $data['product_id'],
                            'packaging_type_id' => $record->packaging_type_id,
                            'name' => $data['name'],
                            'sku' => $data['sku'],
                            'fill_weight_grams' => $data['fill_weight_grams'],
                            'price' => $data['price'],
                            'is_default' => $data['is_default'],
                            'is_global' => false,
                            'is_active' => $data['is_active'],
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Template Applied Successfully')
                            ->body('The template has been applied to the selected product.')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Apply this template to a specific product'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete price variation')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Price Variation')
                    ->modalDescription('Are you sure you want to delete this price variation? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete it'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Price Variations')
                        ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
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
            'index' => Pages\ListPriceVariations::route('/'),
            'create' => Pages\CreatePriceVariation::route('/create'),
            'edit' => Pages\EditPriceVariation::route('/{record}/edit'),
        ];
    }
}
