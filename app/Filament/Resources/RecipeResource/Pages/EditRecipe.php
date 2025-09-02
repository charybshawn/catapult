<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use Filament\Schemas\Schema;
use App\Filament\Resources\RecipeResource\Forms\RecipeForm;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Pages\Base\BaseEditRecord;
use App\Filament\Resources\RecipeResource;

class EditRecipe extends BaseEditRecord
{
    protected static string $resource = RecipeResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components(RecipeForm::schema())->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
