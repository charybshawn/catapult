<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use App\Models\VolumeUnit;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\PackagingTypeResource\Pages\ListPackagingTypes;
use App\Filament\Resources\PackagingTypeResource\Pages\CreatePackagingType;
use App\Filament\Resources\PackagingTypeResource\Pages\EditPackagingType;
use App\Filament\Resources\PackagingTypeResource\Pages;
use App\Filament\Resources\PackagingTypeResource\RelationManagers;
use App\Models\PackagingType;
use App\Models\PackagingTypeCategory;
use App\Models\PackagingUnitType;
use Filament\Forms;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackagingTypeResource extends BaseResource
{
    protected static ?string $model = PackagingType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Packaging Details')
                    ->schema([
                        static::getNameField()
                            ->helperText('Name of the packaging type (e.g., "Clamshell")'),
                            
                        Select::make('type_category_id')
                            ->label('Category')
                            ->required()
                            ->relationship('typeCategory', 'name')
                            ->options(PackagingTypeCategory::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->helperText('Select the category that best describes this packaging type'),
                            
                        Select::make('unit_type_id')
                            ->label('Unit Type')
                            ->required()
                            ->relationship('unitType', 'name')
                            ->options(PackagingUnitType::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->helperText('Select whether this packaging is sold by count or weight'),
                            
                        Grid::make()
                            ->schema([
                                TextInput::make('capacity_volume')
                                    ->label('Volume')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->step(0.01),
                                    
                                Select::make('volume_unit')
                                    ->label('Unit')
                                    ->options(VolumeUnit::options())
                                    ->default('oz')
                                    ->required(),
                            ])
                            ->columns(2),
                            
                        Textarea::make('description')
                            ->maxLength(1000)
                            ->columnSpanFull(),
                            
                        Toggle::make('is_active')
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
            ->persistSearchInSession()
            ->columns([
                static::getNameColumn('Name'),
                    
                TextColumn::make('typeCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color(fn (PackagingType $record): string => $record->typeCategory?->color ?? 'gray')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('unitType.name')
                    ->label('Unit Type')
                    ->badge()
                    ->color(fn (PackagingType $record): string => $record->unitType?->color ?? 'gray')
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('capacity_volume')
                    ->label('Volume')
                    ->formatStateUsing(fn (PackagingType $record): string => 
                        "{$record->capacity_volume} {$record->volume_unit}")
                    ->sortable()
                    ->toggleable(),
                    
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip('Edit packaging type'),
                DeleteAction::make()
                    ->tooltip('Delete packaging type'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Mark as Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
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
            'index' => ListPackagingTypes::route('/'),
            'create' => CreatePackagingType::route('/create'),
            'edit' => EditPackagingType::route('/{record}/edit'),
        ];
    }
}
