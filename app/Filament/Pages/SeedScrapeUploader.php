<?php

namespace App\Filament\Pages;

use App\Jobs\ProcessSeedScrapeUpload;
use App\Models\SeedScrapeUpload;
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
    
    protected static ?string $navigationGroup = 'Seed Inventory';
    
    protected static ?int $navigationSort = 30;
    
    public $jsonFiles = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload Seed Data')
                    ->description('Upload JSON files scraped from seed supplier websites.')
                    ->schema([
                        FileUpload::make('jsonFiles')
                            ->label('JSON Files')
                            ->multiple()
                            ->acceptedFileTypes(['application/json'])
                            ->directory('seed-scrape-uploads')
                            ->maxSize(10240) // 10MB
                            ->disk('local')
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                $filename = $file->getClientOriginalName();
                                $path = $file->storeAs('seed-scrape-uploads', $filename, 'local');
                                
                                // Create upload record
                                $scrapeUpload = SeedScrapeUpload::create([
                                    'original_filename' => $filename,
                                    'status' => SeedScrapeUpload::STATUS_PENDING,
                                    'uploaded_at' => now(),
                                ]);
                                
                                // Process the upload
                                ProcessSeedScrapeUpload::dispatch(
                                    $scrapeUpload,
                                    Storage::disk('local')->path($path)
                                );
                                
                                Notification::make()
                                    ->title('Upload received')
                                    ->body('Your file is being processed.')
                                    ->success()
                                    ->send();
                                
                                return $path;
                            }),
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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('uploaded_at', 'desc');
    }
} 