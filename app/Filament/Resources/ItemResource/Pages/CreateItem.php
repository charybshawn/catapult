<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Notifications\Notification;

class CreateItem extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = ItemResource::class;
    
    public $temporaryPhotos = [];
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getSteps(): array
    {
        return $this->getResource()::getFormSchema($this);
    }

    /**
     * Process the temporary photos
     */
    protected function processTemporaryPhotos(): void
    {
        $maxOrder = 0;
        
        // Process each uploaded photo
        foreach ($this->temporaryPhotos as $index => $path) {
            // Set the first one as default
            $isDefault = ($index === 0);
            
            $photo = $this->record->photos()->create([
                'photo' => $path,
                'is_default' => $isDefault,
                'order' => $maxOrder + $index + 1,
            ]);
            
            // If this is the default, ensure it's properly set
            if ($isDefault) {
                $photo->setAsDefault();
            }
        }
        
        // Clear the temporary photos
        $this->temporaryPhotos = [];
    }

    protected function afterCreate(): void
    {
        // Process any temporary photos that were uploaded
        if (!empty($this->temporaryPhotos)) {
            $this->processTemporaryPhotos();
        }
        
        Notification::make()
            ->title('Product created successfully')
            ->body('You can now add price variations for this product.')
            ->success()
            ->send();
    }
} 