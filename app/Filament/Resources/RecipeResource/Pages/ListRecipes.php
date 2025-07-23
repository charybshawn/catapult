<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\RecipeOptimizedView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecipes extends ListRecords
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    /**
     * Get the query for the list page.
     * Use the optimized view for better performance.
     */
    public function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return RecipeOptimizedView::query();
    }
}
