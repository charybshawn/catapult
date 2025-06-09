<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CropsRelationManager extends RelationManager
{
    protected static string $relationship = 'crops';

    protected static ?string $recordTitleAttribute = 'tray_number';

    public function form(Form $form): Form
    {
        $order = $this->getOwnerRecord();
        
        return $form
            ->schema([
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->seedEntry->cultivar_name} ({$record->name})")
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('tray_number')
                    ->label('Tray Number')
                    ->required()
                    ->integer(),
                Forms\Components\DateTimePicker::make('planted_at')
                    ->label('Planted At')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('current_stage')
                    ->label('Current Stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ])
                    ->required()
                    ->default('germination'),
                Forms\Components\DateTimePicker::make('stage_updated_at')
                    ->label('Stage Updated At')
                    ->default(now()),
                Forms\Components\Toggle::make('watering_suspended')
                    ->label('Suspend Watering')
                    ->default(false),
                Forms\Components\Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tray_number')
            ->columns([
                Tables\Columns\TextColumn::make('tray_number')
                    ->label('Tray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipe.seedEntry.cultivar_name')
                    ->label('Variety')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'germination' => 'info',
                        'blackout' => 'warning',
                        'light' => 'success',
                        'harvested' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('planted_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expectedHarvestDate')
                    ->label('Expected Harvest')
                    ->date()
                    ->getStateUsing(fn ($record) => $record->expectedHarvestDate()),
                Tables\Columns\TextColumn::make('daysInCurrentStage')
                    ->label('Days in Stage')
                    ->getStateUsing(fn ($record) => $record->daysInCurrentStage()),
                Tables\Columns\IconColumn::make('watering_suspended_at')
                    ->label('Watering Suspended')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->watering_suspended_at !== null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ]),
                Tables\Filters\Filter::make('watering_suspended')
                    ->label('Watering Suspended')
                    ->query(fn (Builder $query) => $query->whereNotNull('watering_suspended_at')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $watering_suspended = $data['watering_suspended'] ?? false;
                        unset($data['watering_suspended']);
                        
                        $data['watering_suspended_at'] = $watering_suspended ? now() : null;
                        $data['order_id'] = $this->getOwnerRecord()->id;
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $watering_suspended = $data['watering_suspended'] ?? false;
                        unset($data['watering_suspended']);
                        
                        $data['watering_suspended_at'] = $watering_suspended ? now() : null;
                        
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('advance_stage')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->action(function ($record): void {
                        $record->advanceStage();
                    })
                    ->visible(fn ($record): bool => $record->current_stage !== 'harvested'),
                Tables\Actions\Action::make('toggle_watering')
                    ->label(fn ($record): string => $record->watering_suspended_at ? 'Resume Watering' : 'Suspend Watering')
                    ->icon(fn ($record): string => $record->watering_suspended_at ? 'heroicon-o-play' : 'heroicon-o-pause')
                    ->action(function ($record): void {
                        if ($record->watering_suspended_at) {
                            $record->resumeWatering();
                        } else {
                            $record->suspendWatering();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->current_stage !== 'harvested') {
                                    $record->advanceStage();
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('suspend_watering_bulk')
                        ->label('Suspend Watering')
                        ->icon('heroicon-o-pause')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->suspendWatering();
                            }
                        }),
                    Tables\Actions\BulkAction::make('resume_watering_bulk')
                        ->label('Resume Watering')
                        ->icon('heroicon-o-play')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->resumeWatering();
                            }
                        }),
                ]),
            ]);
    }
} 