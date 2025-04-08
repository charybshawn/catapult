<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CropResource extends Resource
{
    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grow Trays';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'id')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('tray_number')
                    ->label('Tray Number')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100),
                Forms\Components\DateTimePicker::make('planted_at')
                    ->label('Planted At')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('current_stage')
                    ->label('Current Stage')
                    ->options([
                        'planting' => 'Planting',
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ])
                    ->required()
                    ->default('planting'),
                Forms\Components\DateTimePicker::make('stage_updated_at')
                    ->label('Stage Updated At')
                    ->default(now()),
                Forms\Components\TextInput::make('harvest_weight_grams')
                    ->label('Harvest Weight (grams)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10000)
                    ->visible(fn (Forms\Get $get) => $get('current_stage') === 'harvested'),
                Forms\Components\Toggle::make('watering_suspended')
                    ->label('Suspend Watering')
                    ->default(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, Crop $record) {
                        if ($record && $record->exists) {
                            if ($state) {
                                $record->watering_suspended_at = now();
                            } else {
                                $record->watering_suspended_at = null;
                            }
                        }
                    }),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tray_number')
                    ->label('Tray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipe.seedVariety.name')
                    ->label('Variety')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('current_stage')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planting' => 'gray',
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
                    ->getStateUsing(fn (Crop $record) => $record->expectedHarvestDate()),
                Tables\Columns\TextColumn::make('daysInCurrentStage')
                    ->label('Days in Stage')
                    ->getStateUsing(fn (Crop $record) => $record->daysInCurrentStage()),
                Tables\Columns\IconColumn::make('watering_suspended_at')
                    ->label('Watering Suspended')
                    ->boolean()
                    ->getStateUsing(fn (Crop $record) => $record->watering_suspended_at !== null),
                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('planted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('current_stage')
                    ->options([
                        'planting' => 'Planting',
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ]),
                Tables\Filters\Filter::make('watering_suspended')
                    ->label('Watering Suspended')
                    ->query(fn (Builder $query) => $query->whereNotNull('watering_suspended_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('advance_stage')
                    ->label('Advance Stage')
                    ->icon('heroicon-o-arrow-right')
                    ->action(function (Crop $record): void {
                        $record->advanceStage();
                    })
                    ->visible(fn (Crop $record): bool => $record->current_stage !== 'harvested'),
                Tables\Actions\Action::make('toggle_watering')
                    ->label(fn (Crop $record): string => $record->watering_suspended_at ? 'Resume Watering' : 'Suspend Watering')
                    ->icon(fn (Crop $record): string => $record->watering_suspended_at ? 'heroicon-o-play' : 'heroicon-o-pause')
                    ->action(function (Crop $record): void {
                        if ($record->watering_suspended_at) {
                            $record->resumeWatering();
                        } else {
                            $record->suspendWatering();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
} 