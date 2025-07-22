<?php

namespace App\Filament\Resources\HarvestResource\Tables;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class HarvestTable
{
    /**
     * Returns Filament table columns for Harvest resources
     */
    public static function columns(): array
    {
        return [
            static::getHarvestDateColumn(),
            static::getCultivarColumn(),
            static::getTotalWeightColumn(),
            static::getTrayCountColumn(),
            static::getAverageWeightColumn(),
            static::getHarvestedByColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    /**
     * Returns Filament table filters
     */
    public static function filters(): array
    {
        return [
            static::getCultivarFilter(),
            static::getDateRangeFilter(),
        ];
    }

    /**
     * Returns Filament table actions
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()->tooltip('View record'),
                Tables\Actions\EditAction::make()->tooltip('Edit record'),
                Tables\Actions\DeleteAction::make()->tooltip('Delete record'),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Returns Filament table bulk actions
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ];
    }

    /**
     * Returns Filament table groups
     */
    public static function groups(): array
    {
        return [
            Tables\Grouping\Group::make('harvest_date')
                ->label('Date Only')
                ->date()
                ->collapsible(),
            Tables\Grouping\Group::make('master_cultivar_id')
                ->label('Variety Only')
                ->getTitleFromRecordUsing(fn (Harvest $record): string => $record->masterCultivar->full_name)
                ->orderQueryUsing(function (Builder $query, string $direction) {
                    return static::joinCultivarTables($query)
                        ->orderBy('master_seed_catalog.common_name', $direction)
                        ->orderBy('master_cultivars.cultivar_name', $direction);
                })
                ->collapsible(),
        ];
    }

    /**
     * Configure table query modifications
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'masterCultivar.masterSeedCatalog'
        ]);
    }

    /**
     * Harvest date column
     */
    protected static function getHarvestDateColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('harvest_date')
            ->label('Date')
            ->date('M j, Y')
            ->sortable();
    }

    /**
     * Cultivar column with complex search and sort logic
     */
    protected static function getCultivarColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('masterCultivar.full_name')
            ->label('Variety')
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereHas('masterCultivar', function (Builder $query) use ($search) {
                    $query->where('cultivar_name', 'like', "%{$search}%")
                        ->orWhereHas('masterSeedCatalog', function (Builder $query) use ($search) {
                            $query->where('common_name', 'like', "%{$search}%");
                        });
                });
            })
            ->sortable(query: function (Builder $query, string $direction): Builder {
                return static::joinCultivarTables($query)
                    ->orderBy('master_seed_catalog.common_name', $direction)
                    ->orderBy('master_cultivars.cultivar_name', $direction);
            });
    }

    /**
     * Total weight column with summarizer
     */
    protected static function getTotalWeightColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('total_weight_grams')
            ->label('Total Weight')
            ->suffix(' g')
            ->numeric(1)
            ->sortable()
            ->summarize([
                Tables\Columns\Summarizers\Sum::make()
                    ->label('Total')
                    ->numeric(1)
                    ->suffix(' g'),
            ]);
    }

    /**
     * Tray count column with summarizer
     */
    protected static function getTrayCountColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('tray_count')
            ->label('Trays')
            ->numeric()
            ->sortable()
            ->summarize([
                Tables\Columns\Summarizers\Sum::make()
                    ->label('Total'),
            ]);
    }

    /**
     * Average weight per tray column with summarizer
     */
    protected static function getAverageWeightColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('average_weight_per_tray')
            ->label('Avg/Tray')
            ->suffix(' g')
            ->numeric(1)
            ->sortable()
            ->summarize([
                Tables\Columns\Summarizers\Average::make()
                    ->label('Average')
                    ->numeric(1)
                    ->suffix(' g'),
            ]);
    }

    /**
     * Harvested by user column
     */
    protected static function getHarvestedByColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('user.name')
            ->label('Harvested By')
            ->searchable()
            ->toggleable();
    }

    /**
     * Created at timestamp column
     */
    protected static function getCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Cultivar filter for variety selection
     */
    protected static function getCultivarFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('master_cultivar_id')
            ->label('Variety')
            ->options(function () {
                return MasterCultivar::with('masterSeedCatalog')
                    ->whereHas('harvests')
                    ->get()
                    ->mapWithKeys(function ($cultivar) {
                        return [$cultivar->id => $cultivar->full_name];
                    });
            })
            ->searchable();
    }

    /**
     * Date range filter for harvest dates
     */
    protected static function getDateRangeFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('harvest_date')
            ->form([
                Forms\Components\DatePicker::make('from')
                    ->label('From Date'),
                Forms\Components\DatePicker::make('until')
                    ->label('Until Date'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['from'],
                        fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '>=', $date),
                    )
                    ->when(
                        $data['until'],
                        fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '<=', $date),
                    );
            });
    }

    /**
     * Helper method to join cultivar tables for sorting/ordering
     */
    protected static function joinCultivarTables(Builder $query): Builder
    {
        // Check if joins already exist to avoid duplicates
        $joins = collect($query->getQuery()->joins);
        
        if (!$joins->pluck('table')->contains('master_cultivars')) {
            $query->join('master_cultivars', 'harvests.master_cultivar_id', '=', 'master_cultivars.id');
        }
        
        if (!$joins->pluck('table')->contains('master_seed_catalog')) {
            $query->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id');
        }
        
        return $query;
    }
}