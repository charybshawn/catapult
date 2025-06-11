<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedScrapeUploadResource\Pages;
use App\Models\SeedScrapeUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SeedScrapeUploadResource extends Resource
{
    protected static ?string $model = SeedScrapeUpload::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    
    protected static ?string $navigationLabel = 'Seed Data Uploads';
    
    protected static ?string $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('original_filename')
                    ->label('Filename')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        SeedScrapeUpload::STATUS_PENDING => 'Pending',
                        SeedScrapeUpload::STATUS_PROCESSING => 'Processing',
                        SeedScrapeUpload::STATUS_COMPLETED => 'Completed',
                        SeedScrapeUpload::STATUS_ERROR => 'Error',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('uploaded_at')
                    ->required()
                    ->disabled(),
                Forms\Components\DateTimePicker::make('processed_at')
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        SeedScrapeUpload::STATUS_PENDING => 'Pending',
                        SeedScrapeUpload::STATUS_PROCESSING => 'Processing',
                        SeedScrapeUpload::STATUS_COMPLETED => 'Completed',
                        SeedScrapeUpload::STATUS_ERROR => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('uploaded_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedScrapeUploads::route('/'),
            'create' => Pages\CreateSeedScrapeUpload::route('/create'),
            'edit' => Pages\EditSeedScrapeUpload::route('/{record}/edit'),
            'view' => Pages\ViewSeedScrapeUpload::route('/{record}'),
        ];
    }
} 