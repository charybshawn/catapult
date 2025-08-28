<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use App\Filament\Resources\ScheduledTaskResource\Pages\ListScheduledTasks;
use App\Filament\Resources\ScheduledTaskResource\Pages\ViewScheduledTask;
use App\Filament\Resources\ScheduledTaskResource\Pages;
use App\Models\ScheduledTask;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledTaskResource extends BaseResource
{
    protected static ?string $model = ScheduledTask::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Scheduled Tasks';
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    protected static ?int $navigationSort = 10;
    
    // Disable create/edit since these are system-managed
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
    

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                TextColumn::make('command')
                    ->label('Command')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('task_type')
                    ->label('Task Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Scheduled Task' => 'success',
                        'Manual Command' => 'warning',
                        'Queue Worker' => 'info',
                        'Event Listener' => 'primary',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('expression')
                    ->label('Schedule')
                    ->formatStateUsing(function (string $state): string {
                        // Convert cron expression to human readable
                        $expressions = [
                            '* * * * *' => 'Every minute',
                            '*/15 * * * *' => 'Every 15 minutes',
                            '0 * * * *' => 'Hourly',
                            '0 6 * * *' => 'Daily at 6:00 AM',
                            '0 7 * * *' => 'Daily at 7:00 AM',
                            '0 8,16 * * *' => 'Twice daily (8 AM, 4 PM)',
                            '0 5 * * 1' => 'Weekly Monday at 5:00 AM',
                            'N/A' => 'Manual execution only',
                        ];

                        return $expressions[$state] ?? $state;
                    }),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                IconColumn::make('without_overlapping')
                    ->label('No Overlap')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->without_overlapping === 'Yes'),
                TextColumn::make('timezone')
                    ->label('Timezone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('command')
                    ->options([
                        'app:check-resource-levels' => 'Resource Levels Check',
                        'app:update-crop-time-fields' => 'Crop Time Updates',
                        'app:process-crop-tasks' => 'Process Crop Tasks',
                        'orders:process-recurring' => 'Recurring Orders',
                        'invoices:generate-consolidated' => 'Invoice Generation',
                        'app:check-inventory-levels' => 'Inventory Check',
                        'orders:backfill-billing-periods' => 'Order Maintenance',
                    ])
                    ->label('Command Type'),
                SelectFilter::make('task_type')
                    ->options([
                        'Scheduled Task' => 'Scheduled Task',
                        'Manual Command' => 'Manual Command',
                        'Queue Worker' => 'Queue Worker',
                        'Event Listener' => 'Event Listener',
                    ])
                    ->label('Task Type')
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->tooltip('View record')
                        ->icon('heroicon-o-eye'),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->toolbarActions([
                // No bulk actions needed
            ])
            ->defaultSort('command')
            ->poll('30s'); // Refresh every 30 seconds
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduledTasks::route('/'),
            'view' => ViewScheduledTask::route('/{record}'),
        ];
    }
}