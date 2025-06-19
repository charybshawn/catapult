<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Services\ImportExport\ResourceExportService;
use App\Models\DataExport;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('export')
                ->label('Export Orders')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Forms\Components\Select::make('format')
                        ->label('Export Format')
                        ->options([
                            'json' => 'JSON',
                            'csv' => 'CSV',
                        ])
                        ->default('json')
                        ->required(),
                        
                    Forms\Components\Toggle::make('include_timestamps')
                        ->label('Include Timestamps')
                        ->helperText('Include created_at and updated_at columns')
                        ->default(false),
                        
                    Forms\Components\Section::make('Filters')
                        ->description('Optionally filter orders to export')
                        ->schema([
                            Forms\Components\Select::make('status_filter')
                                ->label('Order Status')
                                ->options([
                                    'all' => 'All Orders',
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'processing' => 'Processing',
                                    'completed' => 'Completed',
                                ])
                                ->default('all'),
                                
                            Forms\Components\DatePicker::make('date_from')
                                ->label('From Date'),
                                
                            Forms\Components\DatePicker::make('date_to')
                                ->label('To Date'),
                        ])
                        ->columns(3),
                ])
                ->action(function (array $data) {
                    try {
                        $exportService = new ResourceExportService();
                        
                        $options = [
                            'format' => $data['format'],
                            'include_timestamps' => $data['include_timestamps'],
                            'where' => [],
                        ];
                        
                        // Add filters
                        if ($data['status_filter'] !== 'all') {
                            $options['where']['orders'][] = 'status:' . $data['status_filter'];
                        }
                        
                        if ($data['date_from']) {
                            $options['where']['orders'][] = '>=created_at:' . $data['date_from'];
                        }
                        
                        if ($data['date_to']) {
                            $options['where']['orders'][] = '<=created_at:' . $data['date_to'];
                        }
                        
                        $zipPath = $exportService->exportResource('orders', $options);
                        
                        // Read manifest
                        $zip = new \ZipArchive();
                        $zip->open($zipPath);
                        $manifestContent = $zip->getFromName('manifest.json');
                        $manifest = json_decode($manifestContent, true);
                        $zip->close();
                        
                        // Save export record
                        $export = DataExport::create([
                            'resource' => 'orders',
                            'filename' => basename($zipPath),
                            'filepath' => $zipPath,
                            'format' => $data['format'],
                            'manifest' => $manifest,
                            'options' => $options,
                            'file_size' => filesize($zipPath),
                            'record_count' => array_sum($manifest['statistics'] ?? []),
                            'user_id' => auth()->id(),
                        ]);
                        
                        Notification::make()
                            ->title('Export Successful')
                            ->body("Exported " . number_format($export->total_records) . " records across " . count($manifest['tables']) . " tables")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('download')
                                    ->label('Download')
                                    ->url(route('filament.admin.data-export.download', $export))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Export Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
} 