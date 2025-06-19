<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataExportResource\Pages;
use App\Models\DataExport;
use App\Services\ImportExport\ResourceExportService;
use App\Services\ImportExport\ResourceDefinitions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class DataExportResource extends Resource
{
    protected static ?string $model = DataExport::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Import/Export';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Export Details')
                    ->schema([
                        Forms\Components\Select::make('resource')
                            ->label('Resource')
                            ->options(function () {
                                $resources = ResourceExportService::getAvailableResources();
                                return array_combine($resources, array_map('ucfirst', $resources));
                            })
                            ->required()
                            ->reactive()
                            ->disabled(fn ($record) => $record !== null),
                            
                        Forms\Components\Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'json' => 'JSON',
                                'csv' => 'CSV',
                            ])
                            ->default('json')
                            ->required()
                            ->disabled(fn ($record) => $record !== null),
                            
                        Forms\Components\Toggle::make('include_timestamps')
                            ->label('Include Timestamps')
                            ->helperText('Include created_at and updated_at columns')
                            ->default(false)
                            ->disabled(fn ($record) => $record !== null),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Export Filters')
                    ->description('Optionally filter data to export')
                    ->schema(function (Forms\Get $get) {
                        $resource = $get('resource');
                        
                        // Return resource-specific filters
                        return match($resource) {
                            'orders' => [
                                Forms\Components\Select::make('filters.status')
                                    ->label('Order Status')
                                    ->options([
                                        '' => 'All Orders',
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default(''),
                                    
                                Forms\Components\DatePicker::make('filters.date_from')
                                    ->label('From Date'),
                                    
                                Forms\Components\DatePicker::make('filters.date_to')
                                    ->label('To Date'),
                                    
                                Forms\Components\Select::make('filters.customer_type')
                                    ->label('Customer Type')
                                    ->options([
                                        '' => 'All Customers',
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default(''),
                            ],
                            
                            'products' => [
                                Forms\Components\Toggle::make('filters.active_only')
                                    ->label('Active Products Only')
                                    ->default(true),
                                    
                                Forms\Components\Select::make('filters.category_id')
                                    ->label('Category')
                                    ->options(function () {
                                        return ['' => 'All Categories'] + \App\Models\Category::pluck('name', 'id')->toArray();
                                    })
                                    ->default(''),
                                    
                                Forms\Components\Toggle::make('filters.in_stock_only')
                                    ->label('In Stock Only')
                                    ->default(false),
                            ],
                            
                            'users' => [
                                Forms\Components\Select::make('filters.customer_type')
                                    ->label('Customer Type')
                                    ->options([
                                        '' => 'All Users',
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default(''),
                                    
                                Forms\Components\Toggle::make('filters.with_orders')
                                    ->label('Only Users with Orders')
                                    ->default(false),
                            ],
                            
                            'recipes' => [
                                Forms\Components\Toggle::make('filters.active_only')
                                    ->label('Active Recipes Only')
                                    ->default(true),
                                    
                                Forms\Components\Select::make('filters.common_name')
                                    ->label('Crop Type')
                                    ->options(function () {
                                        return ['' => 'All Crops'] + \App\Models\Recipe::distinct()->pluck('common_name', 'common_name')->toArray();
                                    })
                                    ->default(''),
                            ],
                            
                            'consumables' => [
                                Forms\Components\Select::make('filters.type')
                                    ->label('Consumable Type')
                                    ->options([
                                        '' => 'All Types',
                                        'seed' => 'Seeds',
                                        'soil' => 'Soil',
                                        'packaging' => 'Packaging',
                                        'label' => 'Labels',
                                        'other' => 'Other',
                                    ])
                                    ->default(''),
                                    
                                Forms\Components\Toggle::make('filters.needs_restock')
                                    ->label('Needs Restock Only')
                                    ->default(false),
                            ],
                            
                            default => [
                                Forms\Components\Placeholder::make('no_filters')
                                    ->label('')
                                    ->content('No filters available for this resource'),
                            ],
                        };
                    })
                    ->columns(3)
                    ->visible(fn ($record) => $record === null),
                    
                Forms\Components\Section::make('Export Information')
                    ->schema([
                        Forms\Components\Placeholder::make('filename')
                            ->content(fn ($record) => $record?->filename ?? 'Will be generated'),
                            
                        Forms\Components\Placeholder::make('file_size')
                            ->label('File Size')
                            ->content(fn ($record) => $record?->formatted_file_size ?? '-'),
                            
                        Forms\Components\Placeholder::make('total_records')
                            ->label('Total Records')
                            ->content(fn ($record) => $record ? number_format($record->total_records) : '-'),
                            
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? '-'),
                            
                        Forms\Components\Placeholder::make('user')
                            ->label('Created By')
                            ->content(fn ($record) => $record?->user?->name ?? '-'),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->columns(2),
                    
                Forms\Components\Section::make('Tables Included')
                    ->schema([
                        Forms\Components\View::make('tables_list')
                            ->view('filament.resources.data-export.tables-list')
                            ->visible(fn ($record) => $record !== null && $record->manifest !== null),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('resource')
                    ->label('Resource')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('format')
                    ->label('Format')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'json' => 'info',
                        'csv' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('total_records')
                    ->label('Records')
                    ->numeric()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Size')
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('file_size', $direction)),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
                    
                Tables\Columns\IconColumn::make('file_exists')
                    ->label('Available')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn ($record) => $record->fileExists()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('resource')
                    ->options(function () {
                        $resources = ResourceExportService::getAvailableResources();
                        return array_combine($resources, array_map('ucfirst', $resources));
                    }),
                    
                Tables\Filters\SelectFilter::make('format')
                    ->options([
                        'json' => 'JSON',
                        'csv' => 'CSV',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn ($record) => $record->fileExists())
                    ->action(function ($record) {
                        return response()->download($record->filepath);
                    }),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('This will permanently delete the export file. This action cannot be undone.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDataExports::route('/'),
            'create' => Pages\CreateDataExport::route('/create'),
            'view' => Pages\ViewDataExport::route('/{record}'),
        ];
    }
}