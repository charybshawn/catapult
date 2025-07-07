<?php

namespace App\Filament\Traits;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

trait HasTimestamps
{
    /**
     * Get timestamp columns for tables
     */
    public static function getTimestampColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
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
            Forms\Components\Placeholder::make('created_at')
                ->label('Created')
                ->content(fn ($record): string => $record ? $record->created_at->format('M d, Y H:i') : 'Not created yet'),
            Forms\Components\Placeholder::make('updated_at')
                ->label('Last Updated')
                ->content(fn ($record): string => $record ? $record->updated_at->format('M d, Y H:i') : 'Not updated yet'),
        ];
    }
    
    /**
     * Get timestamp section for forms
     */
    public static function getTimestampSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('System Information')
            ->schema(static::getTimestampFields())
            ->columns(2)
            ->collapsed()
            ->hidden(fn ($record): bool => $record === null);
    }
    
    /**
     * Get date range filter for created_at
     */
    public static function getCreatedAtDateRangeFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('created_at')
            ->form([
                Forms\Components\DatePicker::make('created_from')
                    ->label('Created from'),
                Forms\Components\DatePicker::make('created_until')
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
    public static function getUpdatedAtDateRangeFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('updated_at')
            ->form([
                Forms\Components\DatePicker::make('updated_from')
                    ->label('Updated from'),
                Forms\Components\DatePicker::make('updated_until')
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