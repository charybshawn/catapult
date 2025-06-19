<?php

namespace App\Filament\Resources\DataExportResource\Pages;

use App\Filament\Resources\DataExportResource;
use App\Services\ImportExport\ResourceExportService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDataExport extends CreateRecord
{
    protected static string $resource = DataExportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $exportService = new ResourceExportService();
            
            // Perform the export
            $options = [
                'format' => $data['format'],
                'include_timestamps' => $data['include_timestamps'] ?? false,
                'where' => [],
            ];
            
            // Process filters based on resource type
            if (!empty($data['filters'])) {
                $this->processFilters($data['resource'], $data['filters'], $options);
            }
            
            $zipPath = $exportService->exportResource($data['resource'], $options);
            
            // Read the manifest from the ZIP
            $zip = new \ZipArchive();
            $zip->open($zipPath);
            $manifestContent = $zip->getFromName('manifest.json');
            $manifest = json_decode($manifestContent, true);
            $zip->close();
            
            // Update data with export details
            $data['filename'] = basename($zipPath);
            $data['filepath'] = $zipPath;
            $data['manifest'] = $manifest;
            $data['options'] = $options;
            $data['file_size'] = filesize($zipPath);
            $data['record_count'] = array_sum($manifest['statistics'] ?? []);
            $data['user_id'] = auth()->id();
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Failed to export data: ' . $e->getMessage())
                ->danger()
                ->send();
                
            $this->halt();
        }
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Export completed successfully';
    }
    
    protected function processFilters(string $resource, array $filters, array &$options): void
    {
        $primaryTable = match($resource) {
            'orders' => 'orders',
            'products' => 'products',
            'users' => 'users',
            'recipes' => 'recipes',
            'consumables' => 'consumables',
            'invoices' => 'invoices',
            'harvests' => 'harvests',
            'suppliers' => 'suppliers',
            'master_seed_catalog' => 'master_seed_catalog',
            default => $resource,
        };
        
        switch ($resource) {
            case 'orders':
                if (!empty($filters['status'])) {
                    $options['where'][$primaryTable][] = 'status:' . $filters['status'];
                }
                if (!empty($filters['date_from'])) {
                    $options['where'][$primaryTable][] = '>=created_at:' . $filters['date_from'];
                }
                if (!empty($filters['date_to'])) {
                    $options['where'][$primaryTable][] = '<=created_at:' . $filters['date_to'];
                }
                if (!empty($filters['customer_type'])) {
                    $options['where'][$primaryTable][] = 'customer_type:' . $filters['customer_type'];
                }
                break;
                
            case 'products':
                if ($filters['active_only'] ?? false) {
                    $options['where'][$primaryTable][] = 'active:1';
                }
                if (!empty($filters['category_id'])) {
                    $options['where'][$primaryTable][] = 'category_id:' . $filters['category_id'];
                }
                if ($filters['in_stock_only'] ?? false) {
                    $options['where'][$primaryTable][] = '>available_stock:0';
                }
                break;
                
            case 'users':
                if (!empty($filters['customer_type'])) {
                    $options['where'][$primaryTable][] = 'customer_type:' . $filters['customer_type'];
                }
                if ($filters['with_orders'] ?? false) {
                    // This would need special handling - get user IDs that have orders
                    $userIds = \App\Models\Order::distinct()->pluck('user_id')->toArray();
                    if (!empty($userIds)) {
                        $options['where'][$primaryTable][] = 'id:' . implode(',', $userIds);
                    }
                }
                break;
                
            case 'recipes':
                if ($filters['active_only'] ?? false) {
                    $options['where'][$primaryTable][] = 'is_active:1';
                }
                if (!empty($filters['common_name'])) {
                    $options['where'][$primaryTable][] = 'common_name:' . $filters['common_name'];
                }
                break;
                
            case 'consumables':
                if (!empty($filters['type'])) {
                    $options['where'][$primaryTable][] = 'type:' . $filters['type'];
                }
                if ($filters['needs_restock'] ?? false) {
                    // This would need special handling based on current_stock vs restock_threshold
                    // For now, we'll export all and let the user filter in Excel/spreadsheet
                }
                break;
        }
    }
}
