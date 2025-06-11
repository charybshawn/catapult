<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackagingTypeResource\Pages;
use App\Filament\Resources\PackagingTypeResource\RelationManagers;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackagingTypeResource extends Resource
{
    protected static ?string $model = PackagingType::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Packaging Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Name of the packaging type (e.g., "Clamshell")'),
                            
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('capacity_volume')
                                    ->label('Volume')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01),
                                    
                                Forms\Components\Select::make('volume_unit')
                                    ->label('Unit')
                                    ->options([
                                        'oz' => 'Ounces (oz)',
                                        'ml' => 'Milliliters (ml)',
                                        'l' => 'Liters (l)',
                                        'pt' => 'Pints (pt)',
                                        'qt' => 'Quarts (qt)',
                                        'gal' => 'Gallons (gal)',
                                    ])
                                    ->default('oz')
                                    ->required(),
                            ])
                            ->columns(2),
                            
                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
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
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('capacity_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn (PackagingType $record): string => 
                        "{$record->capacity_volume} {$record->volume_unit}")
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
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
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit packaging type'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete packaging type'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Mark as Inactive')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListPackagingTypes::route('/'),
            'create' => Pages\CreatePackagingType::route('/create'),
            'edit' => Pages\EditPackagingType::route('/{record}/edit'),
        ];
    }
}
