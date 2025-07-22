<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Actions\Order\AdvanceStageAction;
use App\Actions\Order\ToggleWateringAction;
use App\Actions\Order\ValidateCropDataAction;
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
        return $form
            ->schema([
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => app(ValidateCropDataAction::class)->getRecipeOptionLabel($record))
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('tray_number')
                    ->label('Tray Number')
                    ->required()
                    ->integer(),
                Forms\Components\DateTimePicker::make('planting_at')
                    ->label('Planted At')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('current_stage')
                    ->label('Current Stage')
                    ->options(app(ValidateCropDataAction::class)->getStageOptions())
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
                    ->color(fn (string $state): string => app(ValidateCropDataAction::class)->getStageBadgeColor($state)),
                Tables\Columns\TextColumn::make('planting_at')
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
                    ->options(app(ValidateCropDataAction::class)->getStageOptions()),
                Tables\Filters\Filter::make('watering_suspended')
                    ->label('Watering Suspended')
                    ->query(fn (Builder $query) => $query->whereNotNull('watering_suspended_at')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return app(ValidateCropDataAction::class)->transformForCreate($data, $this->getOwnerRecord()->id);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        return app(ValidateCropDataAction::class)->transformForUpdate($data);
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('advance_stage')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->action(function ($record): void {
                        app(AdvanceStageAction::class)->execute($record);
                    })
                    ->visible(fn ($record): bool => app(AdvanceStageAction::class)->canAdvance($record)),
                Tables\Actions\Action::make('toggle_watering')
                    ->label(fn ($record): string => app(ToggleWateringAction::class)->getToggleLabel($record))
                    ->icon(fn ($record): string => app(ToggleWateringAction::class)->getToggleIcon($record))
                    ->action(function ($record): void {
                        app(ToggleWateringAction::class)->execute($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->action(function ($records) {
                            app(AdvanceStageAction::class)->executeBulk($records);
                        }),
                    Tables\Actions\BulkAction::make('suspend_watering_bulk')
                        ->label('Suspend Watering')
                        ->icon('heroicon-o-pause')
                        ->action(function ($records) {
                            app(ToggleWateringAction::class)->suspendBulk($records);
                        }),
                    Tables\Actions\BulkAction::make('resume_watering_bulk')
                        ->label('Resume Watering')
                        ->icon('heroicon-o-play')
                        ->action(function ($records) {
                            app(ToggleWateringAction::class)->resumeBulk($records);
                        }),
                ]),
            ]);
    }
} 