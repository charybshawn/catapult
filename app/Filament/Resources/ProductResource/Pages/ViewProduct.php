<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Forms\Form;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->schema([
                ...ProductResource::getFormSchema($this),
                ...ProductResource::getPanels(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 