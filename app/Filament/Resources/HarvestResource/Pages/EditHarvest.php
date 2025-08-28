<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\HarvestResource;
use App\Filament\Resources\HarvestResource\Forms\HarvestForm;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament page for editing simplified agricultural harvest records.
 *
 * Provides streamlined harvest record editing capabilities for the simplified
 * cultivar-based harvest approach. Supports editing of cultivar-specific harvest
 * data including weight adjustments and date modifications without complex
 * tray relationship management.
 *
 * @filament_page
 * @business_domain Agricultural harvest modification with simplified workflow
 * @related_models Harvest, MasterCultivar
 * @workflow_support Direct harvest record editing for cultivar-based entries
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
     * Override form schema for edit context to use direct fields instead of repeater.
     * 
     * Provides single harvest record editing with direct cultivar selection and weight
     * input fields, eliminating the repeater structure used for multi-cultivar creation.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with direct harvest field editing
     * @edit_context Uses direct fields for single record modification
     * @agricultural_workflow Individual harvest record editing for corrections and updates
     */
    public function form(Schema $schema): Schema
    {
        return $schema->components(HarvestForm::schema(true));
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
