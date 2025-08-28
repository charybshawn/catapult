<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CropAlertResource\Pages\ListCropAlerts;
use App\Filament\Resources\CropAlertResource\Pages\CreateCropAlert;
use App\Filament\Resources\CropAlertResource\Pages\EditCropAlert;
use App\Filament\Resources\CropAlertResource\Forms\CropAlertForm;
use App\Filament\Resources\CropAlertResource\Pages;
use App\Filament\Resources\CropAlertResource\Tables\CropAlertTable;
use App\Filament\Resources\CropAlertResource\Tables\CropAlertTableActions;
use App\Models\CropAlert;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CropAlertResource extends BaseResource
{
    protected static ?string $model = CropAlert::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Crop Alerts';
    protected static string | \UnitEnum | null $navigationGroup = 'Production';
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'task_name';
    protected static ?string $modelLabel = 'Crop Alert';
    protected static ?string $pluralModelLabel = 'Crop Alerts';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(CropAlertForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->defaultSort('next_run_at', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => CropAlertTable::modifyQuery($query))
            ->columns(CropAlertTable::columns())
            ->filters(CropAlertTable::filters())
            ->recordActions(CropAlertTableActions::actions())
            ->toolbarActions(CropAlertTableActions::bulkActions())
            ->emptyStateHeading('No crop alerts')
            ->emptyStateDescription('Alerts will appear here when crops are scheduled for stage transitions.')
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->groups(CropAlertTable::groups());
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
            'index' => ListCropAlerts::route('/'),
            'create' => CreateCropAlert::route('/create'),
            'edit' => EditCropAlert::route('/{record}/edit'),
        ];
    }
} 