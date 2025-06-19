<?php

namespace App\Services\ImportExport;

class ResourceDefinitions
{
    /**
     * Define the tables and their dependencies for each resource
     * 
     * @return array
     */
    public static function getResourceDependencies(): array
    {
        return [
            'orders' => [
                'tables' => [
                    'orders' => ['primary' => true],
                    'order_products' => ['foreign_key' => 'order_id'],
                    'order_packagings' => ['foreign_key' => 'order_id'],
                    'crops' => ['foreign_key' => 'order_id'],
                    'crop_plans' => ['foreign_key' => 'order_id'],
                    'payments' => ['foreign_key' => 'order_id'],
                    'invoices' => ['foreign_key' => 'order_id'],
                ],
                'related_lookups' => [
                    'users' => ['type' => 'lookup', 'referenced_by' => 'orders.user_id'],
                    'products' => ['type' => 'lookup', 'referenced_by' => 'order_products.product_id'],
                    'packaging_types' => ['type' => 'lookup', 'referenced_by' => 'order_packagings.packaging_type_id'],
                    'recipes' => ['type' => 'lookup', 'referenced_by' => 'crops.recipe_id'],
                ]
            ],
            
            'products' => [
                'tables' => [
                    'products' => ['primary' => true],
                    'price_variations' => ['foreign_key' => 'product_id'],
                    'product_photos' => ['foreign_key' => 'product_id'],
                    'product_inventories' => ['foreign_key' => 'product_id'],
                    'inventory_transactions' => ['foreign_key' => 'product_id'],
                    'inventory_reservations' => ['foreign_key' => 'product_id'],
                    'product_mix_components' => ['foreign_key' => 'product_mix_id', 'condition' => 'products.product_mix_id IS NOT NULL'],
                ],
                'related_lookups' => [
                    'categories' => ['type' => 'lookup', 'referenced_by' => 'products.category_id'],
                    'product_mixes' => ['type' => 'lookup', 'referenced_by' => 'products.product_mix_id'],
                    'master_seed_catalog' => ['type' => 'lookup', 'referenced_by' => 'products.master_seed_catalog_id'],
                    'master_cultivars' => ['type' => 'lookup', 'referenced_by' => 'products.master_seed_catalog_id'],
                ]
            ],
            
            'recipes' => [
                'tables' => [
                    'recipes' => ['primary' => true],
                    'recipe_stages' => ['foreign_key' => 'recipe_id'],
                    'recipe_watering_schedule' => ['foreign_key' => 'recipe_id'],
                ],
                'related_lookups' => [
                    'seed_entries' => ['type' => 'lookup', 'referenced_by' => 'recipes.seed_entry_id'],
                    'master_cultivars' => ['type' => 'lookup', 'referenced_by' => 'recipes.master_cultivar_id'],
                ]
            ],
            
            'consumables' => [
                'tables' => [
                    'consumables' => ['primary' => true],
                    'seed_variations' => ['foreign_key' => 'consumable_id'],
                ],
                'related_lookups' => [
                    'suppliers' => ['type' => 'lookup', 'referenced_by' => 'consumables.supplier_id'],
                    'packaging_types' => ['type' => 'lookup', 'referenced_by' => 'consumables.packaging_type_id'],
                    'master_seed_catalog' => ['type' => 'lookup', 'referenced_by' => 'consumables.master_seed_catalog_id'],
                ]
            ],
            
            'users' => [
                'tables' => [
                    'users' => ['primary' => true],
                    'orders' => ['foreign_key' => 'user_id'],
                    'payments' => ['foreign_key' => 'user_id'],
                    'invoices' => ['foreign_key' => 'user_id'],
                    'notification_settings' => ['foreign_key' => 'user_id'],
                ],
                'related_lookups' => []
            ],
            
            'master_seed_catalog' => [
                'tables' => [
                    'master_seed_catalog' => ['primary' => true],
                    'master_cultivars' => ['foreign_key' => 'master_seed_catalog_id'],
                    'seed_entries' => ['foreign_key' => 'master_seed_catalog_id'],
                    'consumables' => ['foreign_key' => 'master_seed_catalog_id', 'condition' => "type = 'seed'"],
                    'products' => ['foreign_key' => 'master_seed_catalog_id'],
                ],
                'related_lookups' => []
            ],
            
            'suppliers' => [
                'tables' => [
                    'suppliers' => ['primary' => true],
                    'supplier_source_mappings' => ['foreign_key' => 'supplier_id'],
                    'consumables' => ['foreign_key' => 'supplier_id'],
                ],
                'related_lookups' => []
            ],
            
            'invoices' => [
                'tables' => [
                    'invoices' => ['primary' => true],
                    'payments' => ['foreign_key' => 'invoice_id'],
                ],
                'related_lookups' => [
                    'users' => ['type' => 'lookup', 'referenced_by' => 'invoices.user_id'],
                    'orders' => ['type' => 'lookup', 'referenced_by' => 'invoices.order_id'],
                ]
            ],
            
            'harvests' => [
                'tables' => [
                    'harvests' => ['primary' => true],
                ],
                'related_lookups' => [
                    'master_cultivars' => ['type' => 'lookup', 'referenced_by' => 'harvests.master_cultivar_id'],
                    'users' => ['type' => 'lookup', 'referenced_by' => 'harvests.user_id'],
                ]
            ]
        ];
    }
    
    /**
     * Get the export order for a resource (handles dependencies)
     */
    public static function getExportOrder(string $resource): array
    {
        $definitions = self::getResourceDependencies();
        
        if (!isset($definitions[$resource])) {
            throw new \Exception("Resource '{$resource}' not defined");
        }
        
        $definition = $definitions[$resource];
        $tables = [];
        
        // First, add lookup tables
        foreach ($definition['related_lookups'] as $table => $config) {
            $tables[] = $table;
        }
        
        // Then add primary table
        foreach ($definition['tables'] as $table => $config) {
            if ($config['primary'] ?? false) {
                $tables[] = $table;
                break;
            }
        }
        
        // Finally, add dependent tables
        foreach ($definition['tables'] as $table => $config) {
            if (!($config['primary'] ?? false) && !in_array($table, $tables)) {
                $tables[] = $table;
            }
        }
        
        return $tables;
    }
    
    /**
     * Get the import order for a resource (reverse of export)
     */
    public static function getImportOrder(string $resource): array
    {
        return array_reverse(self::getExportOrder($resource));
    }
}