<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\Base\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Supplier;
use App\Models\SupplierType;
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
    protected static ?string $navigationGroup = 'Products & Inventory';
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
                            
                        Forms\Components\Select::make('supplier_type_id')
                            ->label('Supplier Type')
                            ->relationship('supplierType', 'name')
                            ->options(SupplierType::options())
                            ->default(function () {
                                return SupplierType::findByCode('other')?->id;
                            })
                            ->required(),
                            
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
        return static::configureTableDefaults($table)
            ->columns([
                static::getTextColumn('name', 'Name')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('supplierType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Seeds' => 'success',
                        'Soil' => 'warning', 
                        'Packaging' => 'info',
                        'Consumables' => 'purple',
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
                Tables\Filters\SelectFilter::make('supplier_type_id')
                    ->label('Supplier Type')
                    ->relationship('supplierType', 'name')
                    ->options(SupplierType::options()),
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