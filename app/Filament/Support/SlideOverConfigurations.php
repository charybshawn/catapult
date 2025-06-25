<?php

namespace App\Filament\Support;

use Filament\Tables;

class SlideOverConfigurations
{
    /**
     * User/Customer resource configuration
     */
    public static function user(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View user details',
                'heading' => 'User Details',
                'description' => fn($record) => $record->email . ' • Member since ' . $record->created_at->format('M Y'),
                'icon' => 'heroicon-o-user-circle',
                'footerActions' => [
                    Tables\Actions\Action::make('send_email')
                        ->label('Send Email')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->action(fn($record) => redirect()->to('mailto:' . $record->email)),
                    Tables\Actions\Action::make('view_orders')
                        ->label('View Orders')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('gray')
                        ->url(fn($record) => route('filament.admin.resources.orders.index', ['tableFilters[user_id][value]' => $record->id]))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit user',
                'heading' => 'Edit User',
                'description' => fn($record) => 'Update information for ' . $record->name,
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New User',
                'tooltip' => 'Create a new user',
                'heading' => 'Create New User',
                'description' => 'Add a new user to the directory',
                'icon' => 'heroicon-o-user-plus',
            ],
        ];
    }

    /**
     * Product resource configuration
     */
    public static function product(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View product details',
                'heading' => 'Product Details',
                'description' => fn($record) => $record->description ?? 'Product information and specifications',
                'icon' => 'heroicon-o-shopping-bag',
                'footerActions' => [
                    Tables\Actions\Action::make('view_inventory')
                        ->label('Check Inventory')
                        ->icon('heroicon-o-cube')
                        ->color('primary')
                        ->url(fn($record) => route('filament.admin.resources.product-inventories.index', ['tableFilters[product_id][value]' => $record->id]))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('view_orders')
                        ->label('View Orders')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->url(fn($record) => route('filament.admin.resources.orders.index', ['tableFilters[product_id][value]' => $record->id]))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit product',
                'heading' => 'Edit Product',
                'description' => fn($record) => 'Update details for ' . $record->name,
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New Product',
                'tooltip' => 'Create a new product',
                'heading' => 'Create New Product',
                'description' => 'Add a new product to the catalog',
                'icon' => 'heroicon-o-plus-circle',
            ],
        ];
    }

    /**
     * Order resource configuration
     */
    public static function order(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View order details',
                'heading' => 'Order Details',
                'description' => fn($record) => 'Order #' . $record->id . ' • ' . $record->status,
                'icon' => 'heroicon-o-shopping-cart',
                'footerActions' => [
                    Tables\Actions\Action::make('print_order')
                        ->label('Print Order')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->action(fn($record) => redirect()->route('orders.print', $record)),
                    Tables\Actions\Action::make('view_customer')
                        ->label('View Customer')
                        ->icon('heroicon-o-user')
                        ->color('gray')
                        ->url(fn($record) => route('filament.admin.resources.users.view', $record->user_id))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit order',
                'heading' => 'Edit Order',
                'description' => fn($record) => 'Update order #' . $record->id,
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New Order',
                'tooltip' => 'Create a new order',
                'heading' => 'Create New Order',
                'description' => 'Process a new customer order',
                'icon' => 'heroicon-o-plus',
            ],
        ];
    }

    /**
     * Supplier resource configuration
     */
    public static function supplier(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View supplier details',
                'heading' => 'Supplier Details',
                'description' => fn($record) => $record->type . ' supplier • ' . ($record->is_active ? 'Active' : 'Inactive'),
                'icon' => 'heroicon-o-building-office',
                'footerActions' => [
                    Tables\Actions\Action::make('contact_supplier')
                        ->label('Contact')
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->action(fn($record) => redirect()->to('mailto:' . $record->contact_email))
                        ->visible(fn($record) => !empty($record->contact_email)),
                    Tables\Actions\Action::make('view_consumables')
                        ->label('View Inventory')
                        ->icon('heroicon-o-cube')
                        ->color('gray')
                        ->url(fn($record) => route('filament.admin.resources.consumables.index', ['tableFilters[supplier_id][value]' => $record->id]))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit supplier',
                'heading' => 'Edit Supplier',
                'description' => fn($record) => 'Update information for ' . $record->name,
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New Supplier',
                'tooltip' => 'Add a new supplier',
                'heading' => 'Create New Supplier',
                'description' => 'Add a new supplier to the system',
                'icon' => 'heroicon-o-building-office-2',
            ],
        ];
    }

    /**
     * Consumable/Inventory resource configuration
     */
    public static function consumable(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View inventory details',
                'heading' => 'Inventory Details',
                'description' => fn($record) => $record->type . ' • Current stock: ' . $record->current_stock . ' ' . $record->unit,
                'icon' => 'heroicon-o-cube',
                'footerActions' => [
                    Tables\Actions\Action::make('restock')
                        ->label('Restock')
                        ->icon('heroicon-o-arrow-up')
                        ->color('primary')
                        ->action(fn($record) => null), // Add restock logic
                    Tables\Actions\Action::make('view_supplier')
                        ->label('View Supplier')
                        ->icon('heroicon-o-building-office')
                        ->color('gray')
                        ->url(fn($record) => $record->supplier_id ? route('filament.admin.resources.suppliers.view', $record->supplier_id) : null)
                        ->visible(fn($record) => !empty($record->supplier_id))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit inventory item',
                'heading' => 'Edit Inventory',
                'description' => fn($record) => 'Update ' . $record->name . ' inventory',
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New Item',
                'tooltip' => 'Add new inventory item',
                'heading' => 'Create Inventory Item',
                'description' => 'Add a new item to inventory',
                'icon' => 'heroicon-o-plus',
            ],
        ];
    }

    /**
     * Recipe resource configuration
     */
    public static function recipe(): array
    {
        return [
            'viewConfig' => [
                'tooltip' => 'View recipe details',
                'heading' => 'Recipe Details',
                'description' => fn($record) => 'Growing instructions and schedule',
                'icon' => 'heroicon-o-beaker',
                'footerActions' => [
                    Tables\Actions\Action::make('start_crop')
                        ->label('Start Crop')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->url(fn($record) => route('filament.admin.resources.crops.create', ['recipe_id' => $record->id])),
                    Tables\Actions\Action::make('view_crops')
                        ->label('View Crops')
                        ->icon('heroicon-o-rectangle-group')
                        ->color('gray')
                        ->url(fn($record) => route('filament.admin.resources.crops.index', ['tableFilters[recipe_id][value]' => $record->id]))
                        ->openUrlInNewTab(),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit recipe',
                'heading' => 'Edit Recipe',
                'description' => fn($record) => 'Update growing instructions',
                'icon' => 'heroicon-o-pencil-square',
            ],
            'createConfig' => [
                'label' => 'New Recipe',
                'tooltip' => 'Create a new recipe',
                'heading' => 'Create New Recipe',
                'description' => 'Define new growing instructions',
                'icon' => 'heroicon-o-beaker',
            ],
        ];
    }
}