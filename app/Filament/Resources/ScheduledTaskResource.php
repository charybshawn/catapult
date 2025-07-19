<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduledTaskResource\Pages;
use App\Models\ScheduledTask;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduledTaskResource extends BaseResource
{
    protected static ?string $model = ScheduledTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Scheduled Tasks';
    protected static ?string $navigationGroup = 'System';
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
                Tables\Columns\TextColumn::make('command')
                    ->label('Command')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('task_type')
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
                Tables\Columns\TextColumn::make('expression')
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
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                Tables\Columns\IconColumn::make('without_overlapping')
                    ->label('No Overlap')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->without_overlapping === 'Yes'),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Timezone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('command')
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
                Tables\Filters\SelectFilter::make('task_type')
                    ->options([
                        'Scheduled Task' => 'Scheduled Task',
                        'Manual Command' => 'Manual Command',
                        'Queue Worker' => 'Queue Worker',
                        'Event Listener' => 'Event Listener',
                    ])
                    ->label('Task Type')
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
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
            ->bulkActions([
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
            'index' => Pages\ListScheduledTasks::route('/'),
            'view' => Pages\ViewScheduledTask::route('/{record}'),
        ];
    }
}