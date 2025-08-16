<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Forms\RecipeForm;
use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\Tables\RecipeTable;
use App\Models\Recipe;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\LogOptions;

class RecipeResource extends BaseResource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Recipes';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 1;
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema(RecipeForm::schema());
    }

    public static function table(Table $table): Table
    {
        [$defaultSortColumn, $defaultSortDirection] = RecipeTable::getDefaultSort();
        
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => RecipeTable::modifyQuery($query))
            ->columns([
                static::getNameColumn(),
                ...array_slice(RecipeTable::columns(), 1), // Skip the first column (name) and use the rest
            ])
            ->defaultSort($defaultSortColumn, $defaultSortDirection)
            ->filters(RecipeTable::filters())
            ->actions(RecipeTable::actions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Recipe-specific bulk actions from RecipeTable
                    ...RecipeTable::getBulkActions(),
                    // Standard active status bulk actions from BaseResource
                    ...static::getActiveStatusBulkActions(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relation managers needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'common_name',
                'cultivar_name',
                'lot_number', 
                'germination_days', 
                'blackout_days', 
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active',
                'planting_notes',
                'harvesting_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}