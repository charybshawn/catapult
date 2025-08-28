<?php

namespace App\Console\Commands;

use Exception;
use App\Models\SeedScrapeUpload;
use App\Services\SeedScrapeImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TestSeedJsonStructure extends Command
{
    protected $signature = 'test:seed-json {file? : Path to JSON file}';
    protected $description = 'Test and diagnose seed JSON file structure';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!$filePath) {
            // Look for JSON files in local storage
            $files = Storage::disk('local')->files();
            $jsonFiles = array_filter($files, function ($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'json';
            });
            
            if (empty($jsonFiles)) {
                $this->error('No JSON files found in storage/app directory.');
                return Command::FAILURE;
            }
            
            $filePath = $this->choice(
                'Select a JSON file to analyze:',
                $jsonFiles
            );
            
            $filePath = storage_path('app/' . $filePath);
        } else {
            if (!file_exists($filePath)) {
                $this->error("File not found: $filePath");
                return Command::FAILURE;
            }
        }
        
        $this->info("Analyzing file: $filePath");
        
        try {
            $jsonData = json_decode(file_get_contents($filePath), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON: ' . json_last_error_msg());
                return Command::FAILURE;
            }
            
            $this->info('JSON is valid');
            $this->info('Analyzing structure...');
            
            // Check top-level structure
            $this->info('Top level keys: ' . implode(', ', array_keys($jsonData)));
            
            if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
                $this->error('Missing required "data" array!');
                return Command::FAILURE;
            }
            
            $this->info('Products count: ' . count($jsonData['data']));
            
            // Check the first product
            if (count($jsonData['data']) > 0) {
                $firstProduct = $jsonData['data'][0];
                $this->info('First product keys: ' . implode(', ', array_keys($firstProduct)));
                
                // Check for variants
                if (!isset($firstProduct['variants']) || !is_array($firstProduct['variants'])) {
                    $this->warn('No "variants" array in first product!');
                } else {
                    $this->info('Variants count: ' . count($firstProduct['variants']));
                    
                    // Check the first variant
                    if (count($firstProduct['variants']) > 0) {
                        $firstVariant = $firstProduct['variants'][0];
                        $this->info('First variant keys: ' . implode(', ', array_keys($firstVariant)));
                        
                        // Check specific fields
                        $this->checkVariantField($firstVariant, 'variant_title');
                        $this->checkVariantField($firstVariant, 'price');
                        $this->checkVariantField($firstVariant, 'currency');
                        $this->checkVariantField($firstVariant, 'is_variant_in_stock');
                    }
                }
            }
            
            // Ask if user wants to simulate import
            if ($this->confirm('Do you want to simulate import with this file?')) {
                $this->simulateImport($filePath);
            }
            
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Error analyzing file: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    private function checkVariantField(array $variant, string $field)
    {
        // Define field aliases - some suppliers use different field names
        $fieldAliases = [
            'variant_title' => ['title', 'variant_title', 'size', 'size_description'],
            'price' => ['price', 'amount', 'cost'],
            'currency' => ['currency', 'currency_code'],
            'is_variant_in_stock' => ['is_variant_in_stock', 'is_in_stock', 'in_stock', 'available']
        ];
        
        // Get the possible field names for this field
        $possibleFields = $fieldAliases[$field] ?? [$field];
        
        // Check if any of the possible field names exist
        $foundField = null;
        $foundValue = null;
        foreach ($possibleFields as $possibleField) {
            if (isset($variant[$possibleField])) {
                $foundField = $possibleField;
                $foundValue = $variant[$possibleField];
                break;
            }
        }
        
        if ($foundField === null) {
            $this->warn("Missing field '$field' in variant (checked aliases: " . implode(', ', $possibleFields) . ")");
        } else {
            $value = $foundValue;
            $type = gettype($value);
            $this->info("Field '$field' found as '$foundField' with value: " . json_encode($value) . " (type: $type)");
        }
    }
    
    private function simulateImport(string $filePath)
    {
        $this->info('Creating test upload record...');
        
        $upload = SeedScrapeUpload::create([
            'filename' => basename($filePath),
            'status' => SeedScrapeUpload::STATUS_PENDING,
            'uploaded_at' => now(),
        ]);
        
        $this->info('Created upload record with ID: ' . $upload->id);
        
        try {
            $importer = new SeedScrapeImporter();
            $importer->import($filePath, $upload);
            
            $upload->refresh();
            
            $this->info('Import simulation completed');
            $this->info('Status: ' . $upload->status);
            $this->info('Notes: ' . $upload->notes);
        } catch (Exception $e) {
            $this->error('Import simulation failed: ' . $e->getMessage());
        }
    }
} 