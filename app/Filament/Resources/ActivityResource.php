<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    // Make visible in navigation
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Activity Logs';
    
    protected static ?string $pluralModelLabel = 'Activity Logs';
    
    protected static ?string $modelLabel = 'Activity Log';
    
    public static function getNavigationBadge(): ?string
    {
        return cache()->remember('activity_log_count', 60, function () {
            return number_format(static::getModel()::count());
        });
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_name')
                    ->label('Log Name')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->disabled(),
                Forms\Components\TextInput::make('event')
                    ->label('Event')
                    ->disabled(),
                Forms\Components\TextInput::make('subject_type')
                    ->label('Subject Type')
                    ->disabled(),
                Forms\Components\TextInput::make('subject_id')
                    ->label('Subject ID')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_type')
                    ->label('Causer Type')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_id')
                    ->label('Causer ID')
                    ->disabled(),
                Forms\Components\KeyValue::make('properties')
                    ->label('Properties')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M j, Y g:i:s A')
                    ->sortable()
                    ->size('sm')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->default('System')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->causer ? $state : 'System'
                    )
                    ->icon(fn ($record) => 
                        $record->causer_type === 'App\Models\User' ? 'heroicon-m-user' : 'heroicon-m-cog'
                    ),
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
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
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
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Model ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('properties')
                    ->label('Details')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-circle')
                    ->getStateUsing(fn ($record) => !empty($record->properties)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Type')
                    ->multiple()
                    ->options(function () {
                        return Activity::distinct()
                            ->pluck('log_name')
                            ->mapWithKeys(fn ($name) => [$name => ucfirst($name ?? 'default')])
                            ->toArray();
                    })
                    ->indicator('Type'),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Action')
                    ->multiple()
                    ->options(function () {
                        return Activity::distinct()
                            ->pluck('event')
                            ->mapWithKeys(fn ($event) => [$event => ucfirst($event)])
                            ->toArray();
                    })
                    ->indicator('Action'),
                Tables\Filters\SelectFilter::make('causer_id')
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
                    ->indicator('User'),
                Tables\Filters\Filter::make('created_at')
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
                    }),
                Tables\Filters\TernaryFilter::make('has_properties')
                    ->label('Has Details')
                    ->placeholder('All activities')
                    ->trueLabel('With details')
                    ->falseLabel('Without details')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('properties')->where('properties', '!=', '[]'),
                        false: fn (Builder $query) => $query->whereNull('properties')->orWhere('properties', '[]'),
                    ),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View activity details'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $csv = \League\Csv\Writer::createFromString('');
                            
                            $csv->insertOne([
                                'Date/Time',
                                'User',
                                'Type',
                                'Action',
                                'Description',
                                'Model',
                                'Model ID',
                                'Properties',
                            ]);
                            
                            foreach ($records as $record) {
                                $csv->insertOne([
                                    $record->created_at->format('Y-m-d H:i:s'),
                                    $record->causer?->name ?? 'System',
                                    $record->log_name ?? 'default',
                                    $record->event,
                                    $record->description,
                                    $record->subject_type ? class_basename($record->subject_type) : '-',
                                    $record->subject_id ?? '-',
                                    json_encode($record->properties),
                                ]);
                            }
                            
                            return response()->streamDownload(function () use ($csv) {
                                echo $csv->toString();
                            }, 'activity-logs-' . now()->format('Y-m-d-His') . '.csv', [
                                'Content-Type' => 'text/csv',
                            ]);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('stats')
                    ->label('View Statistics')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn () => static::getUrl('stats'))
                    ->color('gray'),
                Tables\Actions\Action::make('timeline')
                    ->label('Timeline View')
                    ->icon('heroicon-o-clock')
                    ->url(fn () => static::getUrl('timeline'))
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
            'stats' => Pages\ActivityStatistics::route('/statistics'),
            'timeline' => Pages\ActivityTimeline::route('/timeline'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
} 