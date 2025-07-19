<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\Supplier;
use App\Models\SupplierType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;

class SupplierResource extends BaseResource
{
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Suppliers';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                static::getBasicInformationSection([
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
                        
                    static::getActiveStatusField(),
                ]),
                
                static::getContactInformationSection(),
                
                static::getAdditionalInformationSection(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureStandardTable(
            $table,
            columns: [
                static::getTextColumn('name', 'Name')
                    ->url(fn (Supplier $record): string => SupplierResource::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                static::getStatusBadgeColumn(
                    field: 'supplierType.name',
                    label: 'Type',
                    colorMap: [
                        'Seeds' => 'success',
                        'Soil' => 'warning', 
                        'Packaging' => 'info',
                        'Consumables' => 'purple',
                    ]
                ),
                    
                static::getTextColumn('contact_name', 'Contact'),
                static::getTextColumn('contact_email', 'Email'),
                static::getTextColumn('contact_phone', 'Phone'),
                static::getActiveStatusColumn(),
            ],
            filters: [
                static::getRelationshipFilter('supplierType', 'Supplier Type'),
                static::getActiveStatusFilter(),
            ],
            actions: [
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->tooltip('View record'),
                    Tables\Actions\EditAction::make()
                        ->tooltip('Edit record'),
                    Tables\Actions\DeleteAction::make()
                        ->tooltip('Delete record'),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ]
        )->defaultSort('name', 'asc');
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