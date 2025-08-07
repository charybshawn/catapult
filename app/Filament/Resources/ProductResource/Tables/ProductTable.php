<?php

namespace App\Filament\Resources\ProductResource\Tables;

use App\Models\Product;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ProductTable
{
    /**
     * Get table columns for ProductResource
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable(),
            Tables\Columns\ImageColumn::make('default_photo')
                ->label('Image')
                ->circular(),
            Tables\Columns\TextColumn::make('category.name')
                ->label('Category')
                ->sortable(),
            static::getVarietyTypeColumn(),
            Tables\Columns\IconColumn::make('active')
                ->boolean()
                ->sortable(),
            Tables\Columns\IconColumn::make('is_visible_in_store')
                ->label('In Store')
                ->boolean()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            static::getAvailablePackagingColumn(),
        ];
    }

    /**
     * Get table filters for ProductResource
     */
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('category')
                ->relationship('category', 'name'),
            static::getVarietyTypeFilter(),
            Tables\Filters\TernaryFilter::make('active'),
            Tables\Filters\TernaryFilter::make('is_visible_in_store')
                ->label('Visible in Store'),
        ];
    }

    /**
     * Get table actions for ProductResource
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View record'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit record'),
                static::getCloneAction(),
                static::getDeleteAction(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk actions for ProductResource
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                static::getDeleteBulkAction(),
                static::getActivateBulkAction(),
                static::getDeactivateBulkAction(),
                static::getShowInStoreBulkAction(),
                static::getHideFromStoreBulkAction(),
            ]),
        ];
    }

    /**
     * Get the variety type column
     */
    protected static function getVarietyTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('variety_type')
            ->label('Type')
            ->getStateUsing(function ($record): string {
                if ($record->master_seed_catalog_id) {
                    $catalog = $record->masterSeedCatalog;
                    if (!$catalog) {
                        return 'Single Variety';
                    }
                    
                    // Show cultivar name if available, otherwise just common name
                    if ($catalog->cultivar && $catalog->cultivar->cultivar_name) {
                        return $catalog->cultivar->cultivar_name;
                    }
                    
                    return $catalog->common_name;
                } elseif ($record->product_mix_id) {
                    return $record->productMix->name ?? 'Product Mix';
                }
                return 'No Variety';
            })
            ->searchable(false)
            ->sortable(false)
            ->toggleable();
    }

    /**
     * Get the available packaging column
     */
    protected static function getAvailablePackagingColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('available_packaging')
            ->label('Packaging')
            ->html()
            ->getStateUsing(function ($record): string {
                // Get only product-specific price variations with packaging
                $productPackaging = $record->priceVariations()
                    ->whereNotNull('packaging_type_id')
                    ->with('packagingType')
                    ->get()
                    ->pluck('packagingType.name')
                    ->unique();
                
                // Only show actual product packaging, not potential templates
                $packaging = $productPackaging;
                
                if ($packaging->isEmpty()) {
                    return '<span class="text-gray-400">No packaging</span>';
                }
                
                // Create badges for actual product packaging
                $badges = $packaging->map(function ($name) {
                    return '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">' . $name . '</span>';
                })->join(' ');
                
                return $badges;
            })
            ->searchable(false)
            ->sortable(false);
    }

    /**
     * Get the variety type filter
     */
    protected static function getVarietyTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('variety_type')
            ->label('Product Type')
            ->options([
                'single' => 'Single Variety',
                'mix' => 'Product Mix',
                'none' => 'No Variety Assigned',
            ])
            ->query(function (Builder $query, array $data): Builder {
                return match($data['value']) {
                    'single' => $query->whereNotNull('master_seed_catalog_id'),
                    'mix' => $query->whereNotNull('product_mix_id'),
                    'none' => $query->whereNull('master_seed_catalog_id')->whereNull('product_mix_id'),
                    default => $query,
                };
            });
    }

    /**
     * Get the clone action
     */
    protected static function getCloneAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('clone')
            ->label('Clone')
            ->icon('heroicon-o-document-duplicate')
            ->tooltip('Clone this product')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Clone Product')
            ->modalDescription('This will create a copy of the product with all its price variations and photos. Inventory will not be copied.')
            ->modalSubmitActionLabel('Clone Product')
            ->action(function (Product $record) {
                try {
                    $newProduct = app(\App\Actions\Product\CloneProductAction::class)->execute($record);
                    
                    Notification::make()
                        ->title('Product Cloned Successfully')
                        ->body("Created: {$newProduct->name}")
                        ->success()
                        ->send();
                        
                    // Redirect to the edit page of the new product
                    return redirect()->to(\App\Filament\Resources\ProductResource::getUrl('edit', ['record' => $newProduct]));
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Clone Failed')
                        ->body('Failed to clone product: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Get the delete action with validation
     */
    protected static function getDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->tooltip('Delete record')
            ->before(function (Product $record) {
                $deleteCheck = app(\App\Actions\Product\ValidateProductDeletionAction::class)->execute($record);
                
                if (!$deleteCheck['canDelete']) {
                    Notification::make()
                        ->title('Cannot Delete Product')
                        ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']))
                        ->danger()
                        ->send();
                    
                    // Cancel the deletion
                    return false;
                }
            });
    }

    /**
     * Get the delete bulk action with validation
     */
    protected static function getDeleteBulkAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->before(function ($records) {
                // Check each record for inventory
                foreach ($records as $record) {
                    $deleteCheck = app(\App\Actions\Product\ValidateProductDeletionAction::class)->execute($record);
                    
                    if (!$deleteCheck['canDelete']) {
                        Notification::make()
                            ->title('Cannot Delete Products')
                            ->body("Product '{$record->name}' cannot be deleted:\n" . implode("\n", $deleteCheck['errors']) . "\n\nPlease resolve issues for all selected products first.")
                            ->danger()
                            ->send();
                        
                        // Cancel the deletion
                        return false;
                    }
                }
            });
    }

    /**
     * Get the activate bulk action
     */
    protected static function getActivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->action(function ($records) {
                app(\App\Actions\Product\BulkUpdateProductStatusAction::class)->activate($records);
            })
            ->requiresConfirmation()
            ->color('success');
    }

    /**
     * Get the deactivate bulk action
     */
    protected static function getDeactivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(function ($records) {
                app(\App\Actions\Product\BulkUpdateProductStatusAction::class)->deactivate($records);
            })
            ->requiresConfirmation()
            ->color('danger');
    }

    /**
     * Get the show in store bulk action
     */
    protected static function getShowInStoreBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('show_in_store')
            ->label('Show in Store')
            ->icon('heroicon-o-eye')
            ->action(function ($records) {
                app(\App\Actions\Product\BulkUpdateProductStatusAction::class)->showInStore($records);
            });
    }

    /**
     * Get the hide from store bulk action
     */
    protected static function getHideFromStoreBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('hide_from_store')
            ->label('Hide from Store')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->action(function ($records) {
                app(\App\Actions\Product\BulkUpdateProductStatusAction::class)->hideFromStore($records);
            });
    }

    /**
     * Configure query modifications for the table
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'category',
            'masterSeedCatalog.cultivar',
            'productMix',
            'recipe',
            'priceVariations.packagingType'
        ]);
    }
}