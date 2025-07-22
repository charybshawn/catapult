<?php

namespace App\Filament\Resources\ActivityResource\Tables;

use App\Filament\Resources\ActivityResource\Tables\ActivityTableActions;
use App\Models\Activity;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ActivityTable
{
    /**
     * Get table columns for ActivityResource
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
     * Get table filters for ActivityResource
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
     * Get table actions for ActivityResource
     */
    public static function actions(): array
    {
        return [
            ActivityTableActions::getActionGroup(),
        ];
    }

    /**
     * Get bulk actions for ActivityResource
     */
    public static function bulkActions(): array
    {
        return [
            ActivityTableActions::getBulkActionGroup(),
        ];
    }

    /**
     * Get header actions for ActivityResource
     */
    public static function headerActions(): array
    {
        return ActivityTableActions::getHeaderActions();
    }

    /**
     * Created At column with timestamp formatting
     */
    protected static function getCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->label('Time')
            ->dateTime('M j, Y g:i:s A')
            ->sortable()
            ->size('sm')
            ->color('gray');
    }

    /**
     * Causer column with user name and icon
     */
    protected static function getCauserColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('causer.name')
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
     * Log name column with badge styling
     */
    protected static function getLogNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('log_name')
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
    protected static function getEventColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('event')
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
    protected static function getDescriptionColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('description')
            ->label('Description')
            ->searchable()
            ->limit(50)
            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                $state = $column->getState();
                return strlen($state) > 50 ? $state : null;
            });
    }

    /**
     * Subject type column with class basename formatting
     */
    protected static function getSubjectTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('subject_type')
            ->label('Model')
            ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
            ->searchable()
            ->toggleable();
    }

    /**
     * Subject ID column
     */
    protected static function getSubjectIdColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('subject_id')
            ->label('Model ID')
            ->searchable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Properties column with icon indicator
     */
    protected static function getPropertiesColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('properties')
            ->label('Details')
            ->boolean()
            ->trueIcon('heroicon-o-document-text')
            ->falseIcon('heroicon-o-x-circle')
            ->getStateUsing(fn ($record) => !empty($record->properties));
    }

    /**
     * Log name filter
     */
    protected static function getLogNameFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('log_name')
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
    protected static function getEventFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('event')
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
    protected static function getCauserFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('causer_id')
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
    protected static function getDateRangeFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('created_at')
            ->form([
                Forms\Components\DatePicker::make('created_from')
                    ->label('From'),
                Forms\Components\DatePicker::make('created_until')
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
    protected static function getPropertiesFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('has_properties')
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