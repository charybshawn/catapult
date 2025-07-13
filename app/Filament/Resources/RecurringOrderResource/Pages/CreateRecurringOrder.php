<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRecurringOrder extends BaseCreateRecord
{
    protected static string $resource = RecurringOrderResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract order items data before creating the order
        $orderItems = $data['orderItems'] ?? [];
        unset($data['orderItems']);
        
        // Store order items for use after creation
        $this->orderItems = $orderItems;
        
        // Set required fields for recurring order templates
        $data['is_recurring'] = true;
        $data['status'] = 'template';
        $data['delivery_date'] = $data['recurring_start_date'] ?? now();
        $data['harvest_date'] = $data['delivery_date'];
        
        return $data;
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        // Create the order record
        $order = parent::handleRecordCreation($data);
        
        // Now attach order items to the new order
        if (isset($this->orderItems) && is_array($this->orderItems)) {
            foreach ($this->orderItems as $item) {
                // Skip items with null or empty item_id
                if (empty($item['item_id'])) {
                    continue;
                }
                
                $order->orderItems()->create([
                    'product_id' => $item['item_id'], // Map item_id to product_id
                    'price_variation_id' => $item['price_variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }
        
        return $order;
    }
}
