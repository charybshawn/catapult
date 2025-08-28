<?php

namespace App\Filament\Resources\MasterCultivarResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\MasterCultivarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Filament page for listing and managing master cultivar records.
 *
 * Provides comprehensive listing interface for agricultural seed cultivar management
 * with creation capabilities. Supports microgreens variety catalog organization
 * and reference data maintenance for agricultural production planning.
 *
 * @filament_page
 * @business_domain Agricultural seed cultivar listing and management
 * @related_models MasterCultivar, MasterSeedCatalog
 * @workflow_support Cultivar listing, creation, agricultural reference data management
 * @agricultural_context Microgreens seed variety catalog and production planning support
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class ListMasterCultivars extends ListRecords
{
    protected static string $resource = MasterCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
