<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceVariationResource\Forms\PriceVariationForm;
use App\Filament\Resources\PriceVariationResource\Pages;
use App\Filament\Resources\PriceVariationResource\Tables\PriceVariationTable;
use App\Models\PriceVariation;
use Filament\Forms\Form;
use Filament\Tables\Table;

/**
 * PriceVariation Resource
 * Refactored from 604 lines to follow delegation pattern
 * Extends BaseResource following Filament Resource Architecture Guide
 */
class PriceVariationResource extends BaseResource
{
    protected static ?string $model = PriceVariation::class;

    // Hide from navigation since price variations are managed within ProductResource
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Configure form using extracted form class
     */
    public static function form(Form $form): Form
    {
        return $form->schema(PriceVariationForm::schema());
    }

    /**
     * Configure table using extracted table class
     */
    public static function table(Table $table): Table
    {
        return PriceVariationTable::configure($table);
    }

    /**
     * Get relations - none for now
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Get pages configuration
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceVariations::route('/'),
            'create' => Pages\CreatePriceVariation::route('/create'),
            'edit' => Pages\EditPriceVariation::route('/{record}/edit'),
        ];
    }
}
