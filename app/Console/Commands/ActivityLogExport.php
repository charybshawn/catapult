<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Activity;
use Carbon\Carbon;
use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;

class ActivityLogExport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activitylog:export 
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--user= : Filter by user ID}
                            {--type= : Filter by log type}
                            {--format=csv : Export format (csv, json)}
                            {--output= : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export activity logs to CSV or JSON format';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from'))->startOfDay() : null;
        $to = $this->option('to') ? Carbon::parse($this->option('to'))->endOfDay() : null;
        $userId = $this->option('user');
        $type = $this->option('type');
        $format = $this->option('format');
        $output = $this->option('output');

        // Build query
        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->orderBy('created_at', 'desc');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        if ($userId) {
            $query->where('causer_id', $userId);
        }

        if ($type) {
            $query->where('log_name', $type);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->warn('No activity logs found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} activity logs to export.");

        // Generate filename if not provided
        if (!$output) {
            $timestamp = now()->format('Y-m-d_His');
            $output = "activity_logs_{$timestamp}.{$format}";
        }

        $this->info("Exporting to: {$output}");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        if ($format === 'csv') {
            $this->exportToCsv($query, $output, $progressBar);
        } else {
            $this->exportToJson($query, $output, $progressBar);
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Export completed successfully!");
        $this->info("File saved to: " . Storage::path($output));

        return Command::SUCCESS;
    }

    /**
     * Export data to CSV format
     */
    protected function exportToCsv($query, string $filename, $progressBar): void
    {
        $csv = Writer::createFromPath(Storage::path($filename), 'w+');
        
        // Add headers
        $csv->insertOne([
            'ID',
            'Date/Time',
            'User',
            'User Email',
            'Action',
            'Description',
            'Subject Type',
            'Subject ID',
            'IP Address',
            'User Agent',
            'Properties',
        ]);

        // Export in chunks
        $query->chunk(1000, function ($activities) use ($csv, $progressBar) {
            $records = [];
            
            foreach ($activities as $activity) {
                $records[] = [
                    $activity->id,
                    $activity->created_at->format('Y-m-d H:i:s'),
                    $activity->causer?->name ?? 'System',
                    $activity->causer?->email ?? '-',
                    $activity->event ?? '-',
                    $activity->description,
                    $activity->subject_type ?? '-',
                    $activity->subject_id ?? '-',
                    $activity->properties['ip'] ?? '-',
                    $activity->properties['user_agent'] ?? '-',
                    json_encode($activity->properties),
                ];
                
                $progressBar->advance();
            }
            
            $csv->insertAll($records);
        });
    }

    /**
     * Export data to JSON format
     */
    protected function exportToJson($query, string $filename, $progressBar): void
    {
        $file = fopen(Storage::path($filename), 'w');
        fwrite($file, '[');
        
        $first = true;
        
        $query->chunk(1000, function ($activities) use ($file, &$first, $progressBar) {
            foreach ($activities as $activity) {
                if (!$first) {
                    fwrite($file, ',');
                }
                $first = false;
                
                $data = [
                    'id' => $activity->id,
                    'date_time' => $activity->created_at->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $activity->causer_id,
                        'name' => $activity->causer?->name ?? 'System',
                        'email' => $activity->causer?->email ?? null,
                    ],
                    'action' => $activity->event,
                    'description' => $activity->description,
                    'subject' => [
                        'type' => $activity->subject_type,
                        'id' => $activity->subject_id,
                    ],
                    'properties' => $activity->properties,
                ];
                
                fwrite($file, json_encode($data));
                $progressBar->advance();
            }
        });
        
        fwrite($file, ']');
        fclose($file);
    }
}