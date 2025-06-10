<?php

namespace App\Filament\Resources\ScheduledTaskResource\Pages;

use App\Filament\Resources\ScheduledTaskResource;
use App\Models\ScheduledTask;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewScheduledTask extends ViewRecord
{
    protected static string $resource = ScheduledTaskResource::class;

    public function mount(int | string $record): void
    {
        // Find the record from our static data
        $tasks = ScheduledTask::getScheduledTasks();
        $task = $tasks->firstWhere('id', $record);
        
        if (!$task) {
            abort(404);
        }
        
        $this->record = $task;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Command Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('command')
                                    ->label('Command Name')
                                    ->copyable()
                                    ->icon('heroicon-o-command-line'),
                                TextEntry::make('task_type')
                                    ->label('Task Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'Scheduled Task' => 'success',
                                        'Manual Command' => 'warning',
                                        'Queue Worker' => 'info',
                                        'Event Listener' => 'primary',
                                        default => 'gray',
                                    }),
                            ]),
                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Schedule Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('expression')
                                    ->label('Cron Expression')
                                    ->copyable()
                                    ->formatStateUsing(function (string $state): string {
                                        $expressions = [
                                            '* * * * *' => 'Every minute',
                                            '*/15 * * * *' => 'Every 15 minutes',
                                            '0 * * * *' => 'Hourly',
                                            '0 6 * * *' => 'Daily at 6:00 AM',
                                            '0 7 * * *' => 'Daily at 7:00 AM',
                                            '0 8,16 * * *' => 'Twice daily (8 AM, 4 PM)',
                                            '0 5 * * 1' => 'Weekly Monday at 5:00 AM',
                                            'N/A' => 'Manual execution only',
                                        ];
                                        
                                        return $expressions[$state] ?? $state;
                                    }),
                                TextEntry::make('without_overlapping')
                                    ->label('Overlap Protection')
                                    ->formatStateUsing(fn (string $state): string => $state === 'Yes' ? 'Enabled' : 'Disabled')
                                    ->color(fn (string $state): string => $state === 'Yes' ? 'success' : 'gray'),
                                TextEntry::make('timezone')
                                    ->label('Timezone'),
                            ]),
                    ]),
                
                Section::make('Usage Information')
                    ->schema([
                        TextEntry::make('full_command')
                            ->label('Command Line Usage')
                            ->copyable()
                            ->formatStateUsing(fn (?string $state): string => $state ?? 'php artisan ' . $this->record->command)
                            ->hint('Click to copy to clipboard')
                            ->columnSpanFull(),
                        TextEntry::make('command_flags')
                            ->label('Available Flags')
                            ->formatStateUsing(function () {
                                $command = $this->record->command ?? '';
                                return $this->getCommandFlags($command);
                            })
                            ->html()
                            ->columnSpanFull(),
                        TextEntry::make('example_usage')
                            ->label('Example Usage')
                            ->formatStateUsing(function () {
                                $command = $this->record->command ?? '';
                                return $this->getExampleUsage($command);
                            })
                            ->html()
                            ->columnSpanFull(),
                    ]),
                
                Section::make('Log Information')
                    ->schema([
                        TextEntry::make('log_location')
                            ->label('Log File Location')
                            ->formatStateUsing(function (): string {
                                return $this->getLogLocation($this->record->command);
                            })
                            ->copyable()
                            ->visible(fn (): bool => $this->hasLogFile($this->record->command)),
                    ])
                    ->visible(fn (): bool => $this->hasLogFile($this->record->command)),
            ]);
    }

    private function getCommandFlags(string $command): string
    {
        $flags = match($command) {
            'orders:process-recurring' => [
                '--dry-run' => 'Show what would be processed without making changes',
                '--force' => 'Force processing even if already run today',
            ],
            'orders:backfill-billing-periods' => [
                '--dry-run' => 'Show what would be updated without making changes',
            ],
            'orders:backfill-all-recurring-billing-periods' => [
                '--dry-run' => 'Show what would be updated without making changes',
                '--order-type=TYPE' => 'Only process specific order type (b2b_recurring, farmers_market_recurring, etc.)',
                '--start-date=YYYY-MM-DD' => 'Only process orders from this date onwards',
            ],
            'orders:backfill-recurring' => [
                '--dry-run' => 'Show what would be generated without making changes',
                '--order-id=ID' => 'Only process specific order template ID',
                '--from-date=YYYY-MM-DD' => 'Override start date (YYYY-MM-DD)',
                '--to-date=YYYY-MM-DD' => 'End date for backfill (YYYY-MM-DD, defaults to today)',
            ],
            'orders:generate-crops' => [
                '--dry-run' => 'Show what crops would be generated without creating them',
                '--order-id=ID' => 'Generate crops for specific order ID only',
                '--days-ahead=DAYS' => 'Look ahead this many days for orders (default: 7)',
            ],
            'invoices:generate-consolidated' => [
                '--date=YYYY-MM-DD' => 'Date to generate invoices for (YYYY-MM-DD format)',
                '--dry-run' => 'Show what would be generated without actually creating invoices',
            ],
            'recipe:set-germination' => [
                '{recipe_id}' => 'Required: ID of the recipe to update',
                '{days}' => 'Required: Number of germination days (can be decimal)',
            ],
            'app:check-resource-levels' => [
                '--resource=inventory' => 'Check specific resource type',
                '--threshold=50' => 'Set custom alert threshold percentage',
            ],
            'app:check-inventory-levels' => [
                '--threshold=10' => 'Set low stock threshold percentage',
                '--notify' => 'Send notifications for low stock items',
            ],
            default => [],
        };

        if (empty($flags)) {
            return '<div class="text-gray-500 italic">No additional flags available for this command</div>';
        }

        $html = '<div class="space-y-3">';
        foreach ($flags as $flag => $description) {
            $html .= '<div class="flex flex-col sm:flex-row sm:items-start gap-2">';
            $html .= '<code class="bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded text-sm font-mono whitespace-nowrap">' . htmlspecialchars($flag) . '</code>';
            $html .= '<span class="text-sm text-gray-600 dark:text-gray-400">' . htmlspecialchars($description) . '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    private function getExampleUsage(string $command): string
    {
        $examples = match($command) {
            'orders:process-recurring' => [
                'php artisan orders:process-recurring --dry-run',
                'php artisan orders:process-recurring --force',
            ],
            'orders:backfill-billing-periods' => [
                'php artisan orders:backfill-billing-periods --dry-run',
                'php artisan orders:backfill-billing-periods',
            ],
            'orders:backfill-all-recurring-billing-periods' => [
                'php artisan orders:backfill-all-recurring-billing-periods --dry-run',
                'php artisan orders:backfill-all-recurring-billing-periods --order-type=farmers_market_recurring',
                'php artisan orders:backfill-all-recurring-billing-periods --start-date=2024-01-01',
                'php artisan orders:backfill-all-recurring-billing-periods --order-type=csa_recurring --dry-run',
            ],
            'orders:backfill-recurring' => [
                'php artisan orders:backfill-recurring --dry-run',
                'php artisan orders:backfill-recurring --order-id=1',
                'php artisan orders:backfill-recurring --from-date=2024-05-01',
                'php artisan orders:backfill-recurring --from-date=2024-01-01 --to-date=2024-12-31',
            ],
            'orders:generate-crops' => [
                'php artisan orders:generate-crops --dry-run',
                'php artisan orders:generate-crops --order-id=13',
                'php artisan orders:generate-crops --days-ahead=14',
                'php artisan orders:generate-crops --days-ahead=3 --dry-run',
            ],
            'invoices:generate-consolidated' => [
                'php artisan invoices:generate-consolidated --dry-run',
                'php artisan invoices:generate-consolidated --date=2024-01-15',
            ],
            'recipe:set-germination' => [
                'php artisan recipe:set-germination 1 3',
                'php artisan recipe:set-germination 5 2.5',
            ],
            'app:check-resource-levels' => [
                'php artisan app:check-resource-levels',
                'php artisan app:check-resource-levels --resource=inventory',
            ],
            'app:check-inventory-levels' => [
                'php artisan app:check-inventory-levels',
                'php artisan app:check-inventory-levels --threshold=5 --notify',
            ],
            'app:update-crop-time-fields' => [
                'php artisan app:update-crop-time-fields',
            ],
            'app:process-crop-tasks' => [
                'php artisan app:process-crop-tasks',
            ],
            default => ["php artisan {$command}"],
        };

        $html = '<div class="space-y-2">';
        foreach ($examples as $example) {
            $html .= '<code class="block bg-gray-900 dark:bg-gray-800 text-green-400 dark:text-green-300 px-4 py-2 rounded-lg font-mono text-sm overflow-x-auto">' . htmlspecialchars($example) . '</code>';
        }
        $html .= '</div>';

        return $html;
    }

    private function getLogLocation(string $command): string
    {
        return match($command) {
            'app:update-crop-time-fields' => 'storage/logs/crop-time-updates.log',
            'orders:process-recurring' => 'storage/logs/recurring-orders.log',
            'invoices:generate-consolidated' => 'storage/logs/invoice-generation.log',
            default => 'storage/logs/laravel.log',
        };
    }

    private function hasLogFile(string $command): bool
    {
        return in_array($command, [
            'app:update-crop-time-fields',
            'orders:process-recurring',
            'invoices:generate-consolidated',
        ]);
    }
}