<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\SeedEntryResource\Forms\SeedEntryForm;
use App\Filament\Resources\SeedEntryResource\Pages;
use App\Filament\Resources\SeedEntryResource\Tables\SeedEntryTable;
use App\Models\SeedEntry;
use Filament\Forms\Form;
use Filament\Tables\Table;

class SeedEntryResource extends BaseResource
{
    protected static ?string $model = SeedEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    
    protected static ?string $navigationLabel = 'Seeds';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema(SeedEntryForm::schema());
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
            ->actions(SeedEntryTable::actions())
            ->bulkActions(SeedEntryTable::bulkActions())
            ->recordAction(\Filament\Tables\Actions\EditAction::class);
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
            'index' => Pages\ListSeedEntries::route('/'),
            'create' => Pages\CreateSeedEntry::route('/create'),
            'edit' => Pages\EditSeedEntry::route('/{record}/edit'),
        ];
    }
} 