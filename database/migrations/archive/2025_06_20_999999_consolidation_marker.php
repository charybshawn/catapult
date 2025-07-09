<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert marker into migrations table for all old migrations
        // This prevents old migrations from running again
        $oldMigrations = [
            '0001_01_01_000000_create_users_table',
            '0001_01_01_000001_create_cache_table',
            '0001_01_01_000002_create_jobs_table',
            '2024_08_15_000000_create_crop_alerts_table',
            '2025_03_15_055950_create_permission_tables',
            '2025_03_15_060211_create_suppliers_table',
            '2025_03_15_060212_create_seed_varieties_table',
            '2025_03_15_060214_create_recipes_table',
            '2025_03_15_060215_create_recipe_stages_table',
            '2025_03_15_060305_create_recipe_watering_schedule_table',
            '2025_03_15_060319_create_recipe_mixes_table',
            '2025_03_15_060335_create_crops_table',
            '2025_03_15_060352_create_inventory_table',
            '2025_03_15_060353_create_consumables_table',
            '2025_03_15_060353_create_orders_table',
            '2025_03_15_060355_create_invoices_table',
            '2025_03_15_060355_create_payments_table',
            '2025_03_15_060355_create_settings_table',
            '2025_03_15_060355_drop_inventory_table',
            '2025_03_15_060527_fix_migration_order',
            '2025_03_15_063501_create_activity_log_table',
            '2025_03_15_070829_create_personal_access_tokens_table',
            '2025_03_21_002206_create_packaging_types_table',
            '2025_03_21_002211_create_order_packagings_table',
            '2025_03_21_031151_migrate_legacy_images_to_item_photos',
            '2025_03_21_032617_remove_code_field_from_items_table',
            '2025_03_23_192440_add_light_days_to_recipes_table',
            '2025_03_25_235525_create_task_schedules_table',
            '2025_03_25_235534_create_notification_settings_table',
            '2025_03_26_010126_update_packaging_types_add_volume_field',
            '2025_03_26_010933_remove_capacity_grams_from_packaging_types',
            '2025_03_26_045009_add_soil_consumable_id_to_recipes_table',
            '2025_04_09_020444_add_stage_timestamps_to_crops_table',
            '2025_04_09_045210_create_tasks_table',
            '2025_04_17_185454_add_packaging_type_foreign_key_to_consumables',
            '2025_04_17_234148_update_consumable_unit_types',
            '2025_04_17_234403_update_lot_no_to_uppercase',
            '2025_04_18_003016_add_units_quantity_to_consumables_table',
            '2025_04_18_003759_update_consumable_unit_types_to_simpler_values',
            '2025_04_18_010330_add_consumed_quantity_to_consumables_table',
            '2025_04_18_014631_update_consumables_decimal_precision',
            '2025_04_18_025334_update_clamshell_packaging_types_volume',
            '2025_04_18_034705_add_watering_method_to_recipe_watering_schedule',
            '2025_04_18_042544_rename_seed_soak_days_to_hours_in_recipes_table',
            '2025_04_18_054155_fix_recipe_seed_variety_relationship',
            '2025_04_18_100000_change_seed_soak_days_to_decimal',
            '2025_04_19_000000_drop_recipe_mixes_table',
            '2025_04_19_031951_remove_notes_from_recipes',
            '2025_04_19_035640_add_growth_phase_notes_columns_to_recipes_table',
            '2025_04_19_041217_add_seed_variety_id_to_consumables',
            '2025_04_19_043838_update_consumed_quantity_default_on_consumables',
            '2025_04_19_044201_update_total_quantity_default_on_consumables',
            '2025_04_19_045350_update_consumables_table_structure',
            '2025_04_19_045809_add_missing_columns_to_seed_varieties',
            '2025_04_19_050518_update_crops_recipe_foreign_key',
            '2025_04_19_052750_add_crop_type_to_seed_varieties_table',
            '2025_05_01_133249_add_time_to_next_stage_minutes_to_crops_table',
            '2025_05_01_143431_add_stage_age_minutes_to_crops_table',
            '2025_05_01_144928_add_total_age_minutes_to_crops_table',
            '2025_05_02_165743_update_time_to_next_stage_minutes_column_type',
            '2025_05_02_165851_update_stage_age_minutes_column_type',
            '2025_05_02_165855_update_total_age_minutes_column_type',
            '2025_05_02_205557_create_crop_batches_view',
            '2025_05_03_000000_add_calculated_columns_to_crops_table',
            '2025_05_03_222337_add_suspend_watering_to_recipes_table',
            '2025_05_03_222805_remove_stage_notes_from_recipes_table',
            '2025_05_03_222911_rename_suspend_watering_hours_column_in_recipes_table',
            '2025_05_03_224138_create_crop_tasks_table',
            '2025_05_03_224935_create_notifications_table',
            '2025_05_07_094527_create_products_table',
            '2025_05_07_094528_create_price_variations_for_existing_products',
            '2025_05_07_094529_create_product_photos_table',
            '2025_05_07_094530_remove_caption_from_product_photos',
            '2025_05_09_000000_update_stage_age_minutes_column_type',
            '2025_05_20_201327_add_indexes_for_optimization',
            '2025_05_26_162845_create_seed_cultivars_table',
            '2025_05_26_162849_create_seed_entries_table',
            '2025_05_26_162852_create_seed_variations_table',
            '2025_05_26_162855_create_seed_price_history_table',
            '2025_05_26_162859_create_seed_scrape_uploads_table',
            '2025_05_26_162902_add_consumable_id_to_seed_variations',
            '2025_06_03_100000_placeholder_notes_column_decision',
            '2025_06_03_141432_add_missing_foreign_key_constraints',
            '2025_06_03_141453_add_critical_performance_indexes',
            '2025_06_03_213058_add_recurring_order_support_to_orders_table',
            '2025_06_03_220125_create_product_mixes_table',
            '2025_06_03_220129_create_product_mix_components_table',
            '2025_06_03_223329_add_packaging_type_to_price_variations_table',
            '2025_06_03_223734_add_fill_weight_to_price_variations_table',
            '2025_06_03_224520_add_active_column_to_products_table',
            '2025_06_03_224602_make_sku_nullable_in_products_table',
            '2025_06_04_072532_add_customer_type_to_users_table',
            '2025_06_04_073839_update_orders_status_enum_values',
            '2025_06_04_075015_update_invoice_foreign_key_to_cascade_on_delete',
            '2025_06_04_075517_add_price_variation_id_to_order_products_table',
            '2025_06_04_083155_add_preferences_to_users_table',
            '2025_06_04_090627_remove_preferences_from_users_table',
            '2025_06_04_100000_migrate_recipes_to_seed_cultivar',
            '2025_06_04_100001_add_seed_variety_fields_to_seed_cultivars',
            '2025_06_04_100002_remove_seed_variety_id_from_consumables',
            '2025_06_04_100004_update_product_mix_components_to_seed_cultivar',
            '2025_06_04_100005_drop_seed_varieties_table',
            '2025_06_05_075524_simplify_seed_structure_add_names_to_entries',
            '2025_06_05_075648_fix_common_names_in_seed_entries',
            '2025_06_05_085532_make_seed_cultivar_id_nullable_in_seed_entries',
            '2025_06_05_193018_add_cataloged_at_to_seed_entries_table',
            '2025_06_05_193715_create_supplier_source_mappings_table',
            '2025_06_08_092642_remove_cataloged_at_from_seed_entries_table',
            '2025_06_09_062308_add_seed_entry_id_to_consumables_table',
            '2025_06_09_063844_add_is_active_to_seed_entries_table',
            '2025_06_09_064442_fix_recipes_seed_entry_foreign_key',
            '2025_06_09_065222_rename_seed_cultivar_id_to_seed_entry_id_in_recipes',
            '2025_06_09_065622_rename_seed_cultivar_id_to_seed_entry_id_in_product_mix_components',
            '2025_06_09_111847_add_failed_entries_to_seed_scrape_uploads_table',
            '2025_06_09_130054_make_current_price_nullable_in_seed_variations_table',
            '2025_06_09_155051_make_cost_per_unit_nullable_in_consumables_table',
            '2025_06_09_174941_make_harvest_date_nullable_in_orders_table',
            '2025_06_09_180239_add_order_classification_to_orders_table',
            '2025_06_09_180649_add_consolidated_invoice_support_to_invoices_table',
            '2025_06_09_195832_make_order_id_nullable_in_invoices_table',
            '2025_06_09_222238_add_billing_period_to_orders_table',
            '2025_06_09_233139_create_crop_plans_table',
            '2025_06_09_233223_add_crop_plan_id_to_crops_table',
            '2025_06_11_133418_create_product_inventory_system',
            '2025_06_11_151000_add_seed_entry_to_products_if_not_exists',
            '2025_06_11_210426_create_master_seed_catalog_table',
            '2025_06_11_210429_create_master_cultivars_table',
            '2025_06_11_221240_change_scientific_name_to_json_in_master_seed_catalog',
            '2025_06_11_225657_add_master_seed_catalog_id_to_consumables_table',
            '2025_06_11_230351_add_cultivars_column_to_master_seed_catalog_table',
            '2025_06_11_230435_rename_scientific_name_to_cultivars_in_master_seed_catalog',
            '2025_06_11_231627_add_master_seed_catalog_id_to_products_table',
            '2025_06_11_231700_replace_seed_entry_id_with_master_seed_catalog_id_in_products',
            '2025_06_12_085856_add_template_id_to_price_variations_table',
            '2025_06_12_184326_add_soft_deletes_to_consumables_table',
            '2025_06_12_200016_add_master_cultivar_id_to_consumables_table',
            '2025_06_12_201000_populate_master_cultivar_id_in_consumables',
            '2025_06_12_204424_make_seed_entry_id_nullable_in_product_mix_components',
            '2025_06_12_204633_remove_seed_entry_id_from_product_mix_components',
            '2025_06_12_205054_fix_product_mix_components_unique_constraints',
            '2025_06_12_add_pricing_unit_to_price_variations',
            '2025_06_13_161917_add_wholesale_discount_percentage_to_products_table',
            '2025_06_13_163543_update_existing_products_wholesale_discount_default',
            '2025_06_13_180604_add_unique_index_to_product_name',
            '2025_06_13_214716_add_cultivar_to_consumables_table',
            '2025_06_13_214757_populate_cultivar_in_consumables_table',
            '2025_06_13_215428_update_percentage_precision_in_product_mix_components',
            '2025_06_13_add_cascade_delete_to_product_relations',
            '2025_06_13_remove_batch_number_from_product_inventories',
            '2025_06_18_112138_create_harvests_table',
            '2025_06_18_122409_add_buffer_percentage_to_recipes_table',
            '2025_06_18_142749_add_packed_status_to_orders_enum',
            '2025_06_18_180519_separate_order_statuses_into_distinct_columns',
            '2025_06_18_182436_create_data_exports_table',
            '2025_06_19_073000_drop_data_exports_table',
            '2025_06_19_111313_insert_harvest_data',
            '2025_06_19_141353_add_wholesale_discount_percentage_to_users_table',
            '2025_06_19_180500_make_customer_type_nullable_in_orders_table',
            '2025_06_19_181751_add_b2b_to_order_type_enum',
            '2025_08_15_000001_add_days_to_maturity_to_recipes_table',
        ];

        // Only insert if migrations table exists and is empty or doesn't have these migrations
        if (Schema::hasTable('migrations')) {
            $existingMigrations = DB::table('migrations')->pluck('migration')->toArray();
            $migrationsToInsert = [];
            
            foreach ($oldMigrations as $migration) {
                if (!in_array($migration, $existingMigrations)) {
                    $migrationsToInsert[] = [
                        'migration' => $migration,
                        'batch' => 0
                    ];
                }
            }
            
            if (!empty($migrationsToInsert)) {
                DB::table('migrations')->insert($migrationsToInsert);
            }
        }

        // Run any necessary data migrations
        $this->runDataMigrations();
    }

    /**
     * Run data migrations that were previously in separate files
     */
    protected function runDataMigrations(): void
    {
        // Update lot numbers to uppercase
        if (Schema::hasTable('consumables')) {
            DB::table('consumables')
                ->whereNotNull('lot_no')
                ->update(['lot_no' => DB::raw('UPPER(lot_no)')]);
        }

        // Update clamshell packaging types volume
        if (Schema::hasTable('packaging_types')) {
            DB::table('packaging_types')
                ->where('name', 'like', '%clamshell%')
                ->where(function ($query) {
                    $query->whereNull('volume')
                        ->orWhere('volume', 0);
                })
                ->update(['volume' => 113.4]);
        }

        // Insert harvest data if table is empty
        if (Schema::hasTable('harvests') && DB::table('harvests')->count() === 0 && DB::table('users')->count() > 0) {
            $userId = DB::table('users')->first()->id;
            
            // Check if master cultivars exist
            $cultivars = DB::table('master_cultivars')
                ->join('master_seed_catalog', 'master_cultivars.master_seed_catalog_id', '=', 'master_seed_catalog.id')
                ->pluck('master_cultivars.id', 'master_seed_catalog.common_name')
                ->toArray();
            
            if (!empty($cultivars)) {
                $harvestData = [
                    ['name' => 'Arugula', 'date' => '2025-06-08', 'weight' => 368.5, 'trays' => 4],
                    ['name' => 'Mustard', 'date' => '2025-06-08', 'weight' => 397.7, 'trays' => 4],
                    ['name' => 'Arugula', 'date' => '2025-06-11', 'weight' => 263.6, 'trays' => 3],
                    ['name' => 'Kale', 'date' => '2025-06-11', 'weight' => 154.2, 'trays' => 2],
                    ['name' => 'Broccoli', 'date' => '2025-06-13', 'weight' => 336.8, 'trays' => 4],
                    ['name' => 'Mustard', 'date' => '2025-06-13', 'weight' => 440.0, 'trays' => 4],
                ];
                
                $harvestsToInsert = [];
                foreach ($harvestData as $harvest) {
                    if (isset($cultivars[$harvest['name']])) {
                        $harvestsToInsert[] = [
                            'master_cultivar_id' => $cultivars[$harvest['name']],
                            'user_id' => $userId,
                            'harvest_date' => $harvest['date'],
                            'total_weight_grams' => $harvest['weight'],
                            'tray_count' => $harvest['trays'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
                
                if (!empty($harvestsToInsert)) {
                    DB::table('harvests')->insert($harvestsToInsert);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the marker migrations
        $oldMigrations = [
            '0001_01_01_000000_create_users_table',
            // ... (all migrations listed above)
        ];
        
        DB::table('migrations')->whereIn('migration', $oldMigrations)->delete();
    }
};