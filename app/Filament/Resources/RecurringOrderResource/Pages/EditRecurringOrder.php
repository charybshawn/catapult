<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Illuminate\Database\Eloquent\Model;

class EditRecurringOrder extends BaseEditRecord
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing order items for the form
        $order = $this->record;
        $orderItems = $order->orderItems()->with(['product', 'priceVariation'])->get();
        
        $data['orderItems'] = $orderItems->map(function ($item) {
            return [
                'item_id' => (string) $item->product_id, // Cast to string to match select options
                'price_variation_id' => $item->price_variation_id ? (string) $item->price_variation_id : null,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ];
        })->toArray();
        
        // Load virtual attributes for harvest_day and delivery_day
        $data['harvest_day'] = $order->harvest_day;
        $data['delivery_day'] = $order->delivery_day;
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extract order items data before saving the order
        $orderItems = $data['orderItems'] ?? [];
        unset($data['orderItems']);
        
        // Store order items for use after save
        $this->orderItems = $orderItems;
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update the order record
        $order = parent::handleRecordUpdate($record, $data);
        
        // Update order items
        if (isset($this->orderItems) && is_array($this->orderItems)) {
            // Delete existing order items
            $order->orderItems()->delete();
            
            // Create new order items
            foreach ($this->orderItems as $item) {
                // Skip items with null or empty item_id
                if (empty($item['item_id'])) {
                    continue;
                }
                
                $order->orderItems()->create([
                    'product_id' => $item['item_id'],
                    'price_variation_id' => $item['price_variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }
        
        return $order;
    }
}
