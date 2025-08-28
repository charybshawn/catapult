<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use App\Models\SeedEntry;
use App\Models\SeedVariation;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Intelligent seed reorder advisory widget for agricultural inventory management.
 *
 * Provides automated recommendations for seed restocking based on current inventory
 * levels, restock thresholds, and supplier availability. Displays low-stock seeds
 * with pricing information, supplier details, and direct links to facilitate
 * efficient procurement decisions in microgreens production operations.
 *
 * @filament_widget Table widget for intelligent seed inventory management
 * @business_domain Agricultural seed procurement and inventory optimization
 * @inventory_intelligence Automated low-stock detection and reorder recommendations
 * @procurement_support Direct supplier links and pricing comparison tools
 * @operational_efficiency Prevents production delays through proactive restocking alerts
 */
class SeedReorderAdvisorWidget extends BaseWidget
{
    /** @var string Widget heading for seed reorder recommendations */
    protected static ?string $heading = 'Recommended Seed Reorders';
    
    /** @var string Widget column span for full-width inventory display */
    protected int | string | array $columnSpan = 'full';
    
    /**
     * Generate query for seed reorder recommendations based on inventory levels.
     *
     * Identifies seed variations that are currently in stock at suppliers but
     * have low inventory levels (at or below restock threshold) requiring
     * attention. Orders by current price to highlight cost-effective options
     * for agricultural procurement decisions.
     *
     * @return Builder Eloquent query for low-stock seed variations needing reorder
     * @business_logic Compares current quantity with restock level threshold
     * @inventory_intelligence Only shows in-stock supplier items for immediate ordering
     */
    protected function getTableQuery(): Builder
    {
        return SeedVariation::query()
            ->with(['seedEntry', 'seedEntry.supplier', 'consumable'])
            ->whereHas('consumable', function (Builder $query) {
                $query->whereRaw('quantity <= restock_level');
            })
            ->where('is_in_stock', true)
            ->orderBy('current_price');
    }
    
    /**
     * Get pagination options for reorder advisor table display.
     *
     * @return array Available records per page options for inventory management
     */
    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 25, 50];
    }

    /**
     * Configure table columns for comprehensive seed reorder information display.
     *
     * Displays essential procurement information including variety details,
     * supplier information, pricing (absolute and per-kg), current stock levels,
     * and restock thresholds to support informed purchasing decisions for
     * agricultural operations.
     *
     * @return array Filament table column definitions for reorder advisory display
     * @business_context Shows variety, supplier, pricing, and inventory data
     * @procurement_support Includes calculated price per kg for cost comparison
     */
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('seedEntry.common_name')
                ->label('Common Name')
                ->searchable()
                ->sortable(),
            TextColumn::make('seedEntry.cultivar_name')
                ->label('Cultivar')
                ->searchable()
                ->sortable(),
            TextColumn::make('seedEntry.supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable(),
            TextColumn::make('size_description')
                ->searchable()
                ->sortable(),
            TextColumn::make('current_price')
                ->money('USD')
                ->sortable(),
            TextColumn::make('price_per_kg')
                ->label('Price per kg')
                ->money('USD')
                ->getStateUsing(fn (SeedVariation $record): ?float => 
                    $record->weight_kg && $record->weight_kg > 0 ? 
                    $record->current_price / $record->weight_kg : null
                )
                ->sortable(),
            TextColumn::make('consumable.quantity')
                ->label('Current Stock')
                ->numeric()
                ->sortable(),
            TextColumn::make('consumable.restock_level')
                ->label('Restock Level')
                ->numeric()
                ->sortable(),
        ];
    }
    
    /**
     * Configure table actions for seed reorder workflow facilitation.
     *
     * Provides quick access to detailed seed variation information and direct
     * links to supplier product pages for immediate ordering actions. Supports
     * efficient procurement workflow from identification to purchase.
     *
     * @return array Filament table actions for reorder workflow support
     * @workflow_efficiency Direct links to seed details and supplier pages
     * @operational_support Streamlines procurement process with single-click access
     */
    protected function getTableActions(): array
    {
        return [
            Action::make('view_product')
                ->label('View Product')
                ->url(fn (SeedVariation $record): string => route('filament.admin.resources.seed-variations.edit', $record))
                ->icon('heroicon-o-eye'),
            Action::make('visit_url')
                ->label('Visit Supplier')
                ->url(fn (SeedVariation $record): string => $record->seedEntry->supplier_product_url)
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->openUrlInNewTab(),
        ];
    }
    
    /**
     * Configure table filters for focused seed reorder analysis.
     *
     * Provides filtering capabilities by common name and supplier to enable
     * targeted reorder analysis and strategic procurement planning based on
     * specific varieties or supplier relationships.
     *
     * @return array Filament table filter definitions for reorder refinement
     * @filtering_context Common name and supplier-based reorder focusing
     * @procurement_planning Enables targeted analysis by variety or supplier
     */
    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('common_name')
                ->options(function () {
                    return $this->getCommonNameOptions();
                })
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['value'],
                        fn (Builder $query, $value): Builder => $query->whereHas('seedEntry', function ($q) use ($value) {
                            $q->where('common_name', $value);
                        })
                    );
                })
                ->searchable()
                ->label('Common Name'),
            SelectFilter::make('supplier')
                ->relationship('seedEntry.supplier', 'name')
                ->searchable()
                ->preload()
                ->label('Supplier'),
        ];
    }
    
    /**
     * Generate common name filter options from available in-stock seed entries.
     *
     * Dynamically builds filter options from seed entries that have in-stock
     * variations, ensuring filter relevance and avoiding empty result sets
     * when users filter by common name for reorder planning.
     *
     * @return array Common name options for table filtering
     * @data_accuracy Only includes names with actual in-stock inventory
     * @user_experience Prevents empty results from unavailable seed types
     */
    protected function getCommonNameOptions(): array
    {
        // Get common names from seed entries that have in-stock variations
        // This ensures we only show options that are actually available for reorder
        $seedEntries = SeedEntry::whereHas('variations', function($q) {
                $q->where('is_in_stock', true);
            })
            ->whereNotNull('common_name')
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name');
        
        $commonNames = [];
        foreach ($seedEntries as $commonName) {
            if (!empty($commonName)) {
                $commonNames[$commonName] = $commonName;
            }
        }
        
        return $commonNames;
    }
    
} 