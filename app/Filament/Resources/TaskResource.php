<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
use App\Filament\Resources\TaskResource\RelationManagers;
use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Production Management';
    
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return true; // We'll re-enable it now that the migration is fixed
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('crop_id')
                    ->relationship('crop', 'tray_number')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                    
                Forms\Components\DateTimePicker::make('due_date'),
                
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                    
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->default('medium')
                    ->required(),
                    
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),
                    
                Forms\Components\DateTimePicker::make('completed_at')
                    ->label('Completed At')
                    ->hidden(fn ($record) => $record && $record->status !== 'completed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('crop.tray_number')
                    ->label('Tray')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'blue',
                        'completed' => 'green',
                        'cancelled' => 'red',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'medium' => 'blue',
                        'high' => 'orange',
                        'urgent' => 'red',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                    
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                    
                Tables\Filters\Filter::make('due')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<=', now())),
                    
                Tables\Filters\Filter::make('completed')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('completed_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->tooltip('Mark task as completed')
                    ->action(function (Task $record) {
                        $record->markAsCompleted();
                    })
                    ->visible(fn (Task $record) => !$record->isCompleted()),
                    
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit task'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete task'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('complete')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markAsCompleted();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
