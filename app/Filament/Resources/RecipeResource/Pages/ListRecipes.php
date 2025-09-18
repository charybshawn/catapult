<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Resources\RecipeResource;
use App\Models\Recipe;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;

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
     * Use eager loading for better performance.
     */
    public function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return Recipe::query();
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
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            // Get the IDs from the optimized view records
                            $ids = $records->pluck('id')->toArray();
                            
                            // Delete using the actual Recipe model
                            Recipe::whereIn('id', $ids)->delete();
                            
                            // Show success notification
                            $count = count($ids);
                            \Filament\Notifications\Notification::make()
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
