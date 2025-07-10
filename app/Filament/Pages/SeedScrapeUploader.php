<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessSeedScrapeUpload;
use App\Models\SeedScrapeUpload;
use App\Models\Supplier;
use App\Models\SupplierSourceMapping;
use App\Services\SeedScrapeImporter;
use App\Services\SupplierMatchingService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;

class SeedScrapeUploader extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    
    protected static string $view = 'filament.pages.seed-scrape-uploader';
    
    protected static ?string $title = 'Upload Seed Data';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 6;
    
    // Hide from navigation since it's handled by our JSON navigation
    protected static bool $shouldRegisterNavigation = false;
    
    public $jsonFiles = [];
    public $selectedSupplier = null;
    public $showSupplierSelection = false;
    public $pendingFiles = [];
    public $currentSourceUrl = null;
    public $supplierMatches = [];
    
    // Refresh interval in seconds
    public $refreshInterval = 10;
    
    // Upload processing modal state
    public $uploadOutput = '';
    public $uploadRunning = false;
    public $uploadSuccess = false;
    public $showUploadModal = false;
    
    public function mount(): void
    {
        Log::info('SeedScrapeUploader: Page mounted', [
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload Seed Data')
                    ->description('Upload JSON files scraped from seed supplier websites. The system will help you match suppliers intelligently.')
                    ->schema([
                        FileUpload::make('jsonFiles')
                            ->label('JSON Files')
                            ->multiple()
                            ->acceptedFileTypes(['application/json'])
                            ->maxSize(10240) // 10MB
                            ->disk('local')
                            ->afterStateUpdated(function ($state, callable $set) {
                                Log::info('SeedScrapeUploader: File upload state updated', [
                                    'file_count' => is_array($state) ? count($state) : 1,
                                    'timestamp' => now()->toISOString()
                                ]);
                                $this->handleFileUpload($state, $set);
                            })
                            ->uploadProgressIndicatorPosition('left')
                            ->helperText('Upload JSON files. You will be prompted to select suppliers for new sources.'),
                    ]),
                
                Section::make('Supplier Selection')
                    ->description('Select the correct supplier for the uploaded data. This mapping will be remembered for future uploads.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('source_info')
                                    ->label('Source URL')
                                    ->content(fn (): string => $this->currentSourceUrl ?: 'No file uploaded yet'),
                                    
                                Placeholder::make('match_count')
                                    ->label('Potential Matches Found')
                                    ->content(fn (): string => count($this->supplierMatches) . ' suppliers'),
                            ]),
                            
                        Select::make('selectedSupplier')
                            ->label('Select Supplier')
                            ->options(function () {
                                $options = [];
                                
                                Log::debug('SeedScrapeUploader: Building supplier options list', [
                                    'match_count' => count($this->supplierMatches),
                                    'source_url' => $this->currentSourceUrl
                                ]);
                                
                                // Add potential matches with confidence scores
                                foreach ($this->supplierMatches as $match) {
                                    $confidence = round($match['confidence'] * 100);
                                    $reasons = implode(', ', $match['match_reasons']);
                                    $options[$match['supplier']->id] = 
                                        "{$match['supplier']->name} ({$confidence}% match - {$reasons})";
                                }
                                
                                // Add all other active suppliers
                                $matchedSupplierIds = collect($this->supplierMatches)->pluck('supplier.id')->toArray();
                                $otherSuppliers = Supplier::with('supplierType')
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $matchedSupplierIds)
                                    ->orderBy('name')
                                    ->get();
                                    
                                foreach ($otherSuppliers as $supplier) {
                                    $options[$supplier->id] = $supplier->name;
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->live()
                            ->placeholder('Select a supplier or create new...')
                            ->helperText('Choose an existing supplier or create a new one. Match confidence and reasons are shown.')
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Supplier Name')
                                            ->default(function () {
                                                if ($this->currentSourceUrl) {
                                                    $matchingService = app(SupplierMatchingService::class);
                                                    return $matchingService->suggestSupplierName($this->currentSourceUrl);
                                                }
                                                return null;
                                            }),
                                        Select::make('type')
                                            ->options([
                                                'seed' => 'Seed',
                                                'soil' => 'Soil',
                                                'consumable' => 'Consumable',
                                            ])
                                            ->default('seed')
                                            ->required()
                                            ->label('Supplier Type'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('contact_name')
                                            ->maxLength(255)
                                            ->label('Contact Name'),
                                        TextInput::make('contact_email')
                                            ->email()
                                            ->maxLength(255)
                                            ->label('Contact Email'),
                                    ]),
                                TextInput::make('contact_phone')
                                    ->tel()
                                    ->maxLength(255)
                                    ->label('Contact Phone')
                                    ->columnSpan(1),
                                Textarea::make('address')
                                    ->maxLength(65535)
                                    ->label('Address')
                                    ->columnSpanFull(),
                                Textarea::make('notes')
                                    ->maxLength(65535)
                                    ->label('Notes')
                                    ->placeholder('Enter any notes about this supplier, including website URL if needed')
                                    ->columnSpanFull(),
                            ])
                            ->createOptionUsing(function (array $data): string {
                                Log::info('SeedScrapeUploader: Creating new supplier', [
                                    'supplier_name' => $data['name'],
                                    'supplier_type' => $data['type'],
                                    'source_url' => $this->currentSourceUrl
                                ]);
                                
                                // Look up the supplier type ID based on the type code
                                $supplierType = \App\Models\SupplierType::findByCode($data['type']);

                                if (!$supplierType) {
                                    throw new \Exception("Invalid supplier type: {$data['type']}");
                                }

                                $supplier = Supplier::create([
                                    'name' => $data['name'],
                                    'supplier_type_id' => $supplierType->id,
                                    'contact_name' => $data['contact_name'] ?? null,
                                    'contact_email' => $data['contact_email'] ?? null,
                                    'contact_phone' => $data['contact_phone'] ?? null,
                                    'address' => $data['address'] ?? null,
                                    'notes' => $data['notes'] ?? null,
                                    'is_active' => true,
                                ]);
                                
                                Log::info('SeedScrapeUploader: New supplier created successfully', [
                                    'supplier_id' => $supplier->id,
                                    'supplier_name' => $supplier->name
                                ]);
                                
                                Notification::make()
                                    ->title('Supplier Created')
                                    ->body("New supplier '{$supplier->name}' has been created successfully.")
                                    ->success()
                                    ->send();
                                
                                return $supplier->getKey();
                            })
                            ->getOptionLabelUsing(function ($value): string {
                                if (!$value) {
                                    return '';
                                }
                                
                                $supplier = Supplier::with('supplierType')->find($value);
                                return $supplier ? $supplier->name : "Supplier #{$value}";
                            }),
                            
                        Actions::make([
                            Action::make('process_files')
                                ->label('Process Files with Selected Supplier')
                                ->color('primary')
                                ->action('processFilesWithSupplier')
                                ->disabled(fn (): bool => empty($this->selectedSupplier) || empty($this->pendingFiles)),
                                
                            Action::make('cancel')
                                ->label('Cancel')
                                ->color('gray')
                                ->action('cancelUpload'),
                        ])->columnSpanFull(),
                    ])
                    ->visible(fn (): bool => $this->showSupplierSelection),
            ]);
    }
    
    /**
     * Handle file upload and supplier matching
     */
    protected function handleFileUpload($state, callable $set): void
    {
        if (empty($state)) {
            Log::debug('SeedScrapeUploader: Empty state received in handleFileUpload');
            return;
        }
        
        $filesToProcess = is_array($state) ? $state : [$state];
        Log::info('SeedScrapeUploader: Starting file upload processing', [
            'total_files' => count($filesToProcess),
            'timestamp' => now()->toISOString()
        ]);
        
        foreach ($filesToProcess as $index => $file) {
            if (!($file instanceof TemporaryUploadedFile)) {
                Log::warning('SeedScrapeUploader: Skipping non-TemporaryUploadedFile instance', [
                    'file_index' => $index,
                    'file_type' => gettype($file)
                ]);
                continue;
            }
            
            Log::info('SeedScrapeUploader: Processing uploaded file', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'temp_path' => $file->getRealPath(),
                'storage_path' => $file->getPath(),
                'file_index' => $index
            ]);
            
            try {
                // Read JSON to extract source URL
                $tempPath = $file->getRealPath();
                Log::debug('SeedScrapeUploader: Reading JSON content from temp file', [
                    'temp_file_path' => $tempPath,
                    'file_exists' => file_exists($tempPath),
                    'file_size' => file_exists($tempPath) ? filesize($tempPath) : 0
                ]);
                
                $jsonContent = file_get_contents($tempPath);
                $jsonData = json_decode($jsonContent, true);
                
                Log::debug('SeedScrapeUploader: JSON decode result', [
                    'decode_success' => $jsonData !== null,
                    'json_error' => json_last_error_msg(),
                    'has_source_site' => isset($jsonData['source_site']),
                    'data_keys' => $jsonData ? array_keys($jsonData) : []
                ]);
                
                if (!$jsonData || !isset($jsonData['source_site'])) {
                    Log::error('SeedScrapeUploader: Invalid JSON structure', [
                        'file_name' => $file->getClientOriginalName(),
                        'json_valid' => $jsonData !== null,
                        'has_source_site' => isset($jsonData['source_site']),
                        'available_fields' => $jsonData ? array_keys($jsonData) : []
                    ]);
                    
                    Notification::make()
                        ->title('Invalid JSON')
                        ->body('JSON file must contain a source_site field')
                        ->danger()
                        ->send();
                    continue;
                }
                
                $sourceUrl = $jsonData['source_site'];
                Log::info('SeedScrapeUploader: Extracted source URL from JSON', [
                    'source_url' => $sourceUrl,
                    'file_name' => $file->getClientOriginalName()
                ]);
                
                // Check for existing mapping
                $existingMapping = SupplierSourceMapping::findMappingForSource($sourceUrl);
                if ($existingMapping) {
                    $existingMapping->load('supplier.supplierType');
                }
                Log::debug('SeedScrapeUploader: Checked for existing supplier mapping', [
                    'source_url' => $sourceUrl,
                    'mapping_exists' => $existingMapping !== null,
                    'supplier_id' => $existingMapping ? $existingMapping->supplier_id : null,
                    'supplier_name' => $existingMapping ? $existingMapping->supplier->name : null
                ]);
                
                if ($existingMapping) {
                    // Process directly with existing mapping
                    Log::info('SeedScrapeUploader: Using existing supplier mapping', [
                        'supplier_id' => $existingMapping->supplier_id,
                        'supplier_name' => $existingMapping->supplier->name,
                        'source_url' => $sourceUrl
                    ]);
                    $this->processFileWithSupplierModal($file, $existingMapping->supplier);
                } else {
                    Log::info('SeedScrapeUploader: No existing mapping found, requiring supplier selection', [
                        'source_url' => $sourceUrl
                    ]);
                    // Need supplier selection
                    $this->currentSourceUrl = $sourceUrl;
                    $this->pendingFiles = [$file];
                    
                    // Find potential matches
                    $matchingService = app(SupplierMatchingService::class);
                    $this->supplierMatches = $matchingService->findPotentialMatches($sourceUrl);
                    
                    Log::info('SeedScrapeUploader: Found potential supplier matches', [
                        'source_url' => $sourceUrl,
                        'match_count' => count($this->supplierMatches),
                        'matches' => collect($this->supplierMatches)->map(function ($match) {
                            return [
                                'supplier_id' => $match['supplier']->id,
                                'supplier_name' => $match['supplier']->name,
                                'confidence' => $match['confidence'],
                                'reasons' => $match['match_reasons']
                            ];
                        })->toArray()
                    ]);
                    
                    $this->showSupplierSelection = true;
                    
                    Notification::make()
                        ->title('Supplier Selection Required')
                        ->body("Please select a supplier for: {$sourceUrl}")
                        ->info()
                        ->send();
                }
                
            } catch (\Exception $e) {
                Log::error('SeedScrapeUploader: Error processing uploaded file', [
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'file' => $file->getClientOriginalName(),
                    'temp_path' => $file->getRealPath(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                Notification::make()
                    ->title('File Processing Error')
                    ->body('Error reading file: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
        
        $set('jsonFiles', []);
    }
    
    /**
     * Process files with selected supplier
     */
    public function processFilesWithSupplier(): void
    {
        if (empty($this->selectedSupplier) || empty($this->pendingFiles)) {
            Log::warning('SeedScrapeUploader: processFilesWithSupplier called with empty supplier or files', [
                'selected_supplier' => $this->selectedSupplier,
                'pending_files_count' => count($this->pendingFiles)
            ]);
            return;
        }
        
        Log::info('SeedScrapeUploader: Starting batch file processing with supplier', [
            'supplier_id' => $this->selectedSupplier,
            'source_url' => $this->currentSourceUrl,
            'file_count' => count($this->pendingFiles)
        ]);
        
        try {
            $supplier = Supplier::with('supplierType')->find($this->selectedSupplier);
            
            if (!$supplier) {
                Log::error('SeedScrapeUploader: Supplier not found', [
                    'supplier_id' => $this->selectedSupplier
                ]);
                throw new \Exception('Supplier not found');
            }
            
            Log::info('SeedScrapeUploader: Creating supplier source mapping', [
                'source_url' => $this->currentSourceUrl,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name
            ]);
            
            // Create mapping for future uploads
            SupplierSourceMapping::createMapping(
                $this->currentSourceUrl, 
                $supplier->id,
                ['created_via' => 'upload_interface', 'created_at' => now()->toISOString()]
            );
            
            // Process all pending files using modal
            $this->processFilesWithSupplierModal($supplier);
            
            // Reset state
            Log::info('SeedScrapeUploader: Batch processing completed, resetting state');
            $this->resetSupplierSelection();
            
        } catch (\Exception $e) {
            Log::error('SeedScrapeUploader: Error processing files with supplier', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'supplier_id' => $this->selectedSupplier,
                'source_url' => $this->currentSourceUrl,
                'pending_files_count' => count($this->pendingFiles),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Processing Error')
                ->body('Error processing files: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Process a single file with a specific supplier
     */
    protected function processFileWithSupplier(TemporaryUploadedFile $file, Supplier $supplier): void
    {
        try {
            $originalFilename = $file->getClientOriginalName();
            
            Log::info('SeedScrapeUploader: Starting individual file processing', [
                'filename' => $originalFilename,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'temp_path' => $file->getRealPath(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            $scrapeUpload = SeedScrapeUpload::create([
                'filename' => $originalFilename,
                'supplier_id' => $supplier->id,
                'uploaded_by' => auth()->id(),
                'status' => SeedScrapeUpload::STATUS_PROCESSING,
                'uploaded_at' => now(),
            ]);
            
            Log::info('SeedScrapeUploader: Created SeedScrapeUpload record', [
                'upload_id' => $scrapeUpload->id,
                'filename' => $originalFilename,
                'status' => SeedScrapeUpload::STATUS_PROCESSING
            ]);
            
            // Use enhanced importer with supplier override
            Log::info('SeedScrapeUploader: Initiating import with SeedScrapeImporter', [
                'upload_id' => $scrapeUpload->id,
                'temp_file_path' => $file->getRealPath(),
                'supplier_id' => $supplier->id
            ]);
            
            $importer = new SeedScrapeImporter();
            $importer->importWithSupplier($file->getRealPath(), $scrapeUpload, $supplier);
            
            $scrapeUpload->refresh();
            
            Log::info('SeedScrapeUploader: File processing completed', [
                'upload_id' => $scrapeUpload->id,
                'filename' => $originalFilename,
                'final_status' => $scrapeUpload->status,
                'total_entries' => $scrapeUpload->total_entries,
                'successful_entries' => $scrapeUpload->successful_entries,
                'failed_entries' => $scrapeUpload->failed_entries_count,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name
            ]);
            
            Notification::make()
                ->title('File Processed Successfully')
                ->body("Processed '{$originalFilename}' with supplier: {$supplier->name}")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('SeedScrapeUploader: Error processing individual file', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $file->getClientOriginalName(),
                'temp_path' => $file->getRealPath(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'upload_id' => isset($scrapeUpload) ? $scrapeUpload->id : null,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update upload status if record was created
            if (isset($scrapeUpload)) {
                $scrapeUpload->update([
                    'status' => SeedScrapeUpload::STATUS_ERROR,
                    'notes' => 'Error: ' . $e->getMessage()
                ]);
                
                Log::info('SeedScrapeUploader: Updated upload status to ERROR', [
                    'upload_id' => $scrapeUpload->id
                ]);
            }
            
            throw $e;
        }
    }
    
    /**
     * Cancel upload and reset state
     */
    public function cancelUpload(): void
    {
        $this->resetSupplierSelection();
        
        Notification::make()
            ->title('Upload Cancelled')
            ->body('File upload has been cancelled')
            ->info()
            ->send();
    }
    
    /**
     * Reset supplier selection state
     */
    protected function resetSupplierSelection(): void
    {
        Log::debug('SeedScrapeUploader: Resetting supplier selection state');
        
        $this->showSupplierSelection = false;
        $this->selectedSupplier = null;
        $this->pendingFiles = [];
        $this->currentSourceUrl = null;
        $this->supplierMatches = [];
    }
    
    /**
     * Close upload modal and reset state
     */
    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploadOutput = '';
        $this->uploadRunning = false;
        $this->uploadSuccess = false;
    }
    
    /**
     * Process a single file with modal display
     */
    protected function processFileWithSupplierModal(TemporaryUploadedFile $file, Supplier $supplier): void
    {
        $this->uploadOutput = '';
        $this->uploadRunning = true;
        $this->uploadSuccess = false;
        $this->showUploadModal = true;
        
        $this->dispatch('open-upload-modal');
        
        try {
            $originalFilename = $file->getClientOriginalName();
            
            $this->uploadOutput = "Starting upload processing...\n";
            $this->uploadOutput .= "File: {$originalFilename}\n";
            $this->uploadOutput .= "Supplier: {$supplier->name}\n";
            $this->uploadOutput .= "Size: " . number_format($file->getSize()) . " bytes\n\n";
            
            Log::info('SeedScrapeUploader: Starting individual file processing', [
                'filename' => $originalFilename,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
            ]);
            
            $this->uploadOutput .= "Creating upload record...\n";
            
            $scrapeUpload = SeedScrapeUpload::create([
                'filename' => $originalFilename,
                'supplier_id' => $supplier->id,
                'uploaded_by' => auth()->id(),
                'status' => SeedScrapeUpload::STATUS_PROCESSING,
                'uploaded_at' => now(),
            ]);
            
            $this->uploadOutput .= "Upload record created (ID: {$scrapeUpload->id})\n";
            $this->uploadOutput .= "Starting data import...\n\n";
            
            // Use enhanced importer with supplier override
            $importer = new SeedScrapeImporter();
            $importer->importWithSupplier($file->getRealPath(), $scrapeUpload, $supplier);
            
            $scrapeUpload->refresh();
            
            $this->uploadOutput .= "\n=== IMPORT COMPLETED ===\n";
            $this->uploadOutput .= "Status: {$scrapeUpload->status}\n";
            $this->uploadOutput .= "Total entries: " . number_format($scrapeUpload->total_entries) . "\n";
            $this->uploadOutput .= "Successful imports: " . number_format($scrapeUpload->successful_entries) . "\n";
            $this->uploadOutput .= "Failed imports: " . number_format($scrapeUpload->failed_entries_count) . "\n";
            
            if ($scrapeUpload->status === SeedScrapeUpload::STATUS_COMPLETED) {
                $this->uploadOutput .= "\n✅ Upload processed successfully!\n";
                $this->uploadSuccess = true;
            } else {
                $this->uploadOutput .= "\n⚠️ Upload completed with issues.\n";
                
                // Show detailed error information
                if ($scrapeUpload->failed_entries && is_array($scrapeUpload->failed_entries)) {
                    $this->uploadOutput .= "\n=== ERROR DETAILS ===\n";
                    
                    // Group errors by type to avoid repetition
                    $errorGroups = [];
                    foreach ($scrapeUpload->failed_entries as $failedEntry) {
                        $error = $failedEntry['error'] ?? 'Unknown error';
                        $title = $failedEntry['data']['title'] ?? 'Unknown product';
                        
                        if (!isset($errorGroups[$error])) {
                            $errorGroups[$error] = [];
                        }
                        $errorGroups[$error][] = $title;
                    }
                    
                    // Display grouped errors
                    foreach ($errorGroups as $error => $products) {
                        $count = count($products);
                        $this->uploadOutput .= "\n❌ {$error} ({$count} products)\n";
                        
                        // Show first few product names as examples
                        $examples = array_slice($products, 0, 3);
                        foreach ($examples as $product) {
                            $this->uploadOutput .= "   - {$product}\n";
                        }
                        
                        if ($count > 3) {
                            $remaining = $count - 3;
                            $this->uploadOutput .= "   ... and {$remaining} more\n";
                        }
                        $this->uploadOutput .= "\n";
                    }
                } elseif ($scrapeUpload->notes) {
                    $this->uploadOutput .= "\nGeneral error:\n" . $scrapeUpload->notes . "\n";
                }
                
                $this->uploadSuccess = false;
            }
            
            $this->uploadRunning = false;
            
            Log::info('SeedScrapeUploader: File processing completed', [
                'upload_id' => $scrapeUpload->id,
                'filename' => $originalFilename,
                'final_status' => $scrapeUpload->status,
                'total_entries' => $scrapeUpload->total_entries,
                'successful_entries' => $scrapeUpload->successful_entries,
            ]);
                
        } catch (\Exception $e) {
            $this->uploadOutput .= "\n❌ ERROR: " . $e->getMessage() . "\n";
            $this->uploadRunning = false;
            $this->uploadSuccess = false;
            
            // Update upload status if record was created
            if (isset($scrapeUpload)) {
                $scrapeUpload->update([
                    'status' => SeedScrapeUpload::STATUS_ERROR,
                    'notes' => 'Error: ' . $e->getMessage()
                ]);
            }
            
            Log::error('SeedScrapeUploader: Error processing individual file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);
        }
    }
    
    /**
     * Process multiple files with modal display
     */
    protected function processFilesWithSupplierModal(Supplier $supplier): void
    {
        $this->uploadOutput = '';
        $this->uploadRunning = true;
        $this->uploadSuccess = false;
        $this->showUploadModal = true;
        
        $this->dispatch('open-upload-modal');
        
        try {
            $fileCount = count($this->pendingFiles);
            
            $this->uploadOutput = "Starting batch upload processing...\n";
            $this->uploadOutput .= "Supplier: {$supplier->name}\n";
            $this->uploadOutput .= "Files to process: {$fileCount}\n\n";
            
            Log::info('SeedScrapeUploader: Processing pending files', [
                'file_count' => $fileCount,
                'supplier_id' => $supplier->id
            ]);
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($this->pendingFiles as $index => $file) {
                $fileName = $file->getClientOriginalName();
                $this->uploadOutput .= "=== Processing file " . ($index + 1) . " of {$fileCount} ===\n";
                $this->uploadOutput .= "File: {$fileName}\n";
                
                try {
                    $scrapeUpload = SeedScrapeUpload::create([
                        'filename' => $fileName,
                        'supplier_id' => $supplier->id,
                        'uploaded_by' => auth()->id(),
                        'status' => SeedScrapeUpload::STATUS_PROCESSING,
                        'uploaded_at' => now(),
                    ]);
                    
                    $this->uploadOutput .= "Upload record created (ID: {$scrapeUpload->id})\n";
                    $this->uploadOutput .= "Starting import...\n";
                    
                    $importer = new SeedScrapeImporter();
                    $importer->importWithSupplier($file->getRealPath(), $scrapeUpload, $supplier);
                    
                    $scrapeUpload->refresh();
                    
                    $this->uploadOutput .= "Import completed!\n";
                    $this->uploadOutput .= "- Status: {$scrapeUpload->status}\n";
                    $this->uploadOutput .= "- Total: " . number_format($scrapeUpload->total_entries) . "\n";
                    $this->uploadOutput .= "- Success: " . number_format($scrapeUpload->successful_entries) . "\n";
                    $this->uploadOutput .= "- Failed: " . number_format($scrapeUpload->failed_entries_count) . "\n\n";
                    
                    if ($scrapeUpload->status === SeedScrapeUpload::STATUS_COMPLETED) {
                        $successCount++;
                    } else {
                        $failCount++;
                        
                        // Show error details for failed imports
                        if ($scrapeUpload->failed_entries && is_array($scrapeUpload->failed_entries)) {
                            $errorGroups = [];
                            foreach ($scrapeUpload->failed_entries as $failedEntry) {
                                $error = $failedEntry['error'] ?? 'Unknown error';
                                if (!isset($errorGroups[$error])) {
                                    $errorGroups[$error] = 0;
                                }
                                $errorGroups[$error]++;
                            }
                            
                            $this->uploadOutput .= "  Error breakdown:\n";
                            foreach ($errorGroups as $error => $count) {
                                $this->uploadOutput .= "    • {$error} ({$count} products)\n";
                            }
                        }
                        $this->uploadOutput .= "\n";
                    }
                    
                } catch (\Exception $e) {
                    $this->uploadOutput .= "❌ Error processing {$fileName}: " . $e->getMessage() . "\n\n";
                    $failCount++;
                    
                    if (isset($scrapeUpload)) {
                        $scrapeUpload->update([
                            'status' => SeedScrapeUpload::STATUS_ERROR,
                            'notes' => 'Error: ' . $e->getMessage()
                        ]);
                    }
                }
            }
            
            $this->uploadOutput .= "=== BATCH PROCESSING COMPLETED ===\n";
            $this->uploadOutput .= "Successfully processed: {$successCount} files\n";
            $this->uploadOutput .= "Failed: {$failCount} files\n";
            
            if ($failCount === 0) {
                $this->uploadOutput .= "\n✅ All files processed successfully!\n";
                $this->uploadSuccess = true;
            } else {
                $this->uploadOutput .= "\n⚠️ Some files had issues during processing.\n";
                $this->uploadSuccess = false;
            }
            
            $this->uploadRunning = false;
            
        } catch (\Exception $e) {
            $this->uploadOutput .= "\n❌ BATCH ERROR: " . $e->getMessage() . "\n";
            $this->uploadRunning = false;
            $this->uploadSuccess = false;
            
            Log::error('SeedScrapeUploader: Error processing batch files', [
                'error' => $e->getMessage(),
                'supplier_id' => $supplier->id,
            ]);
        }
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(SeedScrapeUpload::query()->with(['supplier.supplierType', 'uploadedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('filename')
                    ->label('File')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-storefront')
                    ->placeholder('Not assigned')
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        SeedScrapeUpload::STATUS_PENDING => 'heroicon-o-clock',
                        SeedScrapeUpload::STATUS_PROCESSING => 'heroicon-o-cog-8-tooth',
                        SeedScrapeUpload::STATUS_COMPLETED => 'heroicon-o-check-circle',
                        SeedScrapeUpload::STATUS_ERROR => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        SeedScrapeUpload::STATUS_PENDING => 'gray',
                        SeedScrapeUpload::STATUS_PROCESSING => 'warning',
                        SeedScrapeUpload::STATUS_COMPLETED => 'success',
                        SeedScrapeUpload::STATUS_ERROR => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('progress')
                    ->label('Import Results')
                    ->state(function (SeedScrapeUpload $record): string {
                        if ($record->status === SeedScrapeUpload::STATUS_PENDING) {
                            return 'Waiting to process';
                        }
                        
                        if ($record->status === SeedScrapeUpload::STATUS_PROCESSING) {
                            return 'Processing...';
                        }
                        
                        if ($record->total_entries === 0 && $record->status === SeedScrapeUpload::STATUS_ERROR) {
                            return 'Import failed';
                        }
                        
                        if ($record->total_entries === 0) {
                            return 'No data';
                        }
                        
                        $successful = $record->successful_entries;
                        $failed = $record->failed_entries_count;
                        $total = $record->total_entries;
                        
                        $result = "{$successful}/{$total} imported";
                        
                        if ($failed > 0) {
                            $result .= ", {$failed} failed";
                        }
                        
                        if ($successful === $total && $failed === 0) {
                            $result .= " ✓";
                        } elseif ($failed > 0) {
                            $result .= " ⚠";
                        }
                        
                        return $result;
                    })
                    ->badge()
                    ->color(function (SeedScrapeUpload $record): string {
                        if ($record->status === SeedScrapeUpload::STATUS_PROCESSING) {
                            return 'info';
                        }
                        
                        if ($record->status === SeedScrapeUpload::STATUS_ERROR) {
                            return 'danger';
                        }
                        
                        if ($record->failed_entries_count > 0) {
                            return 'warning';
                        }
                        
                        if ($record->successful_entries > 0) {
                            return 'success';
                        }
                        
                        return 'gray';
                    })
                    ->icon(function (SeedScrapeUpload $record): string {
                        if ($record->status === SeedScrapeUpload::STATUS_PROCESSING) {
                            return 'heroicon-o-arrow-path';
                        }
                        
                        if ($record->status === SeedScrapeUpload::STATUS_ERROR) {
                            return 'heroicon-o-x-circle';
                        }
                        
                        if ($record->failed_entries_count > 0) {
                            return 'heroicon-o-exclamation-triangle';
                        }
                        
                        if ($record->successful_entries > 0) {
                            return 'heroicon-o-check-circle';
                        }
                        
                        return 'heroicon-o-minus-circle';
                    }),
                    
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Uploaded')
                    ->dateTime('M j, Y g:i A')
                    ->description(fn (SeedScrapeUpload $record): string => 
                        $record->uploadedBy ? "by {$record->uploadedBy->name}" : 'Unknown user'
                    ),
                    
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y g:i A')
                    ->placeholder('Not processed')
                    ->description(function (SeedScrapeUpload $record): ?string {
                        if (!$record->processed_at) {
                            return null;
                        }
                        
                        $duration = $record->uploaded_at->diffForHumans($record->processed_at, true);
                        return "took {$duration}";
                    }),
                    
                Tables\Columns\TextColumn::make('error_summary')
                    ->label('Issues')
                    ->state(function (SeedScrapeUpload $record): ?string {
                        if ($record->status === SeedScrapeUpload::STATUS_ERROR && $record->notes) {
                            // Extract first error from notes
                            $lines = explode("\n", $record->notes);
                            foreach ($lines as $line) {
                                if (str_contains(strtolower($line), 'error') || str_contains(strtolower($line), 'failed')) {
                                    return trim($line);
                                }
                            }
                            return 'Processing error occurred';
                        }
                        
                        if ($record->failed_entries_count > 0) {
                            return "{$record->failed_entries_count} entries failed to import";
                        }
                        
                        return null;
                    })
                    ->placeholder('No issues')
                    ->color('danger')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->limit(40)
                    ->tooltip(function (SeedScrapeUpload $record): ?string {
                        if ($record->notes) {
                            return $record->notes;
                        }
                        return null;
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->modalHeading(fn (SeedScrapeUpload $record): string => "Upload Details: {$record->filename}")
                        ->modalContent(function (SeedScrapeUpload $record) {
                            $content = '<div class="space-y-4">';
                            
                            // Basic info
                            $content .= '<div class="grid grid-cols-2 gap-4 text-sm">';
                            $content .= '<div><strong>File:</strong> ' . htmlspecialchars($record->filename) . '</div>';
                            $content .= '<div><strong>Status:</strong> ' . htmlspecialchars($record->status) . '</div>';
                            $content .= '<div><strong>Supplier:</strong> ' . htmlspecialchars($record->supplier?->name ?? 'Not assigned') . '</div>';
                            $content .= '<div><strong>Uploaded:</strong> ' . $record->uploaded_at?->format('M j, Y g:i A') . '</div>';
                            
                            if ($record->processed_at) {
                                $content .= '<div><strong>Processed:</strong> ' . $record->processed_at->format('M j, Y g:i A') . '</div>';
                            }
                            
                            if ($record->total_entries > 0) {
                                $content .= '<div><strong>Total Entries:</strong> ' . number_format($record->total_entries) . '</div>';
                                $content .= '<div><strong>Successful:</strong> ' . number_format($record->successful_entries) . '</div>';
                                $content .= '<div><strong>Failed:</strong> ' . number_format($record->failed_entries_count) . '</div>';
                            }
                            
                            $content .= '</div>';
                            
                            // Notes/Errors
                            if ($record->notes) {
                                $content .= '<div><strong>Processing Log:</strong></div>';
                                $content .= '<pre class="bg-gray-100 dark:bg-gray-800 p-3 rounded text-xs overflow-auto max-h-96">' . htmlspecialchars($record->notes) . '</pre>';
                            }
                            
                            $content .= '</div>';
                            
                            return new \Illuminate\Support\HtmlString($content);
                        }),
                        
                    Tables\Actions\Action::make('view_seed_entries')
                        ->label('View Seed Entries')
                        ->url(fn () => route('filament.admin.resources.seed-entries.index'))
                        ->icon('heroicon-o-list-bullet')
                        ->openUrlInNewTab()
                        ->visible(fn (SeedScrapeUpload $record): bool => $record->successful_entries > 0),
                        
                    Tables\Actions\Action::make('view_variations')
                        ->label('View Variations')
                        ->url(fn () => route('filament.admin.resources.seed-variations.index'))
                        ->icon('heroicon-o-squares-plus')
                        ->openUrlInNewTab()
                        ->visible(fn (SeedScrapeUpload $record): bool => $record->successful_entries > 0),
                        
                    Tables\Actions\Action::make('manage_failed')
                        ->label('Manage Failed Entries')
                        ->url(fn () => route('filament.admin.pages.manage-failed-seed-entries'))
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('warning')
                        ->openUrlInNewTab()
                        ->visible(fn (SeedScrapeUpload $record): bool => $record->failed_entries_count > 0),
                        
                    Tables\Actions\Action::make('retry_processing')
                        ->label('Retry Processing')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action('retryProcessing')
                        ->visible(fn (SeedScrapeUpload $record): bool => 
                            $record->status === SeedScrapeUpload::STATUS_ERROR || $record->failed_entries_count > 0
                        ),
                        
                    Tables\Actions\DeleteAction::make()
                        ->label('Delete Upload Record')
                        ->modalHeading('Delete Upload Record')
                        ->modalDescription('This will only delete the upload record, not the imported data.')
                        ->successNotificationTitle('Upload record deleted'),
                ])
                ->icon('heroicon-o-ellipsis-vertical')
                ->tooltip('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll()
            ->striped()
            ->emptyStateHeading('No uploads yet')
            ->emptyStateDescription('Upload your first JSON file to get started with seed data import.')
            ->emptyStateIcon('heroicon-o-arrow-up-tray')
            ->recordUrl(null); // Disable row click navigation
    }
    
    public function getPollingInterval()
    {
        return $this->refreshInterval * 1000; // Convert to milliseconds
    }
    
    public function retryProcessing(SeedScrapeUpload $record): void
    {
        if (!$record->failed_entries || empty($record->failed_entries)) {
            Notification::make()
                ->title('No Failed Entries')
                ->body('This upload has no failed entries to retry.')
                ->warning()
                ->send();
            return;
        }
        
        $this->uploadOutput = '';
        $this->uploadRunning = true;
        $this->uploadSuccess = false;
        $this->showUploadModal = true;
        
        $this->dispatch('open-upload-modal');
        
        try {
            $this->uploadOutput = "Retrying failed entries...\n";
            $this->uploadOutput .= "Upload ID: {$record->id}\n";
            $this->uploadOutput .= "Original file: {$record->filename}\n";
            $this->uploadOutput .= "Failed entries: " . count($record->failed_entries) . "\n\n";
            
            Log::info('SeedScrapeUploader: Starting retry processing', [
                'upload_id' => $record->id,
                'failed_count' => count($record->failed_entries),
                'supplier_id' => $record->supplier_id,
            ]);
            
            if (!$record->supplier) {
                throw new \Exception('No supplier assigned to this upload record');
            }
            
            $importer = new SeedScrapeImporter();
            $successfulRetries = 0;
            $stillFailed = [];
            $totalRetries = count($record->failed_entries);
            
            $this->uploadOutput .= "Processing failed entries with supplier: {$record->supplier->name}\n\n";
            
            foreach ($record->failed_entries as $index => $failedEntry) {
                $entryIndex = $failedEntry['index'] ?? $index;
                $productData = $failedEntry['data'] ?? [];
                $productTitle = $productData['title'] ?? "Entry #{$entryIndex}";
                
                $this->uploadOutput .= "Retrying: {$productTitle}\n";
                
                try {
                    // Create a minimal JSON structure for this single product
                    $tempJson = [
                        'timestamp' => now()->toIso8601String(),
                        'source_site' => $record->supplier->name,
                        'data' => [$productData]
                    ];
                    
                    // Create temporary file
                    $tempFile = tempnam(sys_get_temp_dir(), 'retry_') . '.json';
                    file_put_contents($tempFile, json_encode($tempJson));
                    
                    // Process this single entry
                    $importer->processProduct(
                        $productData,
                        $record->supplier,
                        $tempJson['timestamp'],
                        $this->detectCurrencyFromProduct($productData)
                    );
                    
                    $successfulRetries++;
                    $this->uploadOutput .= "  ✓ Success\n";
                    
                    // Clean up
                    if (file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                    
                } catch (\Exception $e) {
                    $this->uploadOutput .= "  ✗ Failed: " . $e->getMessage() . "\n";
                    $stillFailed[] = [
                        'index' => $entryIndex,
                        'data' => $productData,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'timestamp' => now()->toIso8601String(),
                        'retry_attempt' => ($failedEntry['retry_attempt'] ?? 0) + 1
                    ];
                    
                    Log::warning('Retry failed for product entry', [
                        'upload_id' => $record->id,
                        'entry_index' => $entryIndex,
                        'title' => $productTitle,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Update the record with retry results
            $newSuccessfulCount = $record->successful_entries + $successfulRetries;
            $newFailedCount = count($stillFailed);
            $newStatus = $newFailedCount === 0 ? SeedScrapeUpload::STATUS_COMPLETED : SeedScrapeUpload::STATUS_COMPLETED;
            
            $record->update([
                'successful_entries' => $newSuccessfulCount,
                'failed_entries_count' => $newFailedCount,
                'failed_entries' => $stillFailed,
                'status' => $newStatus,
                'notes' => ($record->notes ?? '') . "\n\n--- RETRY RESULTS ---\n" . 
                          "Retried {$totalRetries} failed entries.\n" .
                          "Successful: {$successfulRetries}\n" . 
                          "Still failed: {$newFailedCount}\n" .
                          "Updated at: " . now()->format('Y-m-d H:i:s')
            ]);
            
            $this->uploadOutput .= "\n=== RETRY COMPLETED ===\n";
            $this->uploadOutput .= "Successfully retried: {$successfulRetries}\n";
            $this->uploadOutput .= "Still failed: {$newFailedCount}\n";
            $this->uploadOutput .= "Total successful entries: {$newSuccessfulCount}\n";
            
            if ($successfulRetries > 0) {
                $this->uploadOutput .= "\n✅ Retry processing completed with improvements!\n";
                $this->uploadSuccess = true;
            } else {
                $this->uploadOutput .= "\n⚠️ No entries could be successfully retried.\n";
                $this->uploadSuccess = false;
            }
            
            $this->uploadRunning = false;
            
            // Show notification
            if ($successfulRetries > 0) {
                Notification::make()
                    ->title('Retry Successful')
                    ->body("Successfully imported {$successfulRetries} previously failed entries.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Retry Failed')
                    ->body('No failed entries could be successfully imported. Check the processing log for details.')
                    ->warning()
                    ->send();
            }
            
        } catch (\Exception $e) {
            $this->uploadOutput .= "\n\nRetry Error: " . $e->getMessage();
            $this->uploadRunning = false;
            $this->uploadSuccess = false;
            
            Log::error('SeedScrapeUploader: Retry processing failed', [
                'upload_id' => $record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Retry Failed')
                ->body('An error occurred while retrying failed entries: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    private function detectCurrencyFromProduct(array $productData): string
    {
        // Try to detect currency from product variations
        if (isset($productData['variants']) && is_array($productData['variants'])) {
            foreach ($productData['variants'] as $variant) {
                if (isset($variant['currency']) && !empty($variant['currency'])) {
                    return $variant['currency'];
                }
            }
        }
        
        if (isset($productData['variations']) && is_array($productData['variations'])) {
            foreach ($productData['variations'] as $variant) {
                if (isset($variant['currency']) && !empty($variant['currency'])) {
                    return $variant['currency'];
                }
            }
        }
        
        // Default to USD
        return 'USD';
    }
} 