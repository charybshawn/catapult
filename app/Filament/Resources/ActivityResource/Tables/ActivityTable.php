<?php

namespace App\Filament\Resources\ActivityResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Resources\ActivityResource\Tables\ActivityTableActions;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Comprehensive activity table builder for agricultural system monitoring and audit trails.
 *
 * Provides complete table configuration for activity logs including columns for
 * timestamps, users, activity types, events, descriptions, and model relationships.
 * Features advanced filtering by date ranges, activity types, users, and detail
 * presence for thorough agricultural system analysis and compliance monitoring.
 *
 * @filament_table Table builder for activity log display and analysis
 * @business_domain Agricultural system activity monitoring and audit compliance
 * @filtering_features Date ranges, activity types, users, and detail presence
 * @column_organization Timestamps, users, types, events, descriptions, models
 * @audit_context Comprehensive system activity tracking for agricultural operations
 */
class ActivityTable
{
    /**
     * Generate comprehensive table columns for activity log display.
     *
     * Assembles complete column set covering timestamps, user attribution,
     * activity types, events, descriptions, and model relationships for
     * thorough agricultural system activity monitoring and analysis.
     *
     * @return array Filament table columns for complete activity log display
     * @column_coverage Timestamps, users, types, events, descriptions, models
     * @audit_display Comprehensive activity information for system monitoring
     */
    public static function columns(): array
    {
        return [
            static::getCreatedAtColumn(),
            static::getCauserColumn(),
            static::getLogNameColumn(),
            static::getEventColumn(),
            static::getDescriptionColumn(),
            static::getSubjectTypeColumn(),
            static::getSubjectIdColumn(),
            static::getPropertiesColumn(),
        ];
    }

    /**
     * Generate advanced filtering options for focused activity analysis.
     *
     * Provides comprehensive filtering capabilities including activity types,
     * events, users, date ranges, and detail presence for targeted agricultural
     * system analysis and operational intelligence gathering.
     *
     * @return array Filament table filters for focused activity analysis
     * @filtering_scope Types, events, users, dates, and detail presence
     * @operational_intelligence Enables targeted analysis of system activities
     */
    public static function filters(): array
    {
        return [
            static::getLogNameFilter(),
            static::getEventFilter(),
            static::getCauserFilter(),
            static::getDateRangeFilter(),
            static::getPropertiesFilter(),
        ];
    }

    /**
     * Configure table actions for activity log workflow support.
     *
     * @return array Filament table actions for activity log operations
     */
    public static function actions(): array
    {
        return [
            ActivityTableActions::getActionGroup(),
        ];
    }

    /**
     * Configure bulk actions for activity log management.
     *
     * @return array Filament bulk actions for activity log operations
     */
    public static function bulkActions(): array
    {
        return [
            ActivityTableActions::getBulkActionGroup(),
        ];
    }

    /**
     * Configure header actions for activity log functionality.
     *
     * @return array Filament header actions for activity log operations
     */
    public static function headerActions(): array
    {
        return ActivityTableActions::getHeaderActions();
    }

    /**
     * Generate created at timestamp column for activity chronology.
     *
     * Creates formatted timestamp column showing when activities occurred
     * for agricultural system monitoring and audit trail chronology.
     *
     * @return TextColumn Filament text column with formatted timestamps
     * @timestamp_format Month day, year with time for operational context
     */
    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Time')
            ->dateTime('M j, Y g:i:s A')
            ->sortable()
            ->size('sm')
            ->color('gray');
    }

    /**
     * Generate causer column for user attribution and accountability.
     *
     * Creates column showing who initiated activities with appropriate icons
     * for user versus system actions. Essential for agricultural operations
     * accountability and audit trail analysis.
     *
     * @return TextColumn Filament text column with user attribution and icons
     * @accountability_context User names with system/user icon differentiation
     */
    protected static function getCauserColumn(): TextColumn
    {
        return TextColumn::make('causer.name')
            ->label('User')
            ->searchable()
            ->default('System')
            ->formatStateUsing(fn ($state, $record) => 
                $record->causer ? $state : 'System'
            )
            ->icon(fn ($record) => 
                $record->causer_type === 'App\Models\User' ? 'heroicon-m-user' : 'heroicon-m-cog'
            );
    }

    /**
     * Generate log name column with color-coded badge styling for activity categorization.
     *
     * Creates badge column showing activity categories (auth, error, api, job,
     * query, timecard) with color coding for quick visual identification
     * in agricultural system monitoring.
     *
     * @return TextColumn Filament text column with color-coded activity type badges
     * @visual_categorization Color-coded badges for auth, error, api, job types
     */
    protected static function getLogNameColumn(): TextColumn
    {
        return TextColumn::make('log_name')
            ->label('Type')
            ->badge()
            ->formatStateUsing(fn ($state) => ucfirst($state ?? 'default'))
            ->color(fn (string $state): string => match ($state) {
                'auth' => 'success',
                'error' => 'danger',
                'api' => 'info',
                'job' => 'warning',
                'query' => 'gray',
                'timecard' => 'primary',
                default => 'secondary',
            })
            ->searchable();
    }

    /**
     * Event column with badge styling
     */
    protected static function getEventColumn(): TextColumn
    {
        return TextColumn::make('event')
            ->label('Action')
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'created' => 'success',
                'updated' => 'info',
                'deleted' => 'danger',
                'restored' => 'warning',
                'login' => 'success',
                'logout' => 'gray',
                'failed' => 'danger',
                default => 'secondary',
            })
            ->searchable();
    }

    /**
     * Description column with tooltip for long text
     */
    protected static function getDescriptionColumn(): TextColumn
    {
        return TextColumn::make('description')
            ->label('Description')
            ->searchable()
            ->limit(50)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();
                return strlen($state) > 50 ? $state : null;
            });
    }

    /**
     * Subject type column with class basename formatting
     */
    protected static function getSubjectTypeColumn(): TextColumn
    {
        return TextColumn::make('subject_type')
            ->label('Model')
            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
            ->searchable()
            ->toggleable();
    }

    /**
     * Subject ID column
     */
    protected static function getSubjectIdColumn(): TextColumn
    {
        return TextColumn::make('subject_id')
            ->label('Model ID')
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Properties column with icon indicator
     */
    protected static function getPropertiesColumn(): IconColumn
    {
        return IconColumn::make('properties')
            ->label('Details')
            ->boolean()
            ->trueIcon('heroicon-o-document-text')
            ->falseIcon('heroicon-o-x-circle')
            ->getStateUsing(fn ($record) => !empty($record->properties));
    }

    /**
     * Log name filter
     */
    protected static function getLogNameFilter(): SelectFilter
    {
        return SelectFilter::make('log_name')
            ->label('Type')
            ->multiple()
            ->options(function () {
                return Activity::distinct()
                    ->pluck('log_name')
                    ->mapWithKeys(fn ($name) => [$name => ucfirst($name ?? 'default')])
                    ->toArray();
            })
            ->indicator('Type');
    }

    /**
     * Event filter
     */
    protected static function getEventFilter(): SelectFilter
    {
        return SelectFilter::make('event')
            ->label('Action')
            ->multiple()
            ->options(function () {
                return Activity::distinct()
                    ->pluck('event')
                    ->mapWithKeys(fn ($event) => [$event => ucfirst($event)])
                    ->toArray();
            })
            ->indicator('Action');
    }

    /**
     * Causer filter
     */
    protected static function getCauserFilter(): SelectFilter
    {
        return SelectFilter::make('causer_id')
            ->label('User')
            ->options(function () {
                return Activity::query()
                    ->whereNotNull('causer_id')
                    ->whereNotNull('causer_type')
                    ->with('causer')
                    ->get()
                    ->pluck('causer.name', 'causer_id')
                    ->filter()
                    ->unique()
                    ->sort();
            })
            ->searchable()
            ->indicator('User');
    }

    /**
     * Date range filter
     */
    protected static function getDateRangeFilter(): Filter
    {
        return Filter::make('created_at')
            ->schema([
                DatePicker::make('created_from')
                    ->label('From'),
                DatePicker::make('created_until')
                    ->label('Until')
                    ->default(now()),
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
                    $indicators['created_from'] = 'From: ' . Carbon::parse($data['created_from'])->format('M j, Y');
                }
                if ($data['created_until'] ?? null) {
                    $indicators['created_until'] = 'Until: ' . Carbon::parse($data['created_until'])->format('M j, Y');
                }
                return $indicators;
            });
    }

    /**
     * Properties filter
     */
    protected static function getPropertiesFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_properties')
            ->label('Has Details')
            ->placeholder('All activities')
            ->trueLabel('With details')
            ->falseLabel('Without details')
            ->queries(
                true: fn (Builder $query) => $query->whereNotNull('properties')->where('properties', '!=', '[]'),
                false: fn (Builder $query) => $query->whereNull('properties')->orWhere('properties', '[]'),
            );
    }

}