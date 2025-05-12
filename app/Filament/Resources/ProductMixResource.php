<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductMixResource\Pages;
use App\Models\ProductMix;
use App\Models\SeedVariety;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductMixResource extends Resource
{
    protected static ?string $model = ProductMix::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Product Mixes';
    protected static ?string $navigationGroup = 'Inventory & Supplies';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Mix Name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Mix Components')
                    ->schema([
                        Forms\Components\Repeater::make('components')
                            ->relationship('seedVarieties')
                            ->label('Varieties')
                            ->addActionLabel('Add Variety')
                            ->schema([
                                Forms\Components\Select::make('seed_variety_id')
                                    ->label('Variety')
                                    ->options(SeedVariety::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('percentage')
                                    ->label('Percentage (%)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(100)
                                    ->required()
                                    ->default(25),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->columnSpanFull()
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                return [
                                    'seed_variety_id' => $data['seed_variety_id'],
                                    'percentage' => $data['percentage'],
                                ];
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (ProductMix $record): string => ProductMixResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('seedVarieties.name')
                    ->label('Components')
                    ->listWithLineBreaks()
                    ->searchable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('seedVarieties.pivot.percentage')
                    ->label('Percentages')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->listWithLineBreaks()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit mix'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete mix'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductMixes::route('/'),
            'create' => Pages\CreateProductMix::route('/create'),
            'edit' => Pages\EditProductMix::route('/{record}/edit'),
        ];
    }
} 