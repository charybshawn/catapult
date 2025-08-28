<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use App\Models\TimeCardStatus;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use App\Models\TaskType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TagsColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TimeCardResource\Pages\ListTimeCards;
use App\Filament\Resources\TimeCardResource\Pages\CreateTimeCard;
use App\Filament\Resources\TimeCardResource\Pages\EditTimeCard;
use App\Filament\Resources\TimeCardResource\Pages;
use App\Filament\Resources\TimeCardResource\RelationManagers;
use App\Models\TimeCard;
use Filament\Forms;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;

class TimeCardResource extends BaseResource
{
    protected static ?string $model = TimeCard::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Time Cards';
    
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Time Entry Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->label('Employee'),
                                DatePicker::make('work_date')
                                    ->required()
                                    ->default(now())
                                    ->label('Work Date'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('clock_in')
                                    ->required()
                                    ->seconds(false)
                                    ->default(now())
                                    ->label('Clock In Time'),
                                DateTimePicker::make('clock_out')
                                    ->seconds(false)
                                    ->label('Clock Out Time')
                                    ->afterOrEqual('clock_in'),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('time_card_status_id')
                                    ->label('Status')
                                    ->relationship('timeCardStatus', 'name')
                                    ->default(fn () => TimeCardStatus::where('code', 'active')->first()?->id)
                                    ->required(),
                                TextInput::make('duration_minutes')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->label('Duration (minutes)')
                                    ->placeholder('Calculated automatically'),
                            ]),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->label('Notes'),
                        TagsInput::make('taskNames')
                            ->label('Tasks Performed')
                            ->placeholder('Select or type tasks...')
                            ->suggestions(function () {
                                return TaskType::active()
                                    ->orderBy('category')
                                    ->orderBy('sort_order')
                                    ->pluck('name')
                                    ->toArray();
                            })
                            ->columnSpanFull()
                            ->helperText('Tasks completed during this shift')
                            ->dehydrated(false),
                    ]),
                Section::make('Technical Information')
                    ->schema([
                        TextInput::make('ip_address')
                            ->maxLength(255)
                            ->disabled(),
                        TextInput::make('user_agent')
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
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('work_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                TextColumn::make('clock_in')
                    ->dateTime('g:i A')
                    ->sortable()
                    ->label('Clock In'),
                TextColumn::make('clock_out')
                    ->dateTime('g:i A')
                    ->sortable()
                    ->label('Clock Out')
                    ->placeholder('Still working...'),
                TextColumn::make('duration_formatted')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->duration_formatted)
                    ->badge()
                    ->color(fn ($state) => $state === '--:--' ? 'warning' : 'success'),
                TextColumn::make('timeCardStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Active' => 'warning',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('requires_review')
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
                TextColumn::make('max_shift_status')
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
                TagsColumn::make('taskNames')
                    ->label('Tasks')
                    ->getStateUsing(fn (TimeCard $record) => $record->task_names)
                    ->separator(',')
                    ->limitList(3),
                TextColumn::make('notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Employee'),
                SelectFilter::make('time_card_status_id')
                    ->label('Status')
                    ->relationship('timeCardStatus', 'name'),
                TernaryFilter::make('requires_review')
                    ->label('Requires Review')
                    ->trueLabel('Flagged for Review')
                    ->falseLabel('No Review Needed')
                    ->placeholder('All Time Cards'),
                TernaryFilter::make('max_shift_exceeded')
                    ->label('Exceeded 8 Hours')
                    ->trueLabel('Over 8 Hours')
                    ->falseLabel('Under 8 Hours')
                    ->placeholder('All Shifts'),
                Filter::make('work_date')
                    ->schema([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
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
            ->recordActions([
                EditAction::make(),
                Action::make('clock_out')
                    ->label('Clock Out')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (TimeCard $record) => $record->clockOut())
                    ->visible(fn (TimeCard $record) => $record->timeCardStatus?->code === 'active'),
                Action::make('resolve_review')
                    ->label('Resolve Review')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->schema([
                        DateTimePicker::make('actual_clock_out')
                            ->label('Actual Clock Out Time')
                            ->required()
                            ->default(fn (TimeCard $record) => $record->clock_out ?? now()),
                        Textarea::make('resolution_notes')
                            ->label('Resolution Notes')
                            ->required()
                            ->placeholder('Explain the resolution (e.g., "Forgot to clock out at 5 PM")')
                            ->rows(3),
                    ])
                    ->action(function (TimeCard $record, array $data) {
                        // Update the time card with the actual clock out time
                        $record->update([
                            'clock_out' => $data['actual_clock_out'],
                            'time_card_status_id' => TimeCardStatus::where('code', 'completed')->first()?->id,
                        ]);
                        
                        // Resolve the review flag
                        $record->resolveReview(
                            auth()->user()->name,
                            $data['resolution_notes']
                        );
                        
                        Notification::make()
                            ->title('Time Card Resolved')
                            ->body("Time card for {$record->user->name} has been resolved.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (TimeCard $record) => $record->requires_review),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListTimeCards::route('/'),
            'create' => CreateTimeCard::route('/create'),
            'edit' => EditTimeCard::route('/{record}/edit'),
        ];
    }
}
