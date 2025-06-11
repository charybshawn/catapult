<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductMixResource\Pages;
use App\Models\ProductMix;
use App\Models\SeedEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductMixResource extends Resource
{
    protected static ?string $model = ProductMix::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'Product Mixes';
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 3;

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
                            ->label('Varieties')
                            ->schema([
                                Forms\Components\Select::make('seed_entry_id')
                                    ->label('Cultivar')
                                    ->options(SeedEntry::all()->pluck('cultivar_name', 'id'))
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('cultivar_name')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('common_name')
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->maxLength(500),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return SeedEntry::create([
                                            'cultivar_name' => $data['cultivar_name'],
                                            'common_name' => $data['common_name'] ?? null,
                                            'description' => $data['description'] ?? null,
                                        ])->id;
                                    })
                                    ->preload()
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
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (ProductMix $record): string => ProductMixResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('components_summary')
                    ->label('Mix Components')
                    ->html()
                    ->getStateUsing(function (ProductMix $record): string {
                        $components = $record->seedCultivars()
                            ->withPivot('percentage')
                            ->get()
                            ->map(fn ($variety) => 
                                "<span class='inline-flex items-center px-2 py-1 mr-1 mb-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-700 dark:text-gray-300'>" .
                                "{$variety->name} ({$variety->pivot->percentage}%)" .
                                "</span>"
                            )
                            ->join('');
                        
                        return $components ?: '<span class="text-gray-400">No components</span>';
                    })
                    ->searchable(false)
                    ->sortable(false),
                    
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Used in Products')
                    ->getStateUsing(fn (ProductMix $record): string => 
                        $record->products()->count() . ' product(s)'
                    )
                    ->badge()
                    ->color(fn (ProductMix $record): string => 
                        $record->products()->count() > 0 ? 'success' : 'gray'
                    )
                    ->sortable(false),
                    
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
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All mixes')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                Tables\Filters\Filter::make('unused')
                    ->label('Unused Mixes')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('products')),
                Tables\Filters\Filter::make('incomplete')
                    ->label('Incomplete Mixes')
                    ->query(fn (Builder $query) => $query->whereDoesntHave('seedCultivars')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View mix details'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit mix'),
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->tooltip('Create a copy of this mix')
                    ->action(function (ProductMix $record) {
                        $newMix = $record->replicate();
                        $newMix->name = $record->name . ' (Copy)';
                        $newMix->save();
                        
                        // Copy the seed varieties
                        foreach ($record->seedCultivars as $variety) {
                            $newMix->seedCultivars()->attach($variety->id, [
                                'percentage' => $variety->pivot->percentage,
                            ]);
                        }
                        
                        redirect(static::getUrl('edit', ['record' => $newMix]));
                    }),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete mix')
                    ->before(function (ProductMix $record) {
                        if ($record->products()->count() > 0) {
                            throw new \Exception('Cannot delete mix that is used by products.');
                        }
                    }),
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
            'view' => Pages\ViewProductMix::route('/{record}'),
            'edit' => Pages\EditProductMix::route('/{record}/edit'),
        ];
    }
} 