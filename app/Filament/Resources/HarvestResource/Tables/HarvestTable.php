<?php

namespace App\Filament\Resources\HarvestResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament table component builder for agricultural harvest data display.
 *
 * Provides comprehensive table configuration for harvest record display with
 * advanced sorting, filtering, and grouping capabilities. Includes agricultural
 * business context with cultivar-based filtering, weight summarization, and
 * complex join operations for optimal performance. Supports harvest analytics
 * and production reporting workflows.
 *
 * @filament_table
 * @business_domain Agricultural harvest data presentation and analytics
 * @related_models Harvest, MasterCultivar, MasterSeedCatalog, User
 * @workflow_support Harvest reporting, production analytics, cultivar performance tracking
 * @performance_optimization Includes join query optimizations and eager loading strategies
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class HarvestTable
{
    /**
     * Generate comprehensive table column configuration for simplified harvest data display.
     *
     * Creates streamlined column set including harvest date, cultivar information,
     * weight metrics with summarizers, and user attribution. Simplified approach
     * eliminates tray complexity and focuses on cultivar-weight tracking for
     * agricultural production analytics and harvest performance.
     *
     * @return array Complete Filament table columns array with simplified agricultural context
     * @filament_method Primary table columns generator for simplified harvest approach
     * @agricultural_metrics Weight totals with summarizers for cultivar-based tracking
     * @business_context Cultivar tracking, user attribution, harvest date organization
     */
    public static function columns(): array
    {
        return [
            static::getHarvestDateColumn(),
            static::getCultivarColumn(),
            static::getTotalWeightColumn(),
            static::getHarvestedByColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    /**
     * Generate table filtering options for harvest data analysis.
     *
     * Provides cultivar-based filtering and date range selection for harvest
     * data analysis. Supports agricultural reporting requirements with business
     * context filtering for production analytics and quality control.
     *
     * @return array Filament table filters array with agricultural business context
     * @filament_method Table filtering configuration
     * @agricultural_filters Cultivar selection, harvest date ranges for production analysis
     * @business_intelligence Supports harvest performance tracking and variety comparison
     */
    public static function filters(): array
    {
        return [
            static::getCultivarFilter(),
            static::getDateRangeFilter(),
        ];
    }

    /**
     * Generate table row actions for harvest record management.
     *
     * Creates action group with view, edit, and delete capabilities for individual
     * harvest records. Provides comprehensive harvest data management with agricultural
     * business context and user-friendly action organization.
     *
     * @return array Filament table actions array with grouped operations
     * @filament_method Row-level actions configuration
     * @crud_operations View, edit, delete actions for harvest record management
     * @ui_optimization Grouped actions with tooltips for better user experience
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()->tooltip('Edit record'),
                DeleteAction::make()->tooltip('Delete record'),
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
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    /**
     * Returns Filament table groups
     */
    public static function groups(): array
    {
        return [
            Group::make('harvest_date')
                ->label('Date Only')
                ->date()
                ->collapsible(),
            Group::make('master_cultivar_id')
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
    protected static function getHarvestDateColumn(): TextColumn
    {
        return TextColumn::make('harvest_date')
            ->label('Date')
            ->date('M j, Y')
            ->sortable();
    }

    /**
     * Cultivar column with complex search and sort logic
     */
    protected static function getCultivarColumn(): TextColumn
    {
        return TextColumn::make('masterCultivar.full_name')
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
    protected static function getTotalWeightColumn(): TextColumn
    {
        return TextColumn::make('total_weight_grams')
            ->label('Total Weight')
            ->suffix(' g')
            ->numeric(1)
            ->sortable()
            ->summarize([
                Sum::make()
                    ->label('Total')
                    ->numeric(1)
                    ->suffix(' g'),
            ]);
    }


    /**
     * Harvested by user column
     */
    protected static function getHarvestedByColumn(): TextColumn
    {
        return TextColumn::make('user.name')
            ->label('Harvested By')
            ->searchable()
            ->toggleable();
    }

    /**
     * Created at timestamp column
     */
    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Cultivar filter for variety selection
     */
    protected static function getCultivarFilter(): SelectFilter
    {
        return SelectFilter::make('master_cultivar_id')
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
    protected static function getDateRangeFilter(): Filter
    {
        return Filter::make('harvest_date')
            ->schema([
                DatePicker::make('from')
                    ->label('From Date'),
                DatePicker::make('until')
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
        
        // Select specific columns to avoid duplicate ID column conflicts
        $query->select([
            'harvests.*',
            'master_cultivars.cultivar_name',
            'master_seed_catalog.common_name'
        ]);
        
        return $query;
    }
}