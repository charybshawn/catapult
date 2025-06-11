<?php

namespace App\Filament\Pages;

use App\Models\SeedEntry;
use App\Models\SeedScrapeUpload;
use App\Models\Supplier;
use App\Services\SeedScrapeImporter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ManageFailedSeedEntries extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static string $view = 'filament.pages.manage-failed-seed-entries';
    
    protected static ?string $slug = 'manage-failed-seed-entries';
    
    protected static ?string $title = 'Failed Seed Entries';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 7;
    
    public static function getNavigationBadge(): ?string
    {
        return SeedScrapeUpload::where('failed_entries_count', '>', 0)->sum('failed_entries_count') ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                SeedScrapeUpload::query()
                    ->where('failed_entries_count', '>', 0)
                    ->orderBy('processed_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('original_filename')
                    ->label('Upload File')
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
                Tables\Columns\TextColumn::make('total_entries')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('successful_entries')
                    ->label('Successful')
                    ->numeric()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_entries_count')
                    ->label('Failed')
                    ->numeric()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Processed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SeedScrapeUpload::STATUS_COMPLETED => 'Completed (with errors)',
                        SeedScrapeUpload::STATUS_ERROR => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_failed_entries')
                    ->label('Fix Failed Entries')
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->color('warning')
                    ->modalHeading(fn (SeedScrapeUpload $record) => "Fix Failed Entries - {$record->original_filename}")
                    ->modalDescription(fn (SeedScrapeUpload $record) => "Review and fix {$record->failed_entries_count} failed entries. You can edit the data and retry individual entries or ignore them.")
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->form([
                        Repeater::make('failed_entries')
                            ->label('Failed Entries')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Product Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                        TextInput::make('url')
                                            ->label('Product URL')
                                            ->url()
                                            ->columnSpan(1),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('common_name')
                                            ->label('Common Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->datalist(fn () => SeedEntry::distinct('common_name')->pluck('common_name')->filter()->toArray())
                                            ->columnSpan(1),
                                        TextInput::make('cultivar_name')
                                            ->label('Cultivar/Variety Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(1),
                                    ]),
                                Grid::make(3)
                                    ->schema([
                                        Select::make('supplier_id')
                                            ->label('Supplier')
                                            ->options(Supplier::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->columnSpan(1),
                                        Checkbox::make('is_in_stock')
                                            ->label('In Stock')
                                            ->columnSpan(1),
                                        TextInput::make('image_url')
                                            ->label('Image URL')
                                            ->url()
                                            ->columnSpan(1),
                                    ]),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->maxLength(1000)
                                    ->columnSpanFull(),
                                Repeater::make('variations')
                                    ->label('Product Variations')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('size')
                                                    ->label('Size/Package')
                                                    ->required()
                                                    ->columnSpan(1),
                                                TextInput::make('price')
                                                    ->label('Price')
                                                    ->numeric()
                                                    ->step(0.01)
                                                    ->helperText('Leave empty if out of stock and price not available')
                                                    ->columnSpan(1),
                                                TextInput::make('sku')
                                                    ->label('SKU')
                                                    ->columnSpan(1),
                                                Checkbox::make('is_in_stock')
                                                    ->label('In Stock')
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->columnSpanFull(),
                                Section::make('Error Information')
                                    ->schema([
                                        TextInput::make('error')
                                            ->label('Error Message')
                                            ->disabled()
                                            ->columnSpanFull(),
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('error_type')
                                                    ->label('Error Type')
                                                    ->disabled()
                                                    ->columnSpan(1),
                                                TextInput::make('timestamp')
                                                    ->label('Failed At')
                                                    ->disabled()
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->collapsed()
                                    ->columnSpanFull(),
                                Actions::make([
                                    Action::make('retry_entry')
                                        ->label('Retry This Entry')
                                        ->icon('heroicon-m-arrow-path')
                                        ->color('success')
                                        ->action(function (array $data, Repeater $component, string $statePath) {
                                            // Extract index from state path (e.g., "failed_entries.0" -> 0)
                                            preg_match('/failed_entries\.(\d+)/', $statePath, $matches);
                                            $entryIndex = $matches[1] ?? 0;
                                            $uploadId = $data['_upload_id'] ?? null;
                                            
                                            if ($uploadId) {
                                                $this->retryIndividualEntry($uploadId, $entryIndex, $data);
                                            }
                                        }),
                                    Action::make('ignore_entry')
                                        ->label('Ignore Entry')
                                        ->icon('heroicon-m-x-mark')
                                        ->color('danger')
                                        ->requiresConfirmation()
                                        ->action(function (array $data, Repeater $component, string $statePath) {
                                            // Extract index from state path (e.g., "failed_entries.0" -> 0)  
                                            preg_match('/failed_entries\.(\d+)/', $statePath, $matches);
                                            $entryIndex = $matches[1] ?? 0;
                                            $uploadId = $data['_upload_id'] ?? null;
                                            
                                            if ($uploadId) {
                                                $this->ignoreIndividualEntry($uploadId, $entryIndex);
                                            }
                                        }),
                                ])
                                ->columnSpanFull(),
                            ])
                            ->itemLabel(function (array $state): ?string {
                                return $state['title'] ?? 'Untitled Entry';
                            })
                            ->collapsed()
                            ->cloneable(false)
                            ->deletable(false)
                            ->addable(false)
                            ->columnSpanFull(),
                    ])
                    ->fillForm(function (SeedScrapeUpload $record): array {
                        $failedEntries = collect($record->failed_entries ?? [])
                            ->map(function ($entry, $index) use ($record) {
                                $data = $entry['data'] ?? [];
                                
                                // Extract variations
                                $variations = [];
                                if (isset($data['variations'])) {
                                    $variations = $data['variations'];
                                } elseif (isset($data['variants'])) {
                                    $variations = $data['variants'];
                                }
                                
                                return [
                                    'title' => $data['title'] ?? '',
                                    'url' => $data['url'] ?? '',
                                    'common_name' => $data['common_name'] ?? $this->extractCommonName($data['title'] ?? ''),
                                    'cultivar_name' => $data['cultivar_name'] ?? $data['cultivar'] ?? $data['title'] ?? '',
                                    'supplier_id' => $this->detectSupplierId($record),
                                    'is_in_stock' => $data['is_in_stock'] ?? true,
                                    'image_url' => $data['image_url'] ?? '',
                                    'description' => $data['description'] ?? '',
                                    'variations' => collect($variations)->map(function ($variation) {
                                        return [
                                            'size' => $variation['size'] ?? $variation['variant_title'] ?? $variation['title'] ?? 'Default',
                                            'price' => $variation['price'] ?? null,
                                            'sku' => $variation['sku'] ?? '',
                                            'is_in_stock' => $variation['is_in_stock'] ?? $variation['is_variation_in_stock'] ?? true,
                                        ];
                                    })->toArray(),
                                    'error' => $entry['error'] ?? '',
                                    'error_type' => $entry['error_type'] ?? '',
                                    'timestamp' => $entry['timestamp'] ?? '',
                                    '_upload_id' => $record->id,
                                    '_entry_index' => $index,
                                ];
                            })
                            ->toArray();

                        return ['failed_entries' => $failedEntries];
                    })
                    ->modalWidth('7xl'),
                    
                Tables\Actions\Action::make('retry_all')
                    ->label('Retry All Failed')
                    ->icon('heroicon-m-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Retry All Failed Entries')
                    ->modalDescription(fn (SeedScrapeUpload $record) => "This will retry processing all {$record->failed_entries_count} failed entries.")
                    ->action(function (SeedScrapeUpload $record) {
                        $this->retryAllFailedEntries($record);
                    }),
                    
                Tables\Actions\Action::make('clear_failed')
                    ->label('Clear Failed')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear All Failed Entries')
                    ->modalDescription('This will permanently remove all failed entries from this upload. This action cannot be undone.')
                    ->action(function (SeedScrapeUpload $record) {
                        $record->update([
                            'failed_entries' => [],
                            'failed_entries_count' => 0,
                            'notes' => $record->notes . ' (Failed entries cleared by user)'
                        ]);
                        
                        Notification::make()
                            ->title('Failed Entries Cleared')
                            ->body("All failed entries have been removed from {$record->original_filename}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('retry_all_selected')
                        ->label('Retry All Failed in Selected')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $this->retryAllFailedEntries($record);
                            }
                        }),
                ]),
            ]);
    }
    
    protected function retryAllFailedEntries(SeedScrapeUpload $upload): void
    {
        try {
            $failedEntries = $upload->failed_entries ?? [];
            
            if (empty($failedEntries)) {
                Notification::make()
                    ->title('No Failed Entries')
                    ->body('There are no failed entries to retry.')
                    ->warning()
                    ->send();
                return;
            }
            
            // Get the supplier from the first successful entry or use a default
            $supplier = $this->detectSupplierForRetry($upload);
            
            if (!$supplier) {
                Notification::make()
                    ->title('Supplier Not Found')
                    ->body('Could not determine the supplier for retrying entries.')
                    ->danger()
                    ->send();
                return;
            }
            
            $importer = new SeedScrapeImporter();
            $successCount = 0;
            $newFailedEntries = [];
            
            foreach ($failedEntries as $failedEntry) {
                try {
                    // Extract the product data
                    $productData = $failedEntry['data'] ?? [];
                    
                    // Attempt to reprocess
                    $importer->processProduct(
                        $productData, 
                        $supplier, 
                        now()->toIso8601String(), 
                        'USD' // Default currency, could be improved
                    );
                    
                    $successCount++;
                } catch (\Exception $e) {
                    // Still failed, keep in failed entries
                    $failedEntry['retry_error'] = $e->getMessage();
                    $failedEntry['retry_timestamp'] = now()->toIso8601String();
                    $newFailedEntries[] = $failedEntry;
                }
            }
            
            // Update the upload record
            $upload->update([
                'failed_entries' => $newFailedEntries,
                'failed_entries_count' => count($newFailedEntries),
                'successful_entries' => $upload->successful_entries + $successCount,
                'notes' => $upload->notes . "\nRetried {$successCount}/" . count($failedEntries) . " failed entries at " . now()->format('Y-m-d H:i:s')
            ]);
            
            Notification::make()
                ->title('Retry Complete')
                ->body("Successfully processed {$successCount} entries. " . count($newFailedEntries) . " entries still failed.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error retrying failed entries', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Retry Failed')
                ->body('An error occurred while retrying: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function detectSupplierForRetry(SeedScrapeUpload $upload): ?Supplier
    {
        return $this->detectSupplier($upload);
    }
    
    protected function detectSupplierId(SeedScrapeUpload $upload): ?int
    {
        $supplier = $this->detectSupplier($upload);
        return $supplier?->id;
    }
    
    protected function detectSupplier(SeedScrapeUpload $upload): ?Supplier
    {
        // Try to find a supplier from successful entries
        // This is a simplified approach - in production, you might want to store supplier_id in the upload record
        
        // Check if we can parse the original filename for supplier info
        if (preg_match('/^(.+?)_/', $upload->original_filename, $matches)) {
            $supplierName = str_replace('_', '.', $matches[1]);
            return Supplier::where('name', 'LIKE', "%{$supplierName}%")->first();
        }
        
        // Fallback to a default or first active supplier
        return Supplier::where('is_active', true)->first();
    }
    
    protected function extractCommonName(string $title): string
    {
        if (empty($title)) {
            return '';
        }
        
        // Simple extraction logic - take first word or up to first comma/dash
        $cleaned = trim($title);
        
        // Remove common prefixes
        $cleaned = preg_replace('/^(Greencrops,?\s*)?(\d+\s*)?/i', '', $cleaned);
        
        // If there's a comma, take everything before it
        if (strpos($cleaned, ',') !== false) {
            $parts = explode(',', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // If there's a dash, take everything before it
        if (strpos($cleaned, ' - ') !== false) {
            $parts = explode(' - ', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // Take first 2-3 words
        $words = explode(' ', $cleaned);
        if (count($words) >= 2) {
            return trim($words[0] . ' ' . $words[1]);
        }
        
        return $cleaned;
    }
    
    protected function retryIndividualEntry(int $uploadId, int $entryIndex, array $fixedData): void
    {
        try {
            $upload = SeedScrapeUpload::findOrFail($uploadId);
            $failedEntries = $upload->failed_entries ?? [];
            
            if (!isset($failedEntries[$entryIndex])) {
                Notification::make()
                    ->title('Entry Not Found')
                    ->body('The specified failed entry could not be found.')
                    ->danger()
                    ->send();
                return;
            }
            
            // Get supplier
            $supplier = Supplier::find($fixedData['supplier_id']);
            if (!$supplier) {
                Notification::make()
                    ->title('Supplier Not Found')
                    ->body('The specified supplier could not be found.')
                    ->danger()
                    ->send();
                return;
            }
            
            // Convert fixed data back to the expected format
            $productData = [
                'title' => $fixedData['title'],
                'url' => $fixedData['url'] ?? '',
                'common_name' => $fixedData['common_name'],
                'cultivar_name' => $fixedData['cultivar_name'],
                'is_in_stock' => $fixedData['is_in_stock'] ?? true,
                'image_url' => $fixedData['image_url'] ?? '',
                'description' => $fixedData['description'] ?? '',
                'variations' => $fixedData['variations'] ?? [],
            ];
            
            // Attempt to process the fixed entry
            $importer = new SeedScrapeImporter();
            $importer->processProduct($productData, $supplier, now()->toIso8601String(), 'USD');
            
            // Remove this entry from failed entries
            unset($failedEntries[$entryIndex]);
            $failedEntries = array_values($failedEntries); // Re-index array
            
            // Update the upload record
            $upload->update([
                'failed_entries' => $failedEntries,
                'failed_entries_count' => count($failedEntries),
                'successful_entries' => $upload->successful_entries + 1,
                'notes' => $upload->notes . "\nFixed and retried entry '{$fixedData['title']}' at " . now()->format('Y-m-d H:i:s')
            ]);
            
            Notification::make()
                ->title('Entry Processed Successfully')
                ->body("Successfully processed '{$fixedData['title']}'")
                ->success()
                ->send();
                
            // Refresh the page to show updated data
            $this->redirect(request()->header('Referer'));
                
        } catch (\Exception $e) {
            Log::error('Error retrying individual entry', [
                'upload_id' => $uploadId,
                'entry_index' => $entryIndex,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Retry Failed')
                ->body('An error occurred while retrying: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function ignoreIndividualEntry(int $uploadId, int $entryIndex): void
    {
        try {
            $upload = SeedScrapeUpload::findOrFail($uploadId);
            $failedEntries = $upload->failed_entries ?? [];
            
            if (!isset($failedEntries[$entryIndex])) {
                Notification::make()
                    ->title('Entry Not Found')
                    ->body('The specified failed entry could not be found.')
                    ->danger()
                    ->send();
                return;
            }
            
            $entryTitle = $failedEntries[$entryIndex]['data']['title'] ?? 'Unknown Entry';
            
            // Remove this entry from failed entries
            unset($failedEntries[$entryIndex]);
            $failedEntries = array_values($failedEntries); // Re-index array
            
            // Update the upload record
            $upload->update([
                'failed_entries' => $failedEntries,
                'failed_entries_count' => count($failedEntries),
                'notes' => $upload->notes . "\nIgnored entry '{$entryTitle}' at " . now()->format('Y-m-d H:i:s')
            ]);
            
            Notification::make()
                ->title('Entry Ignored')
                ->body("Ignored '{$entryTitle}'")
                ->success()
                ->send();
                
            // Refresh the page to show updated data
            $this->redirect(request()->header('Referer'));
                
        } catch (\Exception $e) {
            Log::error('Error ignoring individual entry', [
                'upload_id' => $uploadId,
                'entry_index' => $entryIndex,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Ignore Failed')
                ->body('An error occurred while ignoring entry: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}