<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use App\Filament\Resources\ActivityResource\Pages\ViewActivity;
use App\Filament\Resources\ActivityResource\Pages\ActivityStatistics;
use App\Filament\Resources\ActivityResource\Pages\ActivityTimeline;
use App\Filament\Resources\ActivityResource\Forms\ActivityForm;
use App\Filament\Resources\ActivityResource\Pages;
use App\Filament\Resources\ActivityResource\Tables\ActivityTable;
use App\Models\Activity;
use App\Filament\Resources\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends BaseResource
{
    protected static ?string $model = Activity::class;

    // Make visible in navigation
    protected static bool $shouldRegisterNavigation = true;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'System';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Activity Logs';
    
    protected static ?string $pluralModelLabel = 'Activity Logs';
    
    protected static ?string $modelLabel = 'Activity Log';

    public static function form(Schema $schema): Schema
    {
        return $schema->components(ActivityForm::schema());
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->columns(ActivityTable::columns())
            ->filters(ActivityTable::filters(), layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->recordActions(ActivityTable::actions())
            ->toolbarActions(ActivityTable::bulkActions())
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
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
            'stats' => ActivityStatistics::route('/statistics'),
            'timeline' => ActivityTimeline::route('/timeline'),
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