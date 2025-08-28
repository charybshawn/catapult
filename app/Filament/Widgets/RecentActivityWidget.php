<?php

namespace App\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use App\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Agricultural operations activity monitoring widget for system oversight.
 *
 * Displays real-time activity feed showing recent system interactions including
 * user actions, API requests, background jobs, authentication events, and
 * operational activities. Provides essential operational visibility for
 * monitoring agricultural production system health and user engagement.
 *
 * @filament_widget Table widget for recent activity monitoring
 * @business_domain Agricultural system activity tracking and user oversight
 * @monitoring_context Real-time feed of system events and user interactions
 * @dashboard_position Full width, updates every 30 seconds for current awareness
 * @operational_visibility Tracks auth, errors, jobs, queries, timecards, and API calls
 */
class RecentActivityWidget extends BaseWidget
{
    /** @var int Widget sort order for dashboard positioning */
    protected static ?int $sort = 2;
    
    /** @var string Widget column span for full-width activity display */
    protected int | string | array $columnSpan = 'full';
    
    /** @var string Widget heading for activity monitoring section */
    protected static ?string $heading = 'Recent Activities';
    
    /**
     * Configure activity monitoring table for agricultural operations oversight.
     *
     * Sets up real-time activity feed with user attribution, activity types,
     * and detailed descriptions. Includes color-coded badges for different
     * activity categories (auth, errors, API, jobs) to enable quick
     * identification of system events and operational patterns.
     *
     * @param Table $table Filament table instance for configuration
     * @return Table Configured table with activity columns and styling
     * @business_logic Shows last 10 activities with user context and timestamps
     * @monitoring_features Color-coded badges for activity type identification
     * @real_time_updates 30-second polling for current operational awareness
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['causer', 'subject'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('g:i:s A')
                    ->size('sm')
                    ->color('gray'),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->causer ? $state : 'System'
                    )
                    ->size('sm'),
                TextColumn::make('log_name')
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
                    }),
                TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->size('sm'),
            ])
            ->paginated(false)
            ->striped()
            ->poll('30s');
    }
}