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

class SeedScrapeUploader extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    
    protected static string $view = 'filament.pages.seed-scrape-uploader';
    
    protected static ?string $title = 'Upload Seed Data';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 6;
    
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
            return;
        }
        
        $filesToProcess = is_array($state) ? $state : [$state];
        
        foreach ($filesToProcess as $file) {
            if (!($file instanceof TemporaryUploadedFile)) {
                continue;
            }
            
            try {
                // Read JSON to extract source URL
                $jsonContent = file_get_contents($file->getRealPath());
                $jsonData = json_decode($jsonContent, true);
                
                if (!$jsonData || !isset($jsonData['source_site'])) {
                    Notification::make()
                        ->title('Invalid JSON')
                        ->body('JSON file must contain a source_site field')
                        ->danger()
                        ->send();
                    continue;
                }
                
                $sourceUrl = $jsonData['source_site'];
                
                // Check for existing mapping
                $existingMapping = SupplierSourceMapping::findMappingForSource($sourceUrl);
                
                if ($existingMapping) {
                    // Process directly with existing mapping
                    $this->processFileWithSupplier($file, $existingMapping->supplier);
                } else {
                    // Need supplier selection
                    $this->currentSourceUrl = $sourceUrl;
                    $this->pendingFiles = [$file];
                    
                    // Find potential matches
                    $matchingService = app(SupplierMatchingService::class);
                    $this->supplierMatches = $matchingService->findPotentialMatches($sourceUrl);
                    
                    $this->showSupplierSelection = true;
                    
                    Notification::make()
                        ->title('Supplier Selection Required')
                        ->body("Please select a supplier for: {$sourceUrl}")
                        ->info()
                        ->send();
                }
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error processing uploaded file', [
                    'error' => $e->getMessage(),
                    'file' => $file->getClientOriginalName()
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
            return;
        }
        
        try {
            $supplier = Supplier::find($this->selectedSupplier);
            
            if (!$supplier) {
                throw new \Exception('Supplier not found');
            }
            
            // Create mapping for future uploads
            SupplierSourceMapping::createMapping(
                $this->currentSourceUrl, 
                $supplier->id,
                ['created_via' => 'upload_interface', 'created_at' => now()->toISOString()]
            );
            
            // Process all pending files
            foreach ($this->pendingFiles as $file) {
                $this->processFileWithSupplier($file, $supplier);
            }
            
            // Reset state
            $this->resetSupplierSelection();
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error processing files with supplier', [
                'error' => $e->getMessage(),
                'supplier_id' => $this->selectedSupplier
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
            
            $scrapeUpload = SeedScrapeUpload::create([
                'original_filename' => $originalFilename,
                'status' => SeedScrapeUpload::STATUS_PROCESSING,
                'uploaded_at' => now(),
            ]);
            
            // Use enhanced importer with supplier override
            $importer = new SeedScrapeImporter();
            $importer->importWithSupplier($file->getRealPath(), $scrapeUpload, $supplier);
            
            $scrapeUpload->refresh();
            
            Notification::make()
                ->title('File Processed Successfully')
                ->body("Processed '{$originalFilename}' with supplier: {$supplier->name}")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error processing individual file', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'supplier' => $supplier->name
            ]);
            
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