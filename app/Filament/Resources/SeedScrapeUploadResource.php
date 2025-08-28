<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\SeedScrapeUploadResource\Pages\ListSeedScrapeUploads;
use App\Filament\Resources\SeedScrapeUploadResource\Pages\CreateSeedScrapeUpload;
use App\Filament\Resources\SeedScrapeUploadResource\Pages\EditSeedScrapeUpload;
use App\Filament\Resources\SeedScrapeUploadResource\Pages\ViewSeedScrapeUpload;
use App\Filament\Resources\SeedScrapeUploadResource\Pages;
use App\Models\SeedScrapeUpload;
use Filament\Forms;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SeedScrapeUploadResource extends BaseResource
{
    protected static ?string $model = SeedScrapeUpload::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';
    
    protected static ?string $navigationLabel = 'Seed Data Uploads';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('filename')
                    ->label('Filename')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),
                Select::make('status')
                    ->options([
                        SeedScrapeUpload::STATUS_PENDING => 'Pending',
                        SeedScrapeUpload::STATUS_PROCESSING => 'Processing',
                        SeedScrapeUpload::STATUS_COMPLETED => 'Completed',
                        SeedScrapeUpload::STATUS_ERROR => 'Error',
                    ])
                    ->required(),
                DateTimePicker::make('uploaded_at')
                    ->required()
                    ->disabled(),
                DateTimePicker::make('processed_at')
                    ->disabled(),
                Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                TextColumn::make('filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SeedScrapeUpload::STATUS_PENDING => 'gray',
                        SeedScrapeUpload::STATUS_PROCESSING => 'warning',
                        SeedScrapeUpload::STATUS_COMPLETED => 'success',
                        SeedScrapeUpload::STATUS_ERROR => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        SeedScrapeUpload::STATUS_PENDING => 'Pending',
                        SeedScrapeUpload::STATUS_PROCESSING => 'Processing',
                        SeedScrapeUpload::STATUS_COMPLETED => 'Completed',
                        SeedScrapeUpload::STATUS_ERROR => 'Error',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListSeedScrapeUploads::route('/'),
            'create' => CreateSeedScrapeUpload::route('/create'),
            'edit' => EditSeedScrapeUpload::route('/{record}/edit'),
            'view' => ViewSeedScrapeUpload::route('/{record}'),
        ];
    }
} 