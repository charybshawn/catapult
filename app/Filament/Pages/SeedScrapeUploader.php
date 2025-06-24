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
                                $otherSuppliers = Supplier::where('is_active', true)
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
                                
                                $supplier = Supplier::create([
                                    'name' => $data['name'],
                                    'type' => $data['type'],
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
                                
                                $supplier = Supplier::find($value);
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
                    $this->processFileWithSupplier($file, $existingMapping->supplier);
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
            $supplier = Supplier::find($this->selectedSupplier);
            
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
            
            // Process all pending files
            Log::info('SeedScrapeUploader: Processing pending files', [
                'file_count' => count($this->pendingFiles),
                'supplier_id' => $supplier->id
            ]);
            
            foreach ($this->pendingFiles as $index => $file) {
                Log::debug('SeedScrapeUploader: Processing file in batch', [
                    'file_index' => $index,
                    'file_name' => $file->getClientOriginalName(),
                    'supplier_id' => $supplier->id
                ]);
                $this->processFileWithSupplier($file, $supplier);
            }
            
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
                'original_filename' => $originalFilename,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'temp_path' => $file->getRealPath(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);
            
            $scrapeUpload = SeedScrapeUpload::create([
                'original_filename' => $originalFilename,
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
    
    public function table(Table $table): Table
    {
        return $table
            ->query(SeedScrapeUpload::query())
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SeedScrapeUpload::STATUS_PENDING => 'gray',
                        SeedScrapeUpload::STATUS_PROCESSING => 'warning',
                        SeedScrapeUpload::STATUS_COMPLETED => 'success',
                        SeedScrapeUpload::STATUS_ERROR => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->state(function (SeedScrapeUpload $record): string {
                        if ($record->total_entries === 0) {
                            return 'N/A';
                        }
                        
                        $successful = $record->successful_entries;
                        $failed = $record->failed_entries_count;
                        $total = $record->total_entries;
                        
                        return "{$successful}/{$total} success" . ($failed > 0 ? ", {$failed} failed" : '');
                    })
                    ->color(function (SeedScrapeUpload $record): string {
                        if ($record->failed_entries_count > 0) {
                            return 'warning';
                        } elseif ($record->successful_entries > 0) {
                            return 'success';
                        }
                        return 'gray';
                    }),
                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalContent(fn (SeedScrapeUpload $record): string => $record->notes ?? 'No notes available.'),
                Tables\Actions\Action::make('view_data')
                    ->label('View Imported Data')
                    ->url(route('filament.admin.resources.seed-variations.index'))
                    ->icon('heroicon-m-eye')
                    ->visible(fn (SeedScrapeUpload $record): bool => $record->status === SeedScrapeUpload::STATUS_COMPLETED),
                Tables\Actions\Action::make('manage_failed')
                    ->label('Manage Failed')
                    ->url(route('filament.admin.pages.manage-failed-seed-entries'))
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('warning')
                    ->visible(fn (SeedScrapeUpload $record): bool => $record->failed_entries_count > 0),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->poll();
    }
    
    public function getPollingInterval()
    {
        return $this->refreshInterval * 1000; // Convert to milliseconds
    }
} 