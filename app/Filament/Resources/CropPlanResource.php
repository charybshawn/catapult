<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropPlanResource\Forms\CropPlanForm;
use App\Filament\Resources\CropPlanResource\Pages;
use App\Filament\Resources\CropPlanResource\Tables\CropPlanTable;
use App\Models\CropPlan;
use Filament\Forms\Form;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables\Table;

class CropPlanResource extends BaseResource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Crop Plans';

    protected static ?string $navigationGroup = 'Production';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema(CropPlanForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(CropPlanTable::modifyQuery(...))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns(CropPlanTable::columns())
            ->defaultSort('plant_by_date', 'asc')
            ->filters(CropPlanTable::filters())
            ->headerActions(CropPlanTable::headerActions())
            ->actions(CropPlanTable::actions())
            ->bulkActions(CropPlanTable::bulkActions())
            ->groups(CropPlanTable::groups());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\CalendarCropPlans::route('/'),
            'list' => Pages\ListCropPlans::route('/list'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Crop plans are auto-generated from orders
    }
}
