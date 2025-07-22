<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\CustomerResource\Forms\CustomerForm;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\Tables\CustomerTable;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;

class CustomerResource extends BaseResource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Customers';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema(CustomerForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns([
                ...CustomerTable::columns(),
                ...static::getTimestampColumns(),
            ])
            ->filters(CustomerTable::filters())
            ->actions(CustomerTable::actions())
            ->bulkActions(CustomerTable::bulkActions());
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
