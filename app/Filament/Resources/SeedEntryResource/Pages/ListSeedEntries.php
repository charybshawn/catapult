<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List seed entries page for agricultural seed catalog management.
 * Provides comprehensive table view of all seed entries with filtering, searching,
 * and management capabilities for microgreens production seed inventory.
 *
 * @business_domain Agricultural seed catalog and supplier management
 * @page_context Seed entry listing with table view and creation capabilities
 * @agricultural_features Seed variety management, supplier tracking, pricing overview
 * @list_functionality Standard Filament list page with create action
 */
class ListSeedEntries extends ListRecords
{
    protected static string $resource = SeedEntryResource::class;

    /**
     * Get header actions for seed entry management operations.
     * Provides create action for adding new seed varieties to the agricultural catalog.
     *
     * @agricultural_context Create new seed entries for expanding microgreens variety options
     * @return array Header actions for seed entry management
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
} 