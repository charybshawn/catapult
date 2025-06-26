<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Pages\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends BaseCreateRecord
{
    protected static string $resource = OrderResource::class;
    
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract order items data before creating the order
        $orderItems = $data['orderItems'] ?? [];
        unset($data['orderItems']);
        
        // Store order items for use after creation
        $this->orderItems = $orderItems;
        
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