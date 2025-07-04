<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeCardResource\Pages;
use App\Filament\Resources\TimeCardResource\RelationManagers;
use App\Models\TimeCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class TimeCardResource extends Resource
{
    protected static ?string $model = TimeCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Time Cards';
    
    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Time Entry Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Employee'),
                                Forms\Components\DatePicker::make('work_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Work Date'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('clock_in')
                                    ->required()
                                    ->seconds(false)
                                    ->default(now())
                                    ->label('Clock In Time'),
                                Forms\Components\DateTimePicker::make('clock_out')
                                    ->seconds(false)
                                    ->label('Clock Out Time')
                                    ->afterOrEqual('clock_in'),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('time_card_status_id')
                                    ->label('Status')
                                    ->relationship('timeCardStatus', 'name')
                                    ->default(fn () => \App\Models\TimeCardStatus::where('code', 'active')->first()?->id)
                                    ->required(),
                                Forms\Components\TextInput::make('duration_minutes')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->label('Duration (minutes)')
                                    ->placeholder('Calculated automatically'),
                            ]),
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Notes'),
                        Forms\Components\TagsInput::make('taskNames')
                            ->label('Tasks Performed')
                            ->placeholder('Select or type tasks...')
                            ->suggestions(function () {
                                return \App\Models\TaskType::active()
                                    ->orderBy('category')
                                    ->orderBy('sort_order')
                                    ->pluck('name')
                                    ->toArray();
                            })
                            ->columnSpanFull()
                            ->helperText('Tasks completed during this shift')
                            ->dehydrated(false),
                    ]),
                Forms\Components\Section::make('Technical Information')
                    ->schema([
                        Forms\Components\TextInput::make('ip_address')
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\TextInput::make('user_agent')
                            ->maxLength(255)
                            ->disabled(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                Tables\Columns\TextColumn::make('work_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                Tables\Columns\TextColumn::make('clock_in')
                    ->dateTime('g:i A')
                    ->sortable()
                    ->label('Clock In'),
                Tables\Columns\TextColumn::make('clock_out')
                    ->dateTime('g:i A')
                    ->sortable()
                    ->label('Clock Out')
                    ->placeholder('Still working...'),
                Tables\Columns\TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->duration_formatted)
                    ->badge()
                    ->color(fn ($state) => $state === '--:--' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('timeCardStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Active' => 'warning',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('requires_review')
                    ->boolean()
                    ->label('Needs Review')
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->tooltip(fn (TimeCard $record) => 
                        $record->requires_review ? 
                        'Flagged: ' . implode(', ', $record->flags ?? []) : 
                        ''
                    ),
                Tables\Columns\TextColumn::make('max_shift_status')
                    ->label('Shift Status')
                    ->getStateUsing(function (TimeCard $record): string {
                        if ($record->max_shift_exceeded) {
                            return 'Exceeded 8hrs';
                        }
                        if ($record->timeCardStatus?->code === 'active' && $record->clock_in) {
                            $hours = $record->clock_in->diffInHours(now());
                            if ($hours >= 7) {
                                return 'Near limit (' . round($hours, 1) . 'h)';
                            }
                        }
                        return 'Normal';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Exceeded') => 'danger',
                        str_contains($state, 'Near limit') => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TagsColumn::make('taskNames')
                    ->label('Tasks')
                    ->getStateUsing(fn (TimeCard $record) => $record->task_names)
                    ->separator(',')
                    ->limitList(3),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Employee'),
                Tables\Filters\SelectFilter::make('time_card_status_id')
                    ->label('Status')
                    ->relationship('timeCardStatus', 'name'),
                Tables\Filters\TernaryFilter::make('requires_review')
                    ->label('Requires Review')
                    ->trueLabel('Flagged for Review')
                    ->falseLabel('No Review Needed')
                    ->placeholder('All Time Cards'),
                Tables\Filters\TernaryFilter::make('max_shift_exceeded')
                    ->label('Exceeded 8 Hours')
                    ->trueLabel('Over 8 Hours')
                    ->falseLabel('Under 8 Hours')
                    ->placeholder('All Shifts'),
                Tables\Filters\Filter::make('work_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('work_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('work_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('clock_out')
                    ->label('Clock Out')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (TimeCard $record) => $record->clockOut())
                    ->visible(fn (TimeCard $record) => $record->timeCardStatus?->code === 'active'),
                Tables\Actions\Action::make('resolve_review')
                    ->label('Resolve Review')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DateTimePicker::make('actual_clock_out')
                            ->label('Actual Clock Out Time')
                            ->required()
                            ->default(fn (TimeCard $record) => $record->clock_out ?? now()),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required()
                            ->placeholder('Explain the resolution (e.g., "Forgot to clock out at 5 PM")')
                            ->rows(3),
                    ])
                    ->action(function (TimeCard $record, array $data) {
                        // Update the time card with the actual clock out time
                        $record->update([
                            'clock_out' => $data['actual_clock_out'],
                            'time_card_status_id' => \App\Models\TimeCardStatus::where('code', 'completed')->first()?->id,
                        ]);
                        
                        // Resolve the review flag
                        $record->resolveReview(
                            auth()->user()->name,
                            $data['resolution_notes']
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Time Card Resolved')
                            ->body("Time card for {$record->user->name} has been resolved.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TimeCard $record) => $record->requires_review),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('work_date', 'desc');
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
            'index' => Pages\ListTimeCards::route('/'),
            'create' => Pages\CreateTimeCard::route('/create'),
            'edit' => Pages\EditTimeCard::route('/{record}/edit'),
        ];
    }
}
