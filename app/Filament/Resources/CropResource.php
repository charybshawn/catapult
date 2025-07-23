<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Forms\CropForm;
use App\Filament\Resources\CropResource\Pages;
use App\Filament\Resources\CropResource\Tables\CropTable;
use App\Models\Crop;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Table;

class CropResource extends BaseResource
{
    protected static ?string $model = \App\Models\Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = 'Individual Crops';

    protected static ?string $navigationGroup = 'Production';

    protected static ?int $navigationSort = 99;
    
    // Hide from navigation - access through batch view
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema(CropForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => CropTable::modifyQuery($query))
            ->columns(CropTable::columns())
            ->filters(CropTable::filters())
            ->actions(CropTable::actions())
            ->bulkActions(CropTable::bulkActions())
            ->groups(CropTable::groups())
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Crop Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('recipe.name')
                            ->label('Recipe'),
                        Infolists\Components\TextEntry::make('currentStage.name')
                            ->label('Current Stage')
                            ->badge(),
                        Infolists\Components\TextEntry::make('tray_number')
                            ->label('Tray Number'),
                        Infolists\Components\TextEntry::make('tray_count')
                            ->label('Tray Count'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('soaking_at')
                            ->label('Soaking Started')
                            ->dateTime()
                            ->visible(fn ($record) => $record->requires_soaking),
                        Infolists\Components\TextEntry::make('germination_at')
                            ->label('Germination')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('blackout_at')
                            ->label('Blackout')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('light_at')
                            ->label('Light')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('harvested_at')
                            ->label('Harvested')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Progress')
                    ->schema([
                        Infolists\Components\TextEntry::make('time_to_next_stage_display')
                            ->label('Time to Next Stage'),
                        Infolists\Components\TextEntry::make('stage_age_display')
                            ->label('Time in Current Stage'),
                        Infolists\Components\TextEntry::make('total_age_display')
                            ->label('Total Age'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'view' => Pages\ViewCrop::route('/{record}'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
}
