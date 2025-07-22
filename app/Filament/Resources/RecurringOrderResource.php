<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringOrderResource\Forms\RecurringOrderForm;
use App\Filament\Resources\RecurringOrderResource\Pages;
use App\Filament\Resources\RecurringOrderResource\Tables\RecurringOrderTable;
use App\Filament\Resources\RecurringOrderResource\Tables\RecurringOrderTableActions;
use App\Models\Order;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recurring Order Resource - Refactored following Filament Resource Architecture Guide
 * Reduced from 621 lines to delegation pattern
 * Form logic → RecurringOrderForm (322 lines)
 * Table logic → RecurringOrderTable (193 lines) 
 * Table actions → RecurringOrderTableActions (138 lines)
 * Business logic → Action classes (GenerateRecurringOrdersAction, BulkGenerateOrdersAction)
 */
class RecurringOrderResource extends BaseResource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Recurring Orders';
    protected static ?string $modelLabel = 'Recurring Order';
    protected static ?string $pluralModelLabel = 'Recurring Orders';
    protected static ?string $navigationGroup = 'Orders & Sales';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'id';
    
    // Only show recurring order templates
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_recurring', true)
            ->whereNull('parent_recurring_order_id'); // Only templates, not generated orders
    }

    public static function form(Form $form): Form
    {
        return $form->schema(RecurringOrderForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->modifyQueryUsing(fn (Builder $query) => RecurringOrderTable::modifyQuery($query))
            ->columns(RecurringOrderTable::columns())
            ->filters(RecurringOrderTable::filters())
            ->actions(RecurringOrderTableActions::actions())
            ->headerActions(RecurringOrderTableActions::headerActions())
            ->bulkActions(RecurringOrderTableActions::bulkActions());
    }

    public static function getRelations(): array
    {
        return [
            // Generated orders relation will be added after creating the relation manager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringOrders::route('/'),
            'create' => Pages\CreateRecurringOrder::route('/create'),
            'edit' => Pages\EditRecurringOrder::route('/{record}/edit'),
        ];
    }
}
