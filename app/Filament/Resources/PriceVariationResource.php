<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\PriceVariationResource\Pages\ListPriceVariations;
use App\Filament\Resources\PriceVariationResource\Pages\CreatePriceVariation;
use App\Filament\Resources\PriceVariationResource\Pages\EditPriceVariation;
use App\Filament\Resources\PriceVariationResource\Forms\PriceVariationForm;
use App\Filament\Resources\PriceVariationResource\Pages;
use App\Filament\Resources\PriceVariationResource\Tables\PriceVariationTable;
use App\Models\PriceVariation;
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

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Configure form using extracted form class
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(PriceVariationForm::schema());
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
            'index' => ListPriceVariations::route('/'),
            'create' => CreatePriceVariation::route('/create'),
            'edit' => EditPriceVariation::route('/{record}/edit'),
        ];
    }
}
