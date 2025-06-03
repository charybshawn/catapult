<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\Base\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierResource extends BaseResource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationGroup = 'Inventory & Supplies';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Supplier Name')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('type')
                            ->label('Supplier Type')
                            ->options([
                                'seed' => 'Seed Supplier',
                                'soil' => 'Soil Supplier',
                                'packaging' => 'Packaging Supplier',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('other'),
                            
                        FormCommon::activeToggle(),
                    ])
                    ->columns(2),
                    
                FormCommon::contactInformationSection(),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        FormCommon::notesTextarea(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                static::getTextColumn('name', 'Name')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'seed' => 'success',
                        'soil' => 'warning',
                        'packaging' => 'info',
                        default => 'gray',
                    }),
                    
                static::getTextColumn('contact_name', 'Contact'),
                static::getTextColumn('contact_email', 'Email'),
                static::getTextColumn('contact_phone', 'Phone'),
                static::getActiveBadgeColumn(),
                ...static::getTimestampColumns(),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'seed' => 'Seed Supplier',
                        'soil' => 'Soil Supplier',
                        'packaging' => 'Packaging Supplier',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
            ])
            ->actions(static::getDefaultTableActions())
            ->bulkActions([static::getDefaultBulkActions()])
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
} 