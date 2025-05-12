<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('recipe_updates')
                ->label('Recipe Updates')
                ->icon('heroicon-o-arrow-path')
                ->url(fn (): string => static::getResource()::getUrl('recipe-updates')),
        ];
    }
} 