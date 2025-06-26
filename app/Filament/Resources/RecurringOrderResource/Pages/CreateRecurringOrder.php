<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateRecurringOrder extends BaseCreateRecord
{
    protected static string $resource = RecurringOrderResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set required fields for recurring order templates
        $data['is_recurring'] = true;
        $data['status'] = 'template';
        $data['delivery_date'] = $data['recurring_start_date'] ?? now();
        $data['harvest_date'] = $data['delivery_date'];
        
        return $data;
    }
}
