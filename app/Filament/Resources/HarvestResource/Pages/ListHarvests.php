<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use App\Filament\Resources\HarvestResource\Widgets\WeeklyVarietyComparison;
use App\Filament\Resources\HarvestResource\Widgets\HarvestTrendsChart;
use App\Filament\Resources\HarvestResource\Widgets\HarvestTotalsStats;
use App\Filament\Resources\HarvestResource;
use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament page for listing and managing simplified agricultural harvest records.
 *
 * Provides comprehensive harvest listing with embedded creation modal featuring
 * streamlined cultivar-based harvest entry. Includes harvest analytics widgets
 * and supports inline multi-cultivar harvest creation with simplified workflow
 * eliminating complex tray relationships.
 *
 * @filament_page
 * @business_domain Agricultural harvest listing and simplified creation operations
 * @related_models Harvest, MasterCultivar, MasterSeedCatalog
 * @workflow_support Harvest listing, inline creation, analytics dashboard
 * @widget_integration HarvestTotalsStats, WeeklyVarietyComparison, HarvestTrendsChart
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class ListHarvests extends ListRecords
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Harvest')
                ->icon('heroicon-o-plus')
                ->modal()
                ->modalWidth('2xl')
                ->schema([
                    Section::make('Harvest Details')
                        ->schema([
                            DatePicker::make('harvest_date')
                                ->label('Harvest Date')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->reactive(),
                            Hidden::make('user_id')
                                ->default(auth()->id()),
                        ])
                        ->columns(1),
                    Section::make('Cultivar Harvests')
                        ->schema([
                            Repeater::make('cultivar_harvests')
                                ->label('Select Cultivars to Harvest')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('master_cultivar_id')
                                                ->label('Cultivar')
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
                                            TextInput::make('total_weight_grams')
                                                ->label('Weight (g)')
                                                ->required()
                                                ->numeric()
                                                ->minValue(0)
                                                ->step(0.01),
                                        ]),
                                ])
                                ->addActionLabel('Add Another Cultivar')
                                ->collapsible()
                                ->itemLabel(function (array $state): ?string {
                                    if (!$state['master_cultivar_id']) {
                                        return 'New Cultivar';
                                    }
                                    
                                    $cultivar = MasterCultivar::find($state['master_cultivar_id']);
                                    if (!$cultivar) {
                                        return 'Unknown Cultivar';
                                    }
                                    
                                    $weight = $state['total_weight_grams'] ?? 0;
                                    
                                    return "{$cultivar->full_name} - {$weight}g";
                                }),
                            Textarea::make('notes')
                                ->label('General Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ])
                ->using(function (array $data): Model {
                    $cultivarHarvests = $data['cultivar_harvests'] ?? [];
                    $lastHarvest = null;
                    
                    // Create a harvest record for each cultivar-weight pair
                    foreach ($cultivarHarvests as $cultivarHarvest) {
                        $lastHarvest = Harvest::create([
                            'master_cultivar_id' => $cultivarHarvest['master_cultivar_id'],
                            'harvest_date' => $data['harvest_date'],
                            'user_id' => $data['user_id'],
                            'total_weight_grams' => $cultivarHarvest['total_weight_grams'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }
                    
                    return $lastHarvest;
                })
                ->successNotificationTitle('Harvest(s) recorded successfully'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HarvestTrendsChart::class,
            HarvestTotalsStats::class,
            WeeklyVarietyComparison::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
