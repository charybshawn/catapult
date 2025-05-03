<?php

namespace App\Filament\Resources\PlantingScheduleResource\Pages;

use App\Filament\Resources\PlantingScheduleResource;
use App\Models\PlantingSchedule;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListPlantingSchedules extends ListRecords
{
    protected static string $resource = PlantingScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync_from_recurring_orders')
                ->label('Sync from Recurring Orders')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->required()
                        ->default(fn () => now()),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('End Date')
                        ->required()
                        ->default(fn () => now()->addMonths(3)),
                ])
                ->action(function (array $data): void {
                    $startDate = Carbon::parse($data['start_date']);
                    $endDate = Carbon::parse($data['end_date']);
                    
                    $count = PlantingSchedule::syncFromRecurringOrders($startDate, $endDate);
                    
                    if ($count > 0) {
                        Notification::make()
                            ->title('Planting Schedules Synced')
                            ->body("Created {$count} new planting schedules from recurring orders.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No New Schedules')
                            ->body('No new planting schedules were created.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
} 