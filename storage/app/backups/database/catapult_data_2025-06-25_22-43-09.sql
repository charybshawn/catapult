-- MySQL dump 10.13  Distrib 9.3.0, for macos15.4 (arm64)
--
-- Host: 127.0.0.1    Database: catapult
-- ------------------------------------------------------
-- Server version	8.0.33

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `activity_log`
--

/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'default','created','App\\Models\\User','1','created',NULL,NULL,'{\"attributes\": {\"name\": \"Admin User\", \"email\": \"charybshawn@gmail.com\", \"phone\": \"250-515-4007\"}}',NULL,'2025-06-26 22:59:18','2025-06-26 22:59:18');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;

--
-- Dumping data for table `cache`
--

/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('catapult_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:1;',1750910994),('catapult_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1750910994;',1750910994),('catapult_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:5:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"manage products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"view products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"edit products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"delete products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"access filament\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:7:\"manager\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:4:\"user\";s:1:\"c\";s:3:\"web\";}}}',1750989561);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;

--
-- Dumping data for table `cache_locks`
--

/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;

--
-- Dumping data for table `categories`
--

/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;

--
-- Dumping data for table `consumables`
--

/*!40000 ALTER TABLE `consumables` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumables` ENABLE KEYS */;

--
-- Dumping data for table `crop_alerts`
--

/*!40000 ALTER TABLE `crop_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_alerts` ENABLE KEYS */;

--
-- Dumping data for table `crop_plans`
--

/*!40000 ALTER TABLE `crop_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_plans` ENABLE KEYS */;

--
-- Dumping data for table `crop_tasks`
--

/*!40000 ALTER TABLE `crop_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_tasks` ENABLE KEYS */;

--
-- Dumping data for table `crops`
--

/*!40000 ALTER TABLE `crops` DISABLE KEYS */;
/*!40000 ALTER TABLE `crops` ENABLE KEYS */;

--
-- Dumping data for table `customers`
--

/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;

--
-- Dumping data for table `failed_jobs`
--

/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;

--
-- Dumping data for table `harvests`
--

/*!40000 ALTER TABLE `harvests` DISABLE KEYS */;
/*!40000 ALTER TABLE `harvests` ENABLE KEYS */;

--
-- Dumping data for table `inventory_reservations`
--

/*!40000 ALTER TABLE `inventory_reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_reservations` ENABLE KEYS */;

--
-- Dumping data for table `inventory_transactions`
--

/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;

--
-- Dumping data for table `invoices`
--

/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;

--
-- Dumping data for table `job_batches`
--

/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;

--
-- Dumping data for table `jobs`
--

/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;

--
-- Dumping data for table `master_cultivars`
--

/*!40000 ALTER TABLE `master_cultivars` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_cultivars` ENABLE KEYS */;

--
-- Dumping data for table `master_seed_catalog`
--

/*!40000 ALTER TABLE `master_seed_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_seed_catalog` ENABLE KEYS */;

--
-- Dumping data for table `migrations`
--

/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2025_06_20_000001_create_users_table',1),(2,'2025_06_20_000002_create_cache_table',1),(3,'2025_06_20_000003_create_jobs_table',1),(4,'2025_06_20_000004_create_permission_tables',1),(5,'2025_06_20_000005_create_suppliers_table',1),(6,'2025_06_20_000006_create_inventory_tables',1),(7,'2025_06_20_000007_create_seed_catalog_tables',1),(8,'2025_06_20_000008_create_recipes_tables',1),(9,'2025_06_20_000009_create_orders_tables',1),(10,'2025_06_20_000010_create_products_tables',1),(11,'2025_06_20_000011_create_crops_tables',1),(12,'2025_06_20_000012_create_system_tables',1),(13,'2025_06_20_000013_create_product_inventory_tables',1),(14,'2025_06_20_000014_add_foreign_key_constraints',1),(15,'2025_06_20_144953_add_resource_type_to_task_schedules_table',1),(16,'2025_06_20_145646_add_missing_fields_to_users_table',1),(17,'2025_06_20_145717_add_missing_fields_to_orders_table',1),(18,'2025_06_20_145752_add_missing_fields_to_invoices_table',1),(19,'2025_06_20_145812_fix_seed_scrape_upload_column_names',1),(20,'2025_06_20_150240_add_initial_stock_to_consumables_table',1),(21,'2025_06_20_151052_fix_product_photos_table_columns',1),(22,'2025_06_20_151115_update_orders_status_enum',1),(23,'0001_01_01_000000_create_users_table',0),(24,'0001_01_01_000001_create_cache_table',0),(25,'0001_01_01_000002_create_jobs_table',0),(26,'2024_08_15_000000_create_crop_alerts_table',0),(27,'2025_03_15_055950_create_permission_tables',0),(28,'2025_03_15_060211_create_suppliers_table',0),(29,'2025_03_15_060212_create_seed_varieties_table',0),(30,'2025_03_15_060214_create_recipes_table',0),(31,'2025_03_15_060215_create_recipe_stages_table',0),(32,'2025_03_15_060305_create_recipe_watering_schedule_table',0),(33,'2025_03_15_060319_create_recipe_mixes_table',0),(34,'2025_03_15_060335_create_crops_table',0),(35,'2025_03_15_060352_create_inventory_table',0),(36,'2025_03_15_060353_create_consumables_table',0),(37,'2025_03_15_060353_create_orders_table',0),(38,'2025_03_15_060355_create_invoices_table',0),(39,'2025_03_15_060355_create_payments_table',0),(40,'2025_03_15_060355_create_settings_table',0),(41,'2025_03_15_060355_drop_inventory_table',0),(42,'2025_03_15_060527_fix_migration_order',0),(43,'2025_03_15_063501_create_activity_log_table',0),(44,'2025_03_15_070829_create_personal_access_tokens_table',0),(45,'2025_03_21_002206_create_packaging_types_table',0),(46,'2025_03_21_002211_create_order_packagings_table',0),(47,'2025_03_21_031151_migrate_legacy_images_to_item_photos',0),(48,'2025_03_21_032617_remove_code_field_from_items_table',0),(49,'2025_03_23_192440_add_light_days_to_recipes_table',0),(50,'2025_03_25_235525_create_task_schedules_table',0),(51,'2025_03_25_235534_create_notification_settings_table',0),(52,'2025_03_26_010126_update_packaging_types_add_volume_field',0),(53,'2025_03_26_010933_remove_capacity_grams_from_packaging_types',0),(54,'2025_03_26_045009_add_soil_consumable_id_to_recipes_table',0),(55,'2025_04_09_020444_add_stage_timestamps_to_crops_table',0),(56,'2025_04_09_045210_create_tasks_table',0),(57,'2025_04_17_185454_add_packaging_type_foreign_key_to_consumables',0),(58,'2025_04_17_234148_update_consumable_unit_types',0),(59,'2025_04_17_234403_update_lot_no_to_uppercase',0),(60,'2025_04_18_003016_add_units_quantity_to_consumables_table',0),(61,'2025_04_18_003759_update_consumable_unit_types_to_simpler_values',0),(62,'2025_04_18_010330_add_consumed_quantity_to_consumables_table',0),(63,'2025_04_18_014631_update_consumables_decimal_precision',0),(64,'2025_04_18_025334_update_clamshell_packaging_types_volume',0),(65,'2025_04_18_034705_add_watering_method_to_recipe_watering_schedule',0),(66,'2025_04_18_042544_rename_seed_soak_days_to_hours_in_recipes_table',0),(67,'2025_04_18_054155_fix_recipe_seed_variety_relationship',0),(68,'2025_04_18_100000_change_seed_soak_days_to_decimal',0),(69,'2025_04_19_000000_drop_recipe_mixes_table',0),(70,'2025_04_19_031951_remove_notes_from_recipes',0),(71,'2025_04_19_035640_add_growth_phase_notes_columns_to_recipes_table',0),(72,'2025_04_19_041217_add_seed_variety_id_to_consumables',0),(73,'2025_04_19_043838_update_consumed_quantity_default_on_consumables',0),(74,'2025_04_19_044201_update_total_quantity_default_on_consumables',0),(75,'2025_04_19_045350_update_consumables_table_structure',0),(76,'2025_04_19_045809_add_missing_columns_to_seed_varieties',0),(77,'2025_04_19_050518_update_crops_recipe_foreign_key',0),(78,'2025_04_19_052750_add_crop_type_to_seed_varieties_table',0),(79,'2025_05_01_133249_add_time_to_next_stage_minutes_to_crops_table',0),(80,'2025_05_01_143431_add_stage_age_minutes_to_crops_table',0),(81,'2025_05_01_144928_add_total_age_minutes_to_crops_table',0),(82,'2025_05_02_165743_update_time_to_next_stage_minutes_column_type',0),(83,'2025_05_02_165851_update_stage_age_minutes_column_type',0),(84,'2025_05_02_165855_update_total_age_minutes_column_type',0),(85,'2025_05_02_205557_create_crop_batches_view',0),(86,'2025_05_03_000000_add_calculated_columns_to_crops_table',0),(87,'2025_05_03_222337_add_suspend_watering_to_recipes_table',0),(88,'2025_05_03_222805_remove_stage_notes_from_recipes_table',0),(89,'2025_05_03_222911_rename_suspend_watering_hours_column_in_recipes_table',0),(90,'2025_05_03_224138_create_crop_tasks_table',0),(91,'2025_05_03_224935_create_notifications_table',0),(92,'2025_05_07_094527_create_products_table',0),(93,'2025_05_07_094528_create_price_variations_for_existing_products',0),(94,'2025_05_07_094529_create_product_photos_table',0),(95,'2025_05_07_094530_remove_caption_from_product_photos',0),(96,'2025_05_09_000000_update_stage_age_minutes_column_type',0),(97,'2025_05_20_201327_add_indexes_for_optimization',0),(98,'2025_05_26_162845_create_seed_cultivars_table',0),(99,'2025_05_26_162849_create_seed_entries_table',0),(100,'2025_05_26_162852_create_seed_variations_table',0),(101,'2025_05_26_162855_create_seed_price_history_table',0),(102,'2025_05_26_162859_create_seed_scrape_uploads_table',0),(103,'2025_05_26_162902_add_consumable_id_to_seed_variations',0),(104,'2025_06_03_100000_placeholder_notes_column_decision',0),(105,'2025_06_03_141432_add_missing_foreign_key_constraints',0),(106,'2025_06_03_141453_add_critical_performance_indexes',0),(107,'2025_06_03_213058_add_recurring_order_support_to_orders_table',0),(108,'2025_06_03_220125_create_product_mixes_table',0),(109,'2025_06_03_220129_create_product_mix_components_table',0),(110,'2025_06_03_223329_add_packaging_type_to_price_variations_table',0),(111,'2025_06_03_223734_add_fill_weight_to_price_variations_table',0),(112,'2025_06_03_224520_add_active_column_to_products_table',0),(113,'2025_06_03_224602_make_sku_nullable_in_products_table',0),(114,'2025_06_04_072532_add_customer_type_to_users_table',0),(115,'2025_06_04_073839_update_orders_status_enum_values',0),(116,'2025_06_04_075015_update_invoice_foreign_key_to_cascade_on_delete',0),(117,'2025_06_04_075517_add_price_variation_id_to_order_products_table',0),(118,'2025_06_04_083155_add_preferences_to_users_table',0),(119,'2025_06_04_090627_remove_preferences_from_users_table',0),(120,'2025_06_04_100000_migrate_recipes_to_seed_cultivar',0),(121,'2025_06_04_100001_add_seed_variety_fields_to_seed_cultivars',0),(122,'2025_06_04_100002_remove_seed_variety_id_from_consumables',0),(123,'2025_06_04_100004_update_product_mix_components_to_seed_cultivar',0),(124,'2025_06_04_100005_drop_seed_varieties_table',0),(125,'2025_06_05_075524_simplify_seed_structure_add_names_to_entries',0),(126,'2025_06_05_075648_fix_common_names_in_seed_entries',0),(127,'2025_06_05_085532_make_seed_cultivar_id_nullable_in_seed_entries',0),(128,'2025_06_05_193018_add_cataloged_at_to_seed_entries_table',0),(129,'2025_06_05_193715_create_supplier_source_mappings_table',0),(130,'2025_06_08_092642_remove_cataloged_at_from_seed_entries_table',0),(131,'2025_06_09_062308_add_seed_entry_id_to_consumables_table',0),(132,'2025_06_09_063844_add_is_active_to_seed_entries_table',0),(133,'2025_06_09_064442_fix_recipes_seed_entry_foreign_key',0),(134,'2025_06_09_065222_rename_seed_cultivar_id_to_seed_entry_id_in_recipes',0),(135,'2025_06_09_065622_rename_seed_cultivar_id_to_seed_entry_id_in_product_mix_components',0),(136,'2025_06_09_111847_add_failed_entries_to_seed_scrape_uploads_table',0),(137,'2025_06_09_130054_make_current_price_nullable_in_seed_variations_table',0),(138,'2025_06_09_155051_make_cost_per_unit_nullable_in_consumables_table',0),(139,'2025_06_09_174941_make_harvest_date_nullable_in_orders_table',0),(140,'2025_06_09_180239_add_order_classification_to_orders_table',0),(141,'2025_06_09_180649_add_consolidated_invoice_support_to_invoices_table',0),(142,'2025_06_09_195832_make_order_id_nullable_in_invoices_table',0),(143,'2025_06_09_222238_add_billing_period_to_orders_table',0),(144,'2025_06_09_233139_create_crop_plans_table',0),(145,'2025_06_09_233223_add_crop_plan_id_to_crops_table',0),(146,'2025_06_11_133418_create_product_inventory_system',0),(147,'2025_06_11_151000_add_seed_entry_to_products_if_not_exists',0),(148,'2025_06_11_210426_create_master_seed_catalog_table',0),(149,'2025_06_11_210429_create_master_cultivars_table',0),(150,'2025_06_11_221240_change_scientific_name_to_json_in_master_seed_catalog',0),(151,'2025_06_11_225657_add_master_seed_catalog_id_to_consumables_table',0),(152,'2025_06_11_230351_add_cultivars_column_to_master_seed_catalog_table',0),(153,'2025_06_11_230435_rename_scientific_name_to_cultivars_in_master_seed_catalog',0),(154,'2025_06_11_231627_add_master_seed_catalog_id_to_products_table',0),(155,'2025_06_11_231700_replace_seed_entry_id_with_master_seed_catalog_id_in_products',0),(156,'2025_06_12_085856_add_template_id_to_price_variations_table',0),(157,'2025_06_12_184326_add_soft_deletes_to_consumables_table',0),(158,'2025_06_12_200016_add_master_cultivar_id_to_consumables_table',0),(159,'2025_06_12_201000_populate_master_cultivar_id_in_consumables',0),(160,'2025_06_12_204424_make_seed_entry_id_nullable_in_product_mix_components',0),(161,'2025_06_12_204633_remove_seed_entry_id_from_product_mix_components',0),(162,'2025_06_12_205054_fix_product_mix_components_unique_constraints',0),(163,'2025_06_12_add_pricing_unit_to_price_variations',0),(164,'2025_06_13_161917_add_wholesale_discount_percentage_to_products_table',0),(165,'2025_06_13_163543_update_existing_products_wholesale_discount_default',0),(166,'2025_06_13_180604_add_unique_index_to_product_name',0),(167,'2025_06_13_214716_add_cultivar_to_consumables_table',0),(168,'2025_06_13_214757_populate_cultivar_in_consumables_table',0),(169,'2025_06_13_215428_update_percentage_precision_in_product_mix_components',0),(170,'2025_06_13_add_cascade_delete_to_product_relations',0),(171,'2025_06_13_remove_batch_number_from_product_inventories',0),(172,'2025_06_18_112138_create_harvests_table',0),(173,'2025_06_18_122409_add_buffer_percentage_to_recipes_table',0),(174,'2025_06_18_142749_add_packed_status_to_orders_enum',0),(175,'2025_06_18_180519_separate_order_statuses_into_distinct_columns',0),(176,'2025_06_18_182436_create_data_exports_table',0),(177,'2025_06_19_073000_drop_data_exports_table',0),(178,'2025_06_19_111313_insert_harvest_data',0),(179,'2025_06_19_141353_add_wholesale_discount_percentage_to_users_table',0),(180,'2025_06_19_180500_make_customer_type_nullable_in_orders_table',0),(181,'2025_06_19_181751_add_b2b_to_order_type_enum',0),(182,'2025_08_15_000001_add_days_to_maturity_to_recipes_table',0),(183,'2025_06_20_999999_consolidation_marker',1),(184,'2025_06_24_080303_add_source_url_to_supplier_source_mappings_table',1),(185,'2025_06_24_091546_create_time_cards_table',1),(186,'2025_06_24_092916_add_review_fields_to_time_cards_table',1),(187,'2025_06_24_104741_add_other_type_to_suppliers_table',1),(188,'2025_06_24_110323_add_packaging_type_to_suppliers_table',1),(189,'2025_06_24_145952_create_task_types_table',1),(190,'2025_06_24_150348_create_time_card_tasks_table',1),(191,'2025_06_24_213542_make_password_nullable_in_users_table',1),(192,'2025_06_24_222528_add_group_to_settings_table',1),(193,'2025_06_24_231307_create_customers_table',1),(194,'2025_06_24_232142_update_customers_table_for_canadian_format',1),(195,'2025_06_24_232250_add_country_to_customers_table',1),(196,'2025_06_24_233345_add_customer_id_to_orders_table',1),(197,'2025_06_24_233448_add_customer_id_to_invoices_table',1),(198,'2025_06_25_091104_fix_product_inventory_price_variation_mismatch',1),(199,'2025_06_25_092743_update_packaging_types_columns',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;

--
-- Dumping data for table `model_has_permissions`
--

/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;

--
-- Dumping data for table `model_has_roles`
--

/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;

--
-- Dumping data for table `notification_settings`
--

/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;

--
-- Dumping data for table `notifications`
--

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;

--
-- Dumping data for table `order_packagings`
--

/*!40000 ALTER TABLE `order_packagings` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_packagings` ENABLE KEYS */;

--
-- Dumping data for table `order_products`
--

/*!40000 ALTER TABLE `order_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_products` ENABLE KEYS */;

--
-- Dumping data for table `orders`
--

/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;

--
-- Dumping data for table `packaging_types`
--

/*!40000 ALTER TABLE `packaging_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `packaging_types` ENABLE KEYS */;

--
-- Dumping data for table `password_reset_tokens`
--

/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;

--
-- Dumping data for table `payments`
--

/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;

--
-- Dumping data for table `permissions`
--

/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'manage products','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(2,'view products','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(3,'edit products','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(4,'delete products','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(5,'access filament','web','2025-06-26 22:58:51','2025-06-26 22:58:51');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;

--
-- Dumping data for table `personal_access_tokens`
--

/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;

--
-- Dumping data for table `price_variations`
--

/*!40000 ALTER TABLE `price_variations` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_variations` ENABLE KEYS */;

--
-- Dumping data for table `product_inventories`
--

/*!40000 ALTER TABLE `product_inventories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_inventories` ENABLE KEYS */;

--
-- Dumping data for table `product_mix_components`
--

/*!40000 ALTER TABLE `product_mix_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_mix_components` ENABLE KEYS */;

--
-- Dumping data for table `product_mixes`
--

/*!40000 ALTER TABLE `product_mixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_mixes` ENABLE KEYS */;

--
-- Dumping data for table `product_photos`
--

/*!40000 ALTER TABLE `product_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_photos` ENABLE KEYS */;

--
-- Dumping data for table `products`
--

/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;

--
-- Dumping data for table `recipe_stages`
--

/*!40000 ALTER TABLE `recipe_stages` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_stages` ENABLE KEYS */;

--
-- Dumping data for table `recipe_watering_schedule`
--

/*!40000 ALTER TABLE `recipe_watering_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_watering_schedule` ENABLE KEYS */;

--
-- Dumping data for table `recipes`
--

/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;

--
-- Dumping data for table `role_has_permissions`
--

/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(2,2),(3,2),(5,2),(2,3);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;

--
-- Dumping data for table `roles`
--

/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(2,'manager','web','2025-06-26 22:58:51','2025-06-26 22:58:51'),(3,'user','web','2025-06-26 22:58:51','2025-06-26 22:58:51');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;

--
-- Dumping data for table `seed_cultivars`
--

/*!40000 ALTER TABLE `seed_cultivars` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_cultivars` ENABLE KEYS */;

--
-- Dumping data for table `seed_entries`
--

/*!40000 ALTER TABLE `seed_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_entries` ENABLE KEYS */;

--
-- Dumping data for table `seed_price_history`
--

/*!40000 ALTER TABLE `seed_price_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_price_history` ENABLE KEYS */;

--
-- Dumping data for table `seed_scrape_uploads`
--

/*!40000 ALTER TABLE `seed_scrape_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_scrape_uploads` ENABLE KEYS */;

--
-- Dumping data for table `seed_variations`
--

/*!40000 ALTER TABLE `seed_variations` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_variations` ENABLE KEYS */;

--
-- Dumping data for table `sessions`
--

/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('q0tXvVlNvtlpFtzjFrdzQP3zpYWMon5mTeZm23OZ',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','YTo4OntzOjY6Il90b2tlbiI7czo0MDoiSHJnaWRxQnFBb0lhd3RZSGZWUlJJZTltMmljOEF1aUp2WXp5U2ptUiI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ0OiJodHRwczovL2NhdGFwdWx0LnRlc3QvYWRtaW4vZGF0YWJhc2UtY29uc29sZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNDoianVzdF9sb2dnZWRfaW4iO2I6MTtzOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJEczb25YSmt1RXdUR3NBUGpVMHZqaS4zSHdhUGNwU0ZFZjlOOE85TnEuLlZ1cnFqbS9SUkdTIjtzOjg6ImZpbGFtZW50IjthOjA6e319',1750916584);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;

--
-- Dumping data for table `settings`
--

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;

--
-- Dumping data for table `supplier_source_mappings`
--

/*!40000 ALTER TABLE `supplier_source_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_source_mappings` ENABLE KEYS */;

--
-- Dumping data for table `suppliers`
--

/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;

--
-- Dumping data for table `task_schedules`
--

/*!40000 ALTER TABLE `task_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_schedules` ENABLE KEYS */;

--
-- Dumping data for table `task_types`
--

/*!40000 ALTER TABLE `task_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_types` ENABLE KEYS */;

--
-- Dumping data for table `tasks`
--

/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;

--
-- Dumping data for table `test_workflow`
--

/*!40000 ALTER TABLE `test_workflow` DISABLE KEYS */;
/*!40000 ALTER TABLE `test_workflow` ENABLE KEYS */;

--
-- Dumping data for table `time_card_tasks`
--

/*!40000 ALTER TABLE `time_card_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_card_tasks` ENABLE KEYS */;

--
-- Dumping data for table `time_cards`
--

/*!40000 ALTER TABLE `time_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_cards` ENABLE KEYS */;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','charybshawn@gmail.com','250-515-4007','2025-06-26 22:59:18','$2y$12$G3onXJkuEwTGsAPjU0vji.3HwaPcpSFEf9N8O9Nq..Vurqjm/RRGS',NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26 22:59:18','2025-06-26 22:59:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-25 22:43:09
