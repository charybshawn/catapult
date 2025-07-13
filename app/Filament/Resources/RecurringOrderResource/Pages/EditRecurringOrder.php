<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

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
        $orderItems = $order->orderItems()->get();
        
        if ($orderItems->count() > 0) {
            $data['orderItems'] = $orderItems->map(function ($item) {
                return [
                    'item_id' => (string) $item->product_id,
                    'price_variation_id' => $item->price_variation_id ? (string) $item->price_variation_id : null,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                ];
            })->toArray();
        }
        
        // Load virtual attributes for harvest_day and delivery_day
        $data['harvest_day'] = $order->harvest_day;
        $data['delivery_day'] = $order->delivery_day;
        
        return $data;
    }
}
