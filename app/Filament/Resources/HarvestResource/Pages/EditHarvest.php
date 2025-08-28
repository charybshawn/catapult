<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use Filament\Actions\DeleteAction;
use App\Actions\Harvest\CreateHarvestAction;
use App\Filament\Resources\HarvestResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament page for editing agricultural harvest records.
 *
 * Provides comprehensive harvest record editing capabilities with complex
 * crop relationship management and pivot data handling. Integrates with
 * agricultural business actions for data consistency and validation.
 * Supports multi-tray harvest modifications and production data updates.
 *
 * @filament_page
 * @business_domain Agricultural harvest modification and production record updates
 * @related_models Harvest, Crop, MasterCultivar
 * @workflow_support Harvest record editing, tray relationship management
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class EditHarvest extends BaseEditRecord
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Transform harvest record data for form display including crop relationships.
     *
     * Loads existing crop relationships from pivot table and formats them for
     * the repeater component. Maps pivot data including weight, percentage, and
     * notes for each associated crop in the harvest operation.
     *
     * @param array $data Raw harvest record data from database
     * @return array Transformed data with crops array formatted for form repeater
     * @filament_hook Pre-fill data transformation hook
     * @agricultural_context Loads multi-tray harvest data for editing interface
     * @pivot_handling Maps crop pivot data (weight, percentage, notes) to form structure
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing crop relationships
        $data['crops'] = $this->record->crops->map(function ($crop) {
            return [
                'crop_id' => $crop->id,
                'harvested_weight_grams' => $crop->pivot->harvested_weight_grams,
                'percentage_harvested' => $crop->pivot->percentage_harvested,
                'notes' => $crop->pivot->notes,
            ];
        })->toArray();
        
        return $data;
    }

    /**
     * Handle harvest record updates using agricultural business action.
     *
     * Delegates record updates to CreateHarvestAction which provides comprehensive
     * business logic for harvest modifications including crop relationship updates,
     * pivot data management, and agricultural validation rules.
     *
     * @param Model $record Existing harvest record to update
     * @param array $data Updated form data from user input
     * @return Model Updated harvest record with relationships
     * @filament_hook Record update handler
     * @action_integration Uses CreateHarvestAction for business logic consistency
     * @agricultural_workflow Maintains harvest data integrity and crop status updates
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CreateHarvestAction::class)->update($record, $data);
    }

    /**
     * Provide custom success notification for harvest updates.
     *
     * Returns user-friendly success message specifically tailored for agricultural
     * harvest update operations to provide clear feedback on successful modifications.
     *
     * @return string|null Custom success notification message
     * @filament_hook Success notification customization
     * @user_experience Clear feedback for agricultural data update operations
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Harvest updated successfully';
    }
}
