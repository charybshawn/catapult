<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListHarvests extends ListRecords
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bulk_harvest')
                ->label('Add Harvest')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    Section::make('Bulk Harvest Entry')
                        ->description('Enter harvest data for multiple trays of the same variety')
                        ->schema([
                            Forms\Components\Select::make('master_cultivar_id')
                                ->label('Crop Variety')
                                ->options(function () {
                                    return MasterCultivar::with('masterSeedCatalog')
                                        ->where('is_active', true)
                                        ->whereHas('masterSeedCatalog', function ($query) {
                                            $query->where('is_active', true);
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($cultivar) {
                                            return [$cultivar->id => $cultivar->full_name];
                                        });
                                })
                                ->required()
                                ->searchable()
                                ->reactive(),
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('total_weight_grams')
                                        ->label('Total Weight (grams)')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $trayCount = $get('tray_count');
                                            if ($state && $trayCount) {
                                                $set('average_per_tray', round($state / $trayCount, 2));
                                            }
                                        }),
                                    Forms\Components\TextInput::make('tray_count')
                                        ->label('Number of Trays Harvested')
                                        ->required()
                                        ->rules(['numeric', 'min:0.1'])
                                        ->placeholder('e.g. 1.5, 0.25, 2.75')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $totalWeight = $get('total_weight_grams');
                                            if ($state && $totalWeight) {
                                                $set('average_per_tray', round($totalWeight / $state, 2));
                                            }
                                        }),
                                ]),
                            Forms\Components\Placeholder::make('average_per_tray')
                                ->label('Average Weight per Tray')
                                ->content(fn ($get) => ($get('average_per_tray') ?? 0).' g'),
                            Forms\Components\DatePicker::make('harvest_date')
                                ->label('Harvest Date')
                                ->required()
                                ->default(now())
                                ->maxDate(now()),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->modalWidth('lg')
                ->action(function (array $data): void {
                    // Create the harvest record
                    $harvest = Harvest::create([
                        'master_cultivar_id' => $data['master_cultivar_id'],
                        'user_id' => auth()->id(),
                        'total_weight_grams' => $data['total_weight_grams'],
                        'tray_count' => $data['tray_count'],
                        'harvest_date' => $data['harvest_date'],
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Harvest Recorded')
                        ->success()
                        ->body("Harvested {$data['tray_count']} trays with total weight {$data['total_weight_grams']}g")
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HarvestResource\Widgets\WeeklyHarvestStats::class,
        ];
    }
}
