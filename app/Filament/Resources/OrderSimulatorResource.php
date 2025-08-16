<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderSimulatorResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderSimulatorResource extends BaseResource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Planning';

    protected static ?string $navigationLabel = 'Order Simulator';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ->actions([
                //
            ])
            ->bulkActions([
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
            'index' => Pages\ManageOrderSimulator::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }
}