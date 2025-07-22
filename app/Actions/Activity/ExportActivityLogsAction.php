<?php

namespace App\Actions\Activity;

use Illuminate\Support\Collection;

class ExportActivityLogsAction
{
    /**
     * Export activity logs to CSV format
     */
    public function execute(Collection $records)
    {
        $csv = \League\Csv\Writer::createFromString('');
        
        $csv->insertOne([
            'Date/Time',
            'User',
            'Type',
            'Action',
            'Description',
            'Model',
            'Model ID',
            'Properties',
        ]);
        
        foreach ($records as $record) {
            $csv->insertOne([
                $record->created_at->format('Y-m-d H:i:s'),
                $record->causer?->name ?? 'System',
                $record->log_name ?? 'default',
                $record->event,
                $record->description,
                $record->subject_type ? class_basename($record->subject_type) : '-',
                $record->subject_id ?? '-',
                json_encode($record->properties),
            ]);
        }
        
        return response()->streamDownload(function () use ($csv) {
            echo $csv->toString();
        }, 'activity-logs-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}