<?php

namespace App\Actions\Activity;

use League\Csv\Writer;
use Illuminate\Support\Collection;

/**
 * Exports system activity logs to CSV format for auditing and analysis purposes.
 * 
 * Handles the export of agricultural production system activity logs including
 * crop operations, order processing, inventory transactions, and user actions.
 * Provides comprehensive audit trail functionality for business operations.
 * 
 * @business_domain Agricultural Production Activity Monitoring
 * @audit_trail Comprehensive system activity logging and export functionality
 * @export_format CSV with timestamped filename for audit record keeping
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class ExportActivityLogsAction
{
    /**
     * Export activity logs to CSV format with agricultural business context headers.
     * 
     * Creates a comprehensive CSV export of system activity logs including user actions,
     * crop operations, order processing, inventory transactions, and system events.
     * Each record includes full audit information with timestamps, user attribution,
     * and detailed property changes for regulatory compliance and business analysis.
     * 
     * @business_process Activity Log Export Workflow
     * @agricultural_context Tracks all microgreens production and order management activities
     * 
     * @param Collection $records Collection of activity log records from spatie/laravel-activitylog
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSV download response
     * 
     * @audit_fields Includes: Date/Time, User, Type, Action, Description, Model, Model ID, Properties
     * @filename_pattern activity-logs-{Y-m-d-His}.csv for chronological organization
     * @properties_format JSON encoded for complete change tracking and data preservation
     * 
     * @usage Used by administrators for audit trail exports and compliance reporting
     * @performance Streams output to handle large activity log datasets efficiently
     */
    public function execute(Collection $records)
    {
        $csv = Writer::createFromString('');
        
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