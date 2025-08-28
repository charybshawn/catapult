<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use App\Filament\Resources\SeedEntryResource\Pages\ListSeedEntries;
use App\Filament\Resources\SeedEntryResource\Pages\CreateSeedEntry;
use App\Filament\Resources\SeedEntryResource\Pages\EditSeedEntry;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\SeedEntryResource\Forms\SeedEntryForm;
use App\Filament\Resources\SeedEntryResource\Pages;
use App\Filament\Resources\SeedEntryResource\Tables\SeedEntryTable;
use App\Models\SeedEntry;
use Filament\Tables\Table;

class SeedEntryResource extends BaseResource
{
    protected static ?string $model = SeedEntry::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationLabel = 'Seeds';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(SeedEntryForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->modifyQueryUsing(fn ($query) => SeedEntryTable::modifyQuery($query))
            ->columns([
                ...SeedEntryTable::columns(),
                ...static::getTimestampColumns(),
            ])
            ->filters(SeedEntryTable::filters())
            ->recordActions(SeedEntryTable::actions())
            ->toolbarActions(SeedEntryTable::bulkActions())
            ->recordAction(EditAction::class);
    }

    public static function getRelations(): array
    {
        return [
            // Variations are now managed inline in the form using the custom component
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeedEntries::route('/'),
            'create' => CreateSeedEntry::route('/create'),
            'edit' => EditSeedEntry::route('/{record}/edit'),
        ];
    }
} 