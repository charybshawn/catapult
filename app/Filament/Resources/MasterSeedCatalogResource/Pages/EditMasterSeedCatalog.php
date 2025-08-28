<?php

namespace App\Filament\Resources\MasterSeedCatalogResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\MasterSeedCatalogResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

/**
 * Filament page for editing master seed catalog records in agricultural management.
 *
 * Provides comprehensive seed catalog editing capabilities for microgreens variety
 * management and agricultural reference data maintenance. Supports common name
 * management, botanical classification, and agricultural characteristic tracking
 * for production planning and seed sourcing operations.
 *
 * @filament_page
 * @business_domain Agricultural seed catalog management and botanical reference data
 * @related_models MasterSeedCatalog, MasterCultivar, SeedEntry
 * @workflow_support Seed catalog editing, botanical classification, agricultural reference management
 * @agricultural_context Microgreens seed variety management and production planning support
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class EditMasterSeedCatalog extends BaseEditRecord
{
    protected static string $resource = MasterSeedCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    
}
