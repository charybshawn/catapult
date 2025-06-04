<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessSeedScrapeUpload;
use App\Models\SeedScrapeUpload;
use App\Services\SeedScrapeImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
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
    
    protected static ?string $navigationGroup = 'Seed Management';
    
    protected static ?int $navigationSort = 6;
    
    public $jsonFiles = [];
    
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
                    ->description('Upload JSON files scraped from seed supplier websites. After uploading, the table below will show processing status.')
                    ->schema([
                        FileUpload::make('jsonFiles')
                            ->label('JSON Files')
                            ->multiple()
                            ->acceptedFileTypes(['application/json'])
                            ->maxSize(10240) // 10MB
                            ->disk('local')
                            ->afterStateUpdated(function ($state, callable $set) {
                                \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] CALLED.");
                                
                                $processedFilesPaths = [];

                                $filesToProcess = [];
                                if (is_array($state)) {
                                    $filesToProcess = $state;
                                } elseif ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                    $filesToProcess = [$state];
                                }

                                if (empty($filesToProcess)) {
                                    \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] No files in state to process.");
                                    return;
                                }

                                foreach ($filesToProcess as $fileInState) {
                                    if (!($fileInState instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)) {
                                        \Illuminate\Support\Facades\Log::warning("[PROCESS IN AFTER_STATE_UPDATED] Encountered an item in state that is not a TemporaryUploadedFile. Skipping.");
                                        continue;
                                    }

                                    $originalFilename = "unknown.json";
                                    $tempPath = null;

                                    try {
                                        $originalFilename = $fileInState->getClientOriginalName();
                                        $tempPath = $fileInState->getRealPath();
                                    } catch (\Throwable $e) {
                                        \Illuminate\Support\Facades\Log::error("[PROCESS IN AFTER_STATE_UPDATED] Error getting file details for one of the files: " . $e->getMessage());
                                        // Optionally notify for this specific file error, or collect errors
                                        Notification::make()
                                            ->title('File Detail Error')
                                            ->body("Could not get details for an uploaded file: " . $e->getMessage())
                                            ->danger()
                                            ->send();
                                        continue; // Move to the next file if details can't be obtained
                                    }

                                    \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] Attempting to process: {$originalFilename} from temp path: {$tempPath}");

                                    if (!$tempPath || !file_exists($tempPath)) {
                                        \Illuminate\Support\Facades\Log::error("[PROCESS IN AFTER_STATE_UPDATED] Temporary file not found or path is invalid for {$originalFilename}: {$tempPath}");
                                        Notification::make()
                                            ->title('File Error')
                                            ->body("Temporary file for '{$originalFilename}' is not accessible.")
                                            ->danger()
                                            ->send();
                                        continue; // Move to next file
                                    }

                                    $scrapeUpload = null; 
                                    try {
                                        $scrapeUpload = SeedScrapeUpload::create([
                                            'original_filename' => $originalFilename,
                                            'status' => SeedScrapeUpload::STATUS_PROCESSING,
                                            'uploaded_at' => now(),
                                        ]);
                                        \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] Created SeedScrapeUpload record ID: {$scrapeUpload->id} for {$originalFilename}");

                                        $importer = new SeedScrapeImporter();
                                        \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] Starting direct import for temp file: {$tempPath}");
                                        $importer->import($tempPath, $scrapeUpload);
                                        
                                        $scrapeUpload->refresh();
                                        \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] Import finished for {$originalFilename}. Status: {$scrapeUpload->status}");

                                        Notification::make()
                                            ->title('File Processed Directly!')
                                            ->body("File '{$originalFilename}' processed. Status: {$scrapeUpload->status}. Notes: {$scrapeUpload->notes}")
                                            ->success()
                                            ->send();
                                        
                                        // Add original filename to array for form state
                                        $processedFilesPaths[] = $originalFilename; 

                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error("[PROCESS IN AFTER_STATE_UPDATED] Error during direct processing of '{$originalFilename}': " . $e->getMessage(), ['exception' => $e]);
                                        if ($scrapeUpload) {
                                            $scrapeUpload->refresh();
                                            $scrapeUpload->update([
                                                'status' => SeedScrapeUpload::STATUS_ERROR,
                                                'notes' => ($scrapeUpload->notes ? $scrapeUpload->notes . " | " : "") . "Exception: " . $e->getMessage(),
                                                'processed_at' => now(),
                                            ]);
                                        }
                                        Notification::make()
                                            ->title('Direct Processing Error')
                                            ->body("Failed to process file '{$originalFilename}' directly: " . $e->getMessage())
                                            ->danger()
                                            ->send();
                                        // Decide if one error stops all, or continue processing other files.
                                        // For now, we continue.
                                    }
                                } // end foreach
                                
                                // After processing all files, update the form state with the paths (original filenames in our case)
                                // This tells Filament what to store as the value of the jsonFiles field.
                                // If you don't set this, the field might appear empty after upload, or retain old values.
                                $set('jsonFiles', $processedFilesPaths);
                                \Illuminate\Support\Facades\Log::info("[PROCESS IN AFTER_STATE_UPDATED] Finished processing all files. Updated form state.");
                            })
                            ->uploadProgressIndicatorPosition('left')
                            ->helperText('Upload JSON files. Processing happens directly from temporary location inside afterStateUpdated hook.'),
                    ]),
            ]);
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