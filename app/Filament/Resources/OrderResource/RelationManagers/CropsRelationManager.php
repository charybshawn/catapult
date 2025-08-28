<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Actions\Order\AdvanceStageAction;
use App\Actions\Order\ToggleWateringAction;
use App\Actions\Order\ValidateCropDataAction;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CropsRelationManager extends RelationManager
{
    protected static string $relationship = 'crops';

    protected static ?string $recordTitleAttribute = 'tray_number';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => app(ValidateCropDataAction::class)->getRecipeOptionLabel($record))
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('tray_number')
                    ->label('Tray Number')
                    ->required()
                    ->integer(),
                DateTimePicker::make('planting_at')
                    ->label('Planted At')
                    ->required()
                    ->default(now()),
                Select::make('current_stage')
                    ->label('Current Stage')
                    ->options(app(ValidateCropDataAction::class)->getStageOptions())
                    ->required()
                    ->default('germination'),
                DateTimePicker::make('stage_updated_at')
                    ->label('Stage Updated At')
                    ->default(now()),
                Toggle::make('watering_suspended')
                    ->label('Suspend Watering')
                    ->default(false),
                Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tray_number')
            ->columns([
                TextColumn::make('tray_number')
                    ->label('Tray')
                    ->sortable(),
                TextColumn::make('recipe.seedEntry.cultivar_name')
                    ->label('Variety')
                    ->searchable(),
                TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_stage')
                    ->badge()
                    ->color(fn (string $state): string => app(ValidateCropDataAction::class)->getStageBadgeColor($state)),
                TextColumn::make('planting_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expectedHarvestDate')
                    ->label('Expected Harvest')
                    ->date()
                    ->getStateUsing(fn ($record) => $record->expectedHarvestDate()),
                TextColumn::make('daysInCurrentStage')
                    ->label('Days in Stage')
                    ->getStateUsing(fn ($record) => $record->daysInCurrentStage()),
                IconColumn::make('watering_suspended_at')
                    ->label('Watering Suspended')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->watering_suspended_at !== null),
            ])
            ->filters([
                SelectFilter::make('current_stage')
                    ->options(app(ValidateCropDataAction::class)->getStageOptions()),
                Filter::make('watering_suspended')
                    ->label('Watering Suspended')
                    ->query(fn (Builder $query) => $query->whereNotNull('watering_suspended_at')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        return app(ValidateCropDataAction::class)->transformForCreate($data, $this->getOwnerRecord()->id);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        return app(ValidateCropDataAction::class)->transformForUpdate($data);
                    }),
                DeleteAction::make(),
                Action::make('advance_stage')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->action(function ($record): void {
                        app(AdvanceStageAction::class)->execute($record);
                    })
                    ->visible(fn ($record): bool => app(AdvanceStageAction::class)->canAdvance($record)),
                Action::make('toggle_watering')
                    ->label(fn ($record): string => app(ToggleWateringAction::class)->getToggleLabel($record))
                    ->icon(fn ($record): string => app(ToggleWateringAction::class)->getToggleIcon($record))
                    ->action(function ($record): void {
                        app(ToggleWateringAction::class)->execute($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->action(function ($records) {
                            app(AdvanceStageAction::class)->executeBulk($records);
                        }),
                    BulkAction::make('suspend_watering_bulk')
                        ->label('Suspend Watering')
                        ->icon('heroicon-o-pause')
                        ->action(function ($records) {
                            app(ToggleWateringAction::class)->suspendBulk($records);
                        }),
                    BulkAction::make('resume_watering_bulk')
                        ->label('Resume Watering')
                        ->icon('heroicon-o-play')
                        ->action(function ($records) {
                            app(ToggleWateringAction::class)->resumeBulk($records);
                        }),
                ]),
            ]);
    }
} 