<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Filament page for listing and managing master seed catalog records.
 *
 * Provides comprehensive listing interface for agricultural seed catalog management
 * with enhanced creation capabilities. Supports microgreens botanical reference
 * management, seed variety organization, and agricultural classification systems
 * for production planning and sourcing operations.
 *
 * @filament_page
 * @business_domain Agricultural seed catalog listing and botanical reference management
 * @related_models MasterSeedCatalog, MasterCultivar, SeedEntry
 * @workflow_support Seed catalog listing, creation, botanical classification management
 * @agricultural_context Microgreens seed variety catalog and agricultural reference data organization
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class ListMasterSeedCatalogs extends ListRecords
{
    protected static string $resource = MasterSeedCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create New Entry'),
        ];
    }
}
