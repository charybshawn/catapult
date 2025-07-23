<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Forms\ActivityForm;
use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\Tables\ActivityTable;
use App\Models\Activity;
use Filament\Forms\Form;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends BaseResource
{
    protected static ?string $model = Activity::class;

    // Make visible in navigation
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Activity Logs';
    
    protected static ?string $pluralModelLabel = 'Activity Logs';
    
    protected static ?string $modelLabel = 'Activity Log';

    public static function form(Form $form): Form
    {
        return $form->schema(ActivityForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns(ActivityTable::columns())
            ->filters(ActivityTable::filters(), layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->actions(ActivityTable::actions())
            ->bulkActions(ActivityTable::bulkActions())
            ->headerActions(ActivityTable::headerActions())
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
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
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
            'stats' => Pages\ActivityStatistics::route('/statistics'),
            'timeline' => Pages\ActivityTimeline::route('/timeline'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
} 