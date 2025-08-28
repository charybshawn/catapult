<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use App\Models\Harvest;
use Illuminate\Database\Eloquent\Model;

/**
 * Simplified harvest creation page for cultivar-based harvest recording.
 * 
 * Handles the streamlined harvest workflow where each form submission creates
 * multiple harvest records - one per cultivar-weight pair. This approach
 * eliminates complex tray relationships and provides direct cultivar-based
 * harvest tracking for agricultural operations.
 * 
 * @business_domain Agricultural harvest recording with simplified workflow
 * @workflow_context Multi-cultivar harvest recording in single form submission
 * @agricultural_process Direct cultivar-weight harvest data collection
 */
class CreateHarvest extends BaseCreateRecord
{
    protected static string $resource = HarvestResource::class;

    /**
     * Handle simplified cultivar-based harvest record creation.
     * 
     * Processes the new cultivar-based form data structure and creates
     * multiple harvest records - one for each cultivar-weight pair entered
     * in the form repeater. This approach enables cumulative harvest tracking
     * and eliminates complex pivot table relationships.
     * 
     * @param array $data Form data with structure:
     *   - harvest_date: Date of harvest operation
     *   - user_id: User performing harvest 
     *   - notes: Optional general harvest notes
     *   - cultivar_harvests: Array of cultivar-weight pairs
     * @return Model The last created harvest record (for redirect purposes)
     * 
     * @workflow Creates one harvest record per cultivar in cultivar_harvests array
     * @cumulative_support Multiple submissions with same cultivar/date create separate records
     * @simplified_approach No complex pivot relationships or crop stage management
     */
    protected function handleRecordCreation(array $data): Model
    {
        $lastHarvest = null;
        
        // Create a harvest record for each cultivar-weight pair
        foreach ($data['cultivar_harvests'] as $cultivarHarvest) {
            $lastHarvest = Harvest::create([
                'master_cultivar_id' => $cultivarHarvest['master_cultivar_id'],
                'harvest_date' => $data['harvest_date'],
                'user_id' => $data['user_id'],
                'total_weight_grams' => $cultivarHarvest['total_weight_grams'],
                'notes' => $data['notes'] ?? null,
            ]);
        }
        
        return $lastHarvest;
    }

    /**
     * Redirect to harvest list after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Custom success notification message for multi-cultivar harvest
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Harvest(s) recorded successfully';
    }
}
