<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;
use App\Models\RecipeOptimizedView;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListRecipes extends ListRecords
{
    protected static string $resource = RecipeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    
    /**
     * Get the query for the list page.
     * Use the optimized view for better performance.
     */
    public function getTableQuery(): ?Builder
    {
        return RecipeOptimizedView::query();
    }
    
    /**
     * Override the table configuration to handle bulk actions properly
     */
    public function table(Table $table): Table
    {
        return static::$resource::table($table)
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            // Get the IDs from the optimized view records
                            $ids = $records->pluck('id')->toArray();
                            
                            // Delete using the actual Recipe model
                            Recipe::whereIn('id', $ids)->delete();
                            
                            // Show success notification
                            $count = count($ids);
                            Notification::make()
                                ->title('Deleted')
                                ->body("Successfully deleted {$count} recipe(s).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                ])
            ]);
    }
}
