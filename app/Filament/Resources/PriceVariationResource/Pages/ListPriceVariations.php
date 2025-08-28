<?php

namespace App\Filament\Resources\PriceVariationResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Resources\PriceVariationResource;
use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * ListPriceVariations Page for Agricultural Product Pricing Overview
 * 
 * Provides comprehensive listing of agricultural product price variations with
 * navigation to related resources. Supports both product-specific variations and
 * global pricing templates for microgreens business operations.
 * 
 * @filament_page List page for PriceVariationResource
 * @business_domain Agricultural product pricing with global template support
 * @extends ListRecords Standard Filament list page with navigation enhancements
 * 
 * @navigation_context Links to ProductResource for agricultural product management
 * @pricing_overview Displays retail, wholesale, bulk, and template variations
 * @agricultural_support Handles complex microgreens pricing structures
 * 
 * @related_resources ProductResource for managing agricultural products
 * @crud_operations Standard listing with create action and cross-navigation
 */
class ListPriceVariations extends ListRecords
{
    protected static string $resource = PriceVariationResource::class;

    /**
     * Get header actions for price variation management.
     * 
     * Provides create action for new agricultural price variations and navigation
     * to ProductResource for managing the underlying agricultural products.
     * Essential for efficient workflow between pricing and product management.
     * 
     * @return array Header actions including create and navigation options
     * @agricultural_workflow Links pricing management to product catalog operations
     * @navigation_enhancement Quick access to related agricultural product management
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('view_products')
                ->label('View Products')
                ->icon('heroicon-o-shopping-bag')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
