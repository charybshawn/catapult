<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\OrderSimulatorResource\Pages\ManageOrderSimulator;
use App\Filament\Resources\OrderSimulatorResource\Pages;
use Filament\Forms;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderSimulatorResource extends BaseResource
{
    protected static ?string $model = null;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';

    protected static string | \UnitEnum | null $navigationGroup = 'Planning';

    protected static ?string $navigationLabel = 'Order Simulator';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Form will be handled in custom page
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Table will be handled in custom page
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
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
            'index' => ManageOrderSimulator::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}