<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecurringOrder extends EditRecord
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
        
        return $data;
    }
}
