<?php

namespace App\Filament\Traits;

use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Has Timestamps Trait
 * 
 * Standardized timestamp management for agricultural Filament resources providing
 * consistent created_at and updated_at display, filtering, and form integration.
 * Essential for agricultural data tracking and audit trails.
 * 
 * @filament_trait Timestamp management for agricultural resource tracking
 * @agricultural_use Timestamp tracking for agricultural entities (crop planting dates, order creation, inventory updates)
 * @audit_trail Creation and modification tracking for agricultural business records
 * @data_analysis Temporal filtering and analysis capabilities for agricultural data
 * 
 * Key features:
 * - Timestamp table columns with agricultural date formatting
 * - Form placeholders for creation and modification timestamps
 * - Collapsible system information sections
 * - Date range filtering for agricultural data analysis
 * - Consistent timestamp display across agricultural resources
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasTimestamps
{
    /**
     * Get timestamp columns for agricultural resource tables.
     * 
     * @agricultural_context Creation and modification timestamps for agricultural entities
     * @return array Timestamp columns for created_at and updated_at with agricultural formatting
     * @visibility Hidden by default, toggleable for agricultural data analysis
     */
    public static function getTimestampColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
    
    /**
     * Get timestamp placeholders for forms
     */
    public static function getTimestampFields(): array
    {
        return [
            Placeholder::make('created_at')
                ->label('Created')
                ->content(fn ($record): string => $record ? $record->created_at->format('M d, Y H:i') : 'Not created yet'),
            Placeholder::make('updated_at')
                ->label('Last Updated')
                ->content(fn ($record): string => $record ? $record->updated_at->format('M d, Y H:i') : 'Not updated yet'),
        ];
    }
    
    /**
     * Get timestamp section for agricultural resource forms.
     * 
     * @agricultural_context System information section for agricultural entity forms
     * @return Section Collapsible timestamp section with creation and modification dates
     * @behavior Hidden for new records, collapsed by default for existing agricultural entities
     */
    public static function getTimestampSection(): Section
    {
        return Section::make('System Information')
            ->schema(static::getTimestampFields())
            ->columns(2)
            ->collapsed()
            ->hidden(fn ($record): bool => $record === null);
    }
    
    /**
     * Get creation date range filter for agricultural data analysis.
     * 
     * @agricultural_context Date range filtering for agricultural entity creation tracking
     * @return Filter Date range filter for created_at field with agricultural date analysis
     * @use_cases Crop planting date ranges, order creation periods, inventory receipt tracking
     */
    public static function getCreatedAtDateRangeFilter(): Filter
    {
        return Filter::make('created_at')
            ->schema([
                DatePicker::make('created_from')
                    ->label('Created from'),
                DatePicker::make('created_until')
                    ->label('Created until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['created_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                    )
                    ->when(
                        $data['created_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                
                if ($data['created_from'] ?? null) {
                    $indicators['created_from'] = 'Created from ' . $data['created_from'];
                }
                
                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Created until ' . $data['created_until'];
                }
                
                return $indicators;
            });
    }
    
    /**
     * Get date range filter for updated_at
     */
    public static function getUpdatedAtDateRangeFilter(): Filter
    {
        return Filter::make('updated_at')
            ->schema([
                DatePicker::make('updated_from')
                    ->label('Updated from'),
                DatePicker::make('updated_until')
                    ->label('Updated until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['updated_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '>=', $date),
                    )
                    ->when(
                        $data['updated_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate('updated_at', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                
                if ($data['updated_from'] ?? null) {
                    $indicators['updated_from'] = 'Updated from ' . $data['updated_from'];
                }
                
                if ($data['updated_until'] ?? null) {
                    $indicators['updated_until'] = 'Updated until ' . $data['updated_until'];
                }
                
                return $indicators;
            });
    }
}