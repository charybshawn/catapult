<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Recent Activities';
    
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('g:i:s A')
                    ->size('sm')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->default('System')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->causer ? $state : 'System'
                    )
                    ->size('sm'),
                Tables\Columns\TextColumn::make('log_name')
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
                Tables\Columns\TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->size('sm'),
            ])
            ->paginated(false)
            ->striped()
            ->poll('30s');
    }
}