<?php

namespace App\Filament\Resources\MasterCultivarResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\MasterCultivarResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

/**
 * Filament page for editing master cultivar records in agricultural seed management.
 *
 * Provides comprehensive cultivar editing capabilities for microgreens seed variety
 * management. Supports agricultural reference data maintenance including cultivar
 * names, characteristics, and relationships to seed catalog entries.
 *
 * @filament_page
 * @business_domain Agricultural seed cultivar management and reference data maintenance
 * @related_models MasterCultivar, MasterSeedCatalog
 * @workflow_support Cultivar editing, agricultural reference data management
 * @agricultural_context Microgreens seed variety management and catalog organization
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class EditMasterCultivar extends BaseEditRecord
{
    protected static string $resource = MasterCultivarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
