<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\HasWizard;
use Filament\Actions;
use App\Models\ItemPhoto;
use Filament\Notifications\Notification;

class EditItem extends EditRecord
{
    use HasWizard;
    
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSteps(): array
    {
        return $this->getResource()::getFormSchema($this);
    }

    /**
     * Handle setting a photo as default
     */
    public function setAsDefault(array $data): void
    {
        $photoId = $data['record'];
        $photo = ItemPhoto::find($photoId);
        
        if ($photo) {
            $photo->setAsDefault();
            
            Notification::make()
                ->title('Photo set as default')
                ->success()
                ->send();
        }
    }
    
    /**
     * Handle deleting a photo
     */
    public function deletePhoto(array $data): void
    {
        $photoId = $data['record'];
        $photo = ItemPhoto::find($photoId);
        
        if ($photo) {
            $wasDefault = $photo->is_default;
            $itemId = $photo->item_id;
            
            // Delete the photo
            $photo->delete();
            
            // If this was the default photo, try to set a new default
            if ($wasDefault) {
                $firstPhoto = $this->record->photos()->first();
                if ($firstPhoto) {
                    $firstPhoto->setAsDefault();
                }
            }
            
            Notification::make()
                ->title('Photo deleted')
                ->success()
                ->send();
        }
    }
} 