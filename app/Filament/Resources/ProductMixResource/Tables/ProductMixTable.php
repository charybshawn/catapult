<?php

namespace App\Filament\Resources\ProductMixResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Actions\ProductMix\DuplicateProductMixAction;
use App\Models\ProductMix;
use App\Filament\Resources\ProductMixResource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProductMixTable
{
    /**
     * Modify the base query for the ProductMix table
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('masterSeedCatalogs');
    }

    /**
     * Get table columns for ProductMixResource
     */
    public static function columns(): array
    {
        return [
            static::getNameColumn(),
            static::getComponentsSummaryColumn(),
            static::getProductsCountColumn(),
            static::getHasAllRecipesColumn(),
            static::getIsActiveColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    /**
     * Name column with link to edit page
     */
    protected static function getNameColumn(): TextColumn
    {
        return TextColumn::make('name')
            ->label('Name')
            ->searchable()
            ->sortable()
            ->toggleable()
            ->url(fn (ProductMix $record): string => ProductMixResource::getUrl('edit', ['record' => $record]))
            ->color('primary');
    }

    /**
     * Mix components summary column with visual component tags
     */
    protected static function getComponentsSummaryColumn(): TextColumn
    {
        return TextColumn::make('components_summary')
            ->label('Mix Components')
            ->html()
            ->getStateUsing(function (ProductMix $record): string {
                $components = $record->masterSeedCatalogs()
                    ->withPivot('percentage', 'cultivar')
                    ->get()
                    ->map(function ($catalog) {
                        return "<span class='inline-flex items-center px-2 py-1 mr-1 mb-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full dark:bg-gray-700 dark:text-gray-300'>" .
                        "{$catalog->common_name}" . 
                        ($catalog->pivot->cultivar ? " ({$catalog->pivot->cultivar})" : "") .
                        " - " . number_format($catalog->pivot->percentage, 2) . "%" .
                        "</span>";
                    })
                    ->join('');
                
                return $components ?: '<span class="text-gray-400">No components</span>';
            })
            ->searchable(false)
            ->sortable(false);
    }

    /**
     * Products count column showing usage
     */
    protected static function getProductsCountColumn(): TextColumn
    {
        return TextColumn::make('products_count')
            ->label('Used in Products')
            ->getStateUsing(fn (ProductMix $record): string => 
                $record->products()->count() . ' product(s)'
            )
            ->badge()
            ->color(fn (ProductMix $record): string => 
                $record->products()->count() > 0 ? 'success' : 'gray'
            )
            ->sortable(false);
    }

    /**
     * Recipe completion status column
     */
    protected static function getHasAllRecipesColumn(): IconColumn
    {
        return IconColumn::make('has_all_recipes')
            ->label('Recipes')
            ->getStateUsing(fn (ProductMix $record): bool => $record->hasAllRecipes())
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-exclamation-triangle')
            ->trueColor('success')
            ->falseColor('warning')
            ->tooltip(fn (ProductMix $record): string => 
                $record->hasAllRecipes() 
                    ? 'All components have recipes'
                    : 'Some components missing recipes'
            )
            ->sortable(false);
    }

    /**
     * Active status column
     */
    protected static function getIsActiveColumn(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->sortable()
            ->toggleable();
    }

    /**
     * Created at timestamp column
     */
    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Created')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get table filters for ProductMixResource
     */
    public static function filters(): array
    {
        return [
            static::getActiveFilter(),
            static::getUnusedFilter(),
            static::getIncompleteFilter(),
        ];
    }

    /**
     * Active/inactive status filter
     */
    protected static function getActiveFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label('Status')
            ->placeholder('All mixes')
            ->trueLabel('Active only')
            ->falseLabel('Inactive only');
    }

    /**
     * Filter for mixes not used in any products
     */
    protected static function getUnusedFilter(): Filter
    {
        return Filter::make('unused')
            ->label('Unused Mixes')
            ->query(fn (Builder $query) => $query->whereDoesntHave('products'));
    }

    /**
     * Filter for mixes without any components
     */
    protected static function getIncompleteFilter(): Filter
    {
        return Filter::make('incomplete')
            ->label('Incomplete Mixes')
            ->query(fn (Builder $query) => $query->whereDoesntHave('masterSeedCatalogs'));
    }

    /**
     * Get table actions for ProductMixResource
     */
    public static function actions(): array
    {
        return [
            static::getActionGroup(),
        ];
    }

    /**
     * Action group with all available actions
     */
    protected static function getActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            static::getViewAction(),
            static::getEditAction(),
            static::getDuplicateAction(),
            static::getDeleteAction(),
        ])
        ->label('Actions')
        ->icon('heroicon-m-ellipsis-vertical')
        ->size('sm')
        ->color('gray')
        ->button();
    }

    /**
     * View action
     */
    protected static function getViewAction(): ViewAction
    {
        return ViewAction::make()->tooltip('View record');
    }

    /**
     * Edit action
     */
    protected static function getEditAction(): EditAction
    {
        return EditAction::make()
            ->tooltip('Edit mix');
    }

    /**
     * Duplicate action using dedicated Action class
     */
    protected static function getDuplicateAction(): Action
    {
        return Action::make('duplicate')
            ->label('Duplicate')
            ->icon('heroicon-o-document-duplicate')
            ->color('gray')
            ->tooltip('Create a copy of this mix')
            ->action(function (ProductMix $record) {
                $newMix = app(DuplicateProductMixAction::class)->execute($record);
                redirect(ProductMixResource::getUrl('edit', ['record' => $newMix]));
            });
    }

    /**
     * Delete action with protection for used mixes
     */
    protected static function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip('Delete mix')
            ->before(function (ProductMix $record) {
                if ($record->products()->count() > 0) {
                    throw new Exception('Cannot delete mix that is used by products.');
                }
            });
    }

    /**
     * Get bulk actions for ProductMixResource
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }
}