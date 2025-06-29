-- MySQL dump 10.13  Distrib 9.3.0, for macos15.4 (arm64)
--
-- Host: 127.0.0.1    Database: catapult-dev
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
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `activity_log_causer_type_causer_id_index` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'default','created','App\\Models\\User','1','created',NULL,NULL,'{\"attributes\": {\"name\": \"Admin\", \"email\": \"charybshawn@gmail.com\", \"phone\": \"250-000-0000\"}}',NULL,'2025-06-29 02:32:11','2025-06-29 02:32:11'),(2,'default','created','App\\Models\\Supplier','1','created',NULL,NULL,'{\"attributes\": {\"name\": \"Uline\", \"type\": \"consumable\", \"is_active\": true, \"contact_name\": \"Customer Service\", \"contact_email\": \"customerservice@uline.com\", \"contact_phone\": \"1-800-295-5510\"}}',NULL,'2025-06-29 02:32:11','2025-06-29 02:32:11'),(3,'default','created','App\\Models\\PackagingType','1','created',NULL,NULL,'{\"attributes\": {\"name\": \"16oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": null, \"capacity_volume\": 16}}',NULL,'2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `consumables`
--

DROP TABLE IF EXISTS `consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `consumables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('packaging','soil','seed','label','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `packaging_type_id` bigint unsigned DEFAULT NULL,
  `seed_entry_id` bigint unsigned DEFAULT NULL,
  `master_seed_catalog_id` bigint unsigned DEFAULT NULL,
  `master_cultivar_id` bigint unsigned DEFAULT NULL,
  `cultivar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initial_stock` decimal(10,3) NOT NULL DEFAULT '0.000',
  `current_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` enum('unit','gram','pound','ounce','bag','tray','gallon','litre','millilitre') COLLATE utf8mb4_unicode_ci NOT NULL,
  `units_quantity` decimal(10,2) NOT NULL DEFAULT '1.00',
  `restock_threshold` decimal(10,2) NOT NULL DEFAULT '0.00',
  `restock_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `quantity_per_unit` decimal(10,2) NOT NULL DEFAULT '1.00',
  `quantity_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `consumed_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lot_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_ordered_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumables_packaging_type_id_foreign` (`packaging_type_id`),
  KEY `consumables_type_index` (`type`),
  KEY `consumables_supplier_id_index` (`supplier_id`),
  KEY `consumables_is_active_index` (`is_active`),
  KEY `consumables_seed_entry_id_index` (`seed_entry_id`),
  KEY `consumables_master_seed_catalog_id_index` (`master_seed_catalog_id`),
  KEY `consumables_master_cultivar_id_index` (`master_cultivar_id`),
  CONSTRAINT `consumables_master_cultivar_id_foreign` FOREIGN KEY (`master_cultivar_id`) REFERENCES `master_cultivars` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumables`
--

LOCK TABLES `consumables` WRITE;
/*!40000 ALTER TABLE `consumables` DISABLE KEYS */;
/*!40000 ALTER TABLE `consumables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crop_alerts`
--

DROP TABLE IF EXISTS `crop_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crop_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_alerts`
--

LOCK TABLES `crop_alerts` WRITE;
/*!40000 ALTER TABLE `crop_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `crop_batches`
--

DROP TABLE IF EXISTS `crop_batches`;
/*!50001 DROP VIEW IF EXISTS `crop_batches`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `crop_batches` AS SELECT 
 1 AS `id`,
 1 AS `recipe_id`,
 1 AS `planting_date`,
 1 AS `current_stage`,
 1 AS `tray_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `crop_plans`
--

DROP TABLE IF EXISTS `crop_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crop_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `recipe_id` bigint unsigned DEFAULT NULL,
  `trays_needed` int NOT NULL DEFAULT '0',
  `grams_needed` decimal(8,2) NOT NULL DEFAULT '0.00',
  `grams_per_tray` decimal(8,2) NOT NULL DEFAULT '0.00',
  `plant_by_date` date DEFAULT NULL,
  `expected_harvest_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `calculation_details` json DEFAULT NULL,
  `order_items_included` json DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','approved','generating','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  PRIMARY KEY (`id`),
  KEY `crop_plans_created_by_foreign` (`created_by`),
  KEY `crop_plans_order_id_foreign` (`order_id`),
  KEY `crop_plans_recipe_id_foreign` (`recipe_id`),
  KEY `crop_plans_approved_by_foreign` (`approved_by`),
  CONSTRAINT `crop_plans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crop_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_plans_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_plans_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_plans`
--

LOCK TABLES `crop_plans` WRITE;
/*!40000 ALTER TABLE `crop_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crop_tasks`
--

DROP TABLE IF EXISTS `crop_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crop_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `crop_id` bigint unsigned NOT NULL,
  `task_type` enum('water','advance_stage','harvest','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `scheduled_for` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `completed_by` bigint unsigned DEFAULT NULL,
  `status` enum('pending','completed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crop_tasks_completed_by_foreign` (`completed_by`),
  KEY `crop_tasks_crop_id_status_index` (`crop_id`,`status`),
  KEY `crop_tasks_scheduled_for_status_index` (`scheduled_for`,`status`),
  CONSTRAINT `crop_tasks_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crop_tasks_crop_id_foreign` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_tasks`
--

LOCK TABLES `crop_tasks` WRITE;
/*!40000 ALTER TABLE `crop_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `crop_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crops`
--

DROP TABLE IF EXISTS `crops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crops` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned DEFAULT NULL,
  `crop_plan_id` bigint unsigned DEFAULT NULL,
  `tray_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `planted_at` timestamp NULL DEFAULT NULL,
  `current_stage` enum('germination','blackout','light','harvested') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'germination',
  `stage_updated_at` timestamp NULL DEFAULT NULL,
  `planting_at` timestamp NULL DEFAULT NULL,
  `germination_at` timestamp NULL DEFAULT NULL,
  `blackout_at` timestamp NULL DEFAULT NULL,
  `light_at` timestamp NULL DEFAULT NULL,
  `harvested_at` timestamp NULL DEFAULT NULL,
  `harvest_weight_grams` decimal(8,2) DEFAULT NULL,
  `watering_suspended_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `time_to_next_stage_minutes` int DEFAULT NULL,
  `time_to_next_stage_display` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage_age_minutes` int DEFAULT NULL,
  `stage_age_display` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_age_minutes` int DEFAULT NULL,
  `total_age_display` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crops_tray_number_current_stage_index` (`tray_number`,`current_stage`),
  KEY `crops_recipe_id_index` (`recipe_id`),
  KEY `crops_order_id_index` (`order_id`),
  KEY `crops_crop_plan_id_index` (`crop_plan_id`),
  KEY `crops_current_stage_index` (`current_stage`),
  KEY `crops_planted_at_index` (`planted_at`),
  CONSTRAINT `crops_crop_plan_id_foreign` FOREIGN KEY (`crop_plan_id`) REFERENCES `crop_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crops_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crops_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crops`
--

LOCK TABLES `crops` WRITE;
/*!40000 ALTER TABLE `crops` DISABLE KEYS */;
/*!40000 ALTER TABLE `crops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cc_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_type` enum('retail','wholesale') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'retail',
  `business_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wholesale_discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CA',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_customer_type_index` (`customer_type`),
  KEY `customers_user_id_index` (`user_id`),
  CONSTRAINT `customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `harvests`
--

DROP TABLE IF EXISTS `harvests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `harvests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_cultivar_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `total_weight_grams` decimal(10,2) NOT NULL,
  `tray_count` int NOT NULL,
  `average_weight_per_tray` decimal(10,2) GENERATED ALWAYS AS ((`total_weight_grams` / nullif(`tray_count`,0))) STORED,
  `harvest_date` date NOT NULL,
  `week_start_date` date GENERATED ALWAYS AS ((`harvest_date` - interval weekday(`harvest_date`) day)) STORED,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `harvests_user_id_foreign` (`user_id`),
  KEY `harvests_master_cultivar_id_harvest_date_index` (`master_cultivar_id`,`harvest_date`),
  KEY `harvests_week_start_date_index` (`week_start_date`),
  CONSTRAINT `harvests_master_cultivar_id_foreign` FOREIGN KEY (`master_cultivar_id`) REFERENCES `master_cultivars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `harvests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvests`
--

LOCK TABLES `harvests` WRITE;
/*!40000 ALTER TABLE `harvests` DISABLE KEYS */;
/*!40000 ALTER TABLE `harvests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_reservations`
--

DROP TABLE IF EXISTS `inventory_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_inventory_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `order_id` bigint unsigned NOT NULL,
  `order_item_id` bigint unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','fulfilled','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NULL DEFAULT NULL,
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_reservations_product_inventory_id_foreign` (`product_inventory_id`),
  KEY `inventory_reservations_order_item_id_foreign` (`order_item_id`),
  KEY `inventory_reservations_product_id_status_index` (`product_id`,`status`),
  KEY `inventory_reservations_order_id_status_index` (`order_id`,`status`),
  KEY `inventory_reservations_expires_at_index` (`expires_at`),
  CONSTRAINT `inventory_reservations_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_reservations_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_reservations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_reservations_product_inventory_id_foreign` FOREIGN KEY (`product_inventory_id`) REFERENCES `product_inventories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_reservations`
--

LOCK TABLES `inventory_reservations` WRITE;
/*!40000 ALTER TABLE `inventory_reservations` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_reservations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_inventory_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `type` enum('purchase','sale','adjustment','return','damage','transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_product_inventory_id_foreign` (`product_inventory_id`),
  KEY `inventory_transactions_user_id_foreign` (`user_id`),
  KEY `inventory_transactions_product_id_type_index` (`product_id`,`type`),
  KEY `inventory_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_transactions_created_at_index` (`created_at`),
  CONSTRAINT `inventory_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_product_inventory_id_foreign` FOREIGN KEY (`product_inventory_id`) REFERENCES `product_inventories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('draft','sent','paid','overdue','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_consolidated` tinyint(1) NOT NULL DEFAULT '0',
  `consolidated_order_count` int NOT NULL DEFAULT '1',
  `consolidated_order_ids` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_order_id_foreign` (`order_id`),
  KEY `invoices_status_index` (`status`),
  KEY `invoices_due_date_index` (`due_date`),
  KEY `invoices_customer_id_index` (`customer_id`),
  CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_cultivars`
--

DROP TABLE IF EXISTS `master_cultivars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_cultivars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `master_seed_catalog_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `days_to_maturity` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `master_cultivars_master_seed_catalog_id_name_index` (`master_seed_catalog_id`,`name`),
  KEY `master_cultivars_supplier_id_index` (`supplier_id`),
  CONSTRAINT `master_cultivars_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `master_cultivars_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_cultivars`
--

LOCK TABLES `master_cultivars` WRITE;
/*!40000 ALTER TABLE `master_cultivars` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_cultivars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `master_seed_catalog`
--

DROP TABLE IF EXISTS `master_seed_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_seed_catalog` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `common_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cultivars` json DEFAULT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliases` json DEFAULT NULL,
  `growing_notes` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_seed_catalog_common_name_unique` (`common_name`),
  KEY `master_seed_catalog_common_name_index` (`common_name`),
  KEY `master_seed_catalog_category_index` (`category`),
  KEY `master_seed_catalog_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_seed_catalog`
--

LOCK TABLES `master_seed_catalog` WRITE;
/*!40000 ALTER TABLE `master_seed_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `master_seed_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=207 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2025_06_20_000001_create_users_table',1),(2,'2025_06_20_000002_create_cache_table',1),(3,'2025_06_20_000003_create_jobs_table',1),(4,'2025_06_20_000004_create_permission_tables',1),(5,'2025_06_20_000005_create_suppliers_table',1),(6,'2025_06_20_000006_create_inventory_tables',1),(7,'2025_06_20_000007_create_seed_catalog_tables',1),(8,'2025_06_20_000008_create_recipes_tables',1),(9,'2025_06_20_000009_create_orders_tables',1),(10,'2025_06_20_000010_create_products_tables',1),(11,'2025_06_20_000011_create_crops_tables',1),(12,'2025_06_20_000012_create_system_tables',1),(13,'2025_06_20_000013_create_product_inventory_tables',1),(14,'2025_06_20_000014_add_foreign_key_constraints',1),(15,'2025_06_20_144953_add_resource_type_to_task_schedules_table',1),(16,'2025_06_20_145646_add_missing_fields_to_users_table',1),(17,'2025_06_20_145717_add_missing_fields_to_orders_table',1),(18,'2025_06_20_145752_add_missing_fields_to_invoices_table',1),(19,'2025_06_20_145812_fix_seed_scrape_upload_column_names',1),(20,'2025_06_20_150240_add_initial_stock_to_consumables_table',1),(21,'2025_06_20_151052_fix_product_photos_table_columns',1),(22,'2025_06_20_151115_update_orders_status_enum',1),(23,'0001_01_01_000000_create_users_table',0),(24,'0001_01_01_000001_create_cache_table',0),(25,'0001_01_01_000002_create_jobs_table',0),(26,'2024_08_15_000000_create_crop_alerts_table',0),(27,'2025_03_15_055950_create_permission_tables',0),(28,'2025_03_15_060211_create_suppliers_table',0),(29,'2025_03_15_060212_create_seed_varieties_table',0),(30,'2025_03_15_060214_create_recipes_table',0),(31,'2025_03_15_060215_create_recipe_stages_table',0),(32,'2025_03_15_060305_create_recipe_watering_schedule_table',0),(33,'2025_03_15_060319_create_recipe_mixes_table',0),(34,'2025_03_15_060335_create_crops_table',0),(35,'2025_03_15_060352_create_inventory_table',0),(36,'2025_03_15_060353_create_consumables_table',0),(37,'2025_03_15_060353_create_orders_table',0),(38,'2025_03_15_060355_create_invoices_table',0),(39,'2025_03_15_060355_create_payments_table',0),(40,'2025_03_15_060355_create_settings_table',0),(41,'2025_03_15_060355_drop_inventory_table',0),(42,'2025_03_15_060527_fix_migration_order',0),(43,'2025_03_15_063501_create_activity_log_table',0),(44,'2025_03_15_070829_create_personal_access_tokens_table',0),(45,'2025_03_21_002206_create_packaging_types_table',0),(46,'2025_03_21_002211_create_order_packagings_table',0),(47,'2025_03_21_031151_migrate_legacy_images_to_item_photos',0),(48,'2025_03_21_032617_remove_code_field_from_items_table',0),(49,'2025_03_23_192440_add_light_days_to_recipes_table',0),(50,'2025_03_25_235525_create_task_schedules_table',0),(51,'2025_03_25_235534_create_notification_settings_table',0),(52,'2025_03_26_010126_update_packaging_types_add_volume_field',0),(53,'2025_03_26_010933_remove_capacity_grams_from_packaging_types',0),(54,'2025_03_26_045009_add_soil_consumable_id_to_recipes_table',0),(55,'2025_04_09_020444_add_stage_timestamps_to_crops_table',0),(56,'2025_04_09_045210_create_tasks_table',0),(57,'2025_04_17_185454_add_packaging_type_foreign_key_to_consumables',0),(58,'2025_04_17_234148_update_consumable_unit_types',0),(59,'2025_04_17_234403_update_lot_no_to_uppercase',0),(60,'2025_04_18_003016_add_units_quantity_to_consumables_table',0),(61,'2025_04_18_003759_update_consumable_unit_types_to_simpler_values',0),(62,'2025_04_18_010330_add_consumed_quantity_to_consumables_table',0),(63,'2025_04_18_014631_update_consumables_decimal_precision',0),(64,'2025_04_18_025334_update_clamshell_packaging_types_volume',0),(65,'2025_04_18_034705_add_watering_method_to_recipe_watering_schedule',0),(66,'2025_04_18_042544_rename_seed_soak_days_to_hours_in_recipes_table',0),(67,'2025_04_18_054155_fix_recipe_seed_variety_relationship',0),(68,'2025_04_18_100000_change_seed_soak_days_to_decimal',0),(69,'2025_04_19_000000_drop_recipe_mixes_table',0),(70,'2025_04_19_031951_remove_notes_from_recipes',0),(71,'2025_04_19_035640_add_growth_phase_notes_columns_to_recipes_table',0),(72,'2025_04_19_041217_add_seed_variety_id_to_consumables',0),(73,'2025_04_19_043838_update_consumed_quantity_default_on_consumables',0),(74,'2025_04_19_044201_update_total_quantity_default_on_consumables',0),(75,'2025_04_19_045350_update_consumables_table_structure',0),(76,'2025_04_19_045809_add_missing_columns_to_seed_varieties',0),(77,'2025_04_19_050518_update_crops_recipe_foreign_key',0),(78,'2025_04_19_052750_add_crop_type_to_seed_varieties_table',0),(79,'2025_05_01_133249_add_time_to_next_stage_minutes_to_crops_table',0),(80,'2025_05_01_143431_add_stage_age_minutes_to_crops_table',0),(81,'2025_05_01_144928_add_total_age_minutes_to_crops_table',0),(82,'2025_05_02_165743_update_time_to_next_stage_minutes_column_type',0),(83,'2025_05_02_165851_update_stage_age_minutes_column_type',0),(84,'2025_05_02_165855_update_total_age_minutes_column_type',0),(85,'2025_05_02_205557_create_crop_batches_view',0),(86,'2025_05_03_000000_add_calculated_columns_to_crops_table',0),(87,'2025_05_03_222337_add_suspend_watering_to_recipes_table',0),(88,'2025_05_03_222805_remove_stage_notes_from_recipes_table',0),(89,'2025_05_03_222911_rename_suspend_watering_hours_column_in_recipes_table',0),(90,'2025_05_03_224138_create_crop_tasks_table',0),(91,'2025_05_03_224935_create_notifications_table',0),(92,'2025_05_07_094527_create_products_table',0),(93,'2025_05_07_094528_create_price_variations_for_existing_products',0),(94,'2025_05_07_094529_create_product_photos_table',0),(95,'2025_05_07_094530_remove_caption_from_product_photos',0),(96,'2025_05_09_000000_update_stage_age_minutes_column_type',0),(97,'2025_05_20_201327_add_indexes_for_optimization',0),(98,'2025_05_26_162845_create_seed_cultivars_table',0),(99,'2025_05_26_162849_create_seed_entries_table',0),(100,'2025_05_26_162852_create_seed_variations_table',0),(101,'2025_05_26_162855_create_seed_price_history_table',0),(102,'2025_05_26_162859_create_seed_scrape_uploads_table',0),(103,'2025_05_26_162902_add_consumable_id_to_seed_variations',0),(104,'2025_06_03_100000_placeholder_notes_column_decision',0),(105,'2025_06_03_141432_add_missing_foreign_key_constraints',0),(106,'2025_06_03_141453_add_critical_performance_indexes',0),(107,'2025_06_03_213058_add_recurring_order_support_to_orders_table',0),(108,'2025_06_03_220125_create_product_mixes_table',0),(109,'2025_06_03_220129_create_product_mix_components_table',0),(110,'2025_06_03_223329_add_packaging_type_to_price_variations_table',0),(111,'2025_06_03_223734_add_fill_weight_to_price_variations_table',0),(112,'2025_06_03_224520_add_active_column_to_products_table',0),(113,'2025_06_03_224602_make_sku_nullable_in_products_table',0),(114,'2025_06_04_072532_add_customer_type_to_users_table',0),(115,'2025_06_04_073839_update_orders_status_enum_values',0),(116,'2025_06_04_075015_update_invoice_foreign_key_to_cascade_on_delete',0),(117,'2025_06_04_075517_add_price_variation_id_to_order_products_table',0),(118,'2025_06_04_083155_add_preferences_to_users_table',0),(119,'2025_06_04_090627_remove_preferences_from_users_table',0),(120,'2025_06_04_100000_migrate_recipes_to_seed_cultivar',0),(121,'2025_06_04_100001_add_seed_variety_fields_to_seed_cultivars',0),(122,'2025_06_04_100002_remove_seed_variety_id_from_consumables',0),(123,'2025_06_04_100004_update_product_mix_components_to_seed_cultivar',0),(124,'2025_06_04_100005_drop_seed_varieties_table',0),(125,'2025_06_05_075524_simplify_seed_structure_add_names_to_entries',0),(126,'2025_06_05_075648_fix_common_names_in_seed_entries',0),(127,'2025_06_05_085532_make_seed_cultivar_id_nullable_in_seed_entries',0),(128,'2025_06_05_193018_add_cataloged_at_to_seed_entries_table',0),(129,'2025_06_05_193715_create_supplier_source_mappings_table',0),(130,'2025_06_08_092642_remove_cataloged_at_from_seed_entries_table',0),(131,'2025_06_09_062308_add_seed_entry_id_to_consumables_table',0),(132,'2025_06_09_063844_add_is_active_to_seed_entries_table',0),(133,'2025_06_09_064442_fix_recipes_seed_entry_foreign_key',0),(134,'2025_06_09_065222_rename_seed_cultivar_id_to_seed_entry_id_in_recipes',0),(135,'2025_06_09_065622_rename_seed_cultivar_id_to_seed_entry_id_in_product_mix_components',0),(136,'2025_06_09_111847_add_failed_entries_to_seed_scrape_uploads_table',0),(137,'2025_06_09_130054_make_current_price_nullable_in_seed_variations_table',0),(138,'2025_06_09_155051_make_cost_per_unit_nullable_in_consumables_table',0),(139,'2025_06_09_174941_make_harvest_date_nullable_in_orders_table',0),(140,'2025_06_09_180239_add_order_classification_to_orders_table',0),(141,'2025_06_09_180649_add_consolidated_invoice_support_to_invoices_table',0),(142,'2025_06_09_195832_make_order_id_nullable_in_invoices_table',0),(143,'2025_06_09_222238_add_billing_period_to_orders_table',0),(144,'2025_06_09_233139_create_crop_plans_table',0),(145,'2025_06_09_233223_add_crop_plan_id_to_crops_table',0),(146,'2025_06_11_133418_create_product_inventory_system',0),(147,'2025_06_11_151000_add_seed_entry_to_products_if_not_exists',0),(148,'2025_06_11_210426_create_master_seed_catalog_table',0),(149,'2025_06_11_210429_create_master_cultivars_table',0),(150,'2025_06_11_221240_change_scientific_name_to_json_in_master_seed_catalog',0),(151,'2025_06_11_225657_add_master_seed_catalog_id_to_consumables_table',0),(152,'2025_06_11_230351_add_cultivars_column_to_master_seed_catalog_table',0),(153,'2025_06_11_230435_rename_scientific_name_to_cultivars_in_master_seed_catalog',0),(154,'2025_06_11_231627_add_master_seed_catalog_id_to_products_table',0),(155,'2025_06_11_231700_replace_seed_entry_id_with_master_seed_catalog_id_in_products',0),(156,'2025_06_12_085856_add_template_id_to_price_variations_table',0),(157,'2025_06_12_184326_add_soft_deletes_to_consumables_table',0),(158,'2025_06_12_200016_add_master_cultivar_id_to_consumables_table',0),(159,'2025_06_12_201000_populate_master_cultivar_id_in_consumables',0),(160,'2025_06_12_204424_make_seed_entry_id_nullable_in_product_mix_components',0),(161,'2025_06_12_204633_remove_seed_entry_id_from_product_mix_components',0),(162,'2025_06_12_205054_fix_product_mix_components_unique_constraints',0),(163,'2025_06_12_add_pricing_unit_to_price_variations',0),(164,'2025_06_13_161917_add_wholesale_discount_percentage_to_products_table',0),(165,'2025_06_13_163543_update_existing_products_wholesale_discount_default',0),(166,'2025_06_13_180604_add_unique_index_to_product_name',0),(167,'2025_06_13_214716_add_cultivar_to_consumables_table',0),(168,'2025_06_13_214757_populate_cultivar_in_consumables_table',0),(169,'2025_06_13_215428_update_percentage_precision_in_product_mix_components',0),(170,'2025_06_13_add_cascade_delete_to_product_relations',0),(171,'2025_06_13_remove_batch_number_from_product_inventories',0),(172,'2025_06_18_112138_create_harvests_table',0),(173,'2025_06_18_122409_add_buffer_percentage_to_recipes_table',0),(174,'2025_06_18_142749_add_packed_status_to_orders_enum',0),(175,'2025_06_18_180519_separate_order_statuses_into_distinct_columns',0),(176,'2025_06_18_182436_create_data_exports_table',0),(177,'2025_06_19_073000_drop_data_exports_table',0),(178,'2025_06_19_111313_insert_harvest_data',0),(179,'2025_06_19_141353_add_wholesale_discount_percentage_to_users_table',0),(180,'2025_06_19_180500_make_customer_type_nullable_in_orders_table',0),(181,'2025_06_19_181751_add_b2b_to_order_type_enum',0),(182,'2025_08_15_000001_add_days_to_maturity_to_recipes_table',0),(183,'2025_06_20_999999_consolidation_marker',1),(184,'2025_06_24_080303_add_source_url_to_supplier_source_mappings_table',1),(185,'2025_06_24_091546_create_time_cards_table',1),(186,'2025_06_24_092916_add_review_fields_to_time_cards_table',1),(187,'2025_06_24_104741_add_other_type_to_suppliers_table',1),(188,'2025_06_24_110323_add_packaging_type_to_suppliers_table',1),(189,'2025_06_24_145952_create_task_types_table',1),(190,'2025_06_24_150348_create_time_card_tasks_table',1),(191,'2025_06_24_213542_make_password_nullable_in_users_table',1),(192,'2025_06_24_222528_add_group_to_settings_table',1),(193,'2025_06_24_231307_create_customers_table',1),(194,'2025_06_24_232142_update_customers_table_for_canadian_format',1),(195,'2025_06_24_232250_add_country_to_customers_table',1),(196,'2025_06_24_233345_add_customer_id_to_orders_table',1),(197,'2025_06_24_233448_add_customer_id_to_invoices_table',1),(198,'2025_06_25_091104_fix_product_inventory_price_variation_mismatch',1),(199,'2025_06_25_092743_update_packaging_types_columns',1),(200,'2025_06_25_233222_add_missing_columns_to_seed_entries_table',1),(201,'2025_06_25_233253_update_seed_variations_table_for_import',1),(202,'2025_06_25_233329_update_seed_price_history_table_for_import',1),(203,'2025_06_25_233359_update_seed_scrape_uploads_table_for_import',1),(204,'2025_06_25_233429_update_supplier_source_mappings_table_for_import',1),(205,'2025_06_26_233734_update_crop_plans_table_structure',1),(206,'2025_06_28_192811_fix_crops_column_names',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `channel` enum('email','sms','push') COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notification_settings_user_id_channel_index` (`user_id`,`channel`),
  CONSTRAINT `notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_packagings`
--

DROP TABLE IF EXISTS `order_packagings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_packagings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `packaging_type_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_packagings_packaging_type_id_foreign` (`packaging_type_id`),
  KEY `order_packagings_order_id_index` (`order_id`),
  CONSTRAINT `order_packagings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_packagings_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_packagings`
--

LOCK TABLES `order_packagings` WRITE;
/*!40000 ALTER TABLE `order_packagings` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_packagings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_products`
--

DROP TABLE IF EXISTS `order_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `price_variation_id` bigint unsigned DEFAULT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_products_price_variation_id_foreign` (`price_variation_id`),
  KEY `order_products_order_id_index` (`order_id`),
  KEY `order_products_product_id_index` (`product_id`),
  CONSTRAINT `order_products_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_products_price_variation_id_foreign` FOREIGN KEY (`price_variation_id`) REFERENCES `price_variations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `order_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_products`
--

LOCK TABLES `order_products` WRITE;
/*!40000 ALTER TABLE `order_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `harvest_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('pending','confirmed','in_production','ready_for_harvest','harvested','packed','delivered','cancelled','draft','template') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `crop_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fulfillment_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_status` enum('pending','confirmed','in_production','ready_for_harvest','harvested','packed','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','partial','paid','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `delivery_status` enum('pending','scheduled','in_transit','delivered','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `customer_type` enum('retail','wholesale','b2b') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_type` enum('standard','subscription','b2b') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `billing_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_invoice` tinyint(1) NOT NULL DEFAULT '0',
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `consolidated_invoice_id` bigint unsigned DEFAULT NULL,
  `billing_preferences` json DEFAULT NULL,
  `order_classification` enum('scheduled','ondemand','overflow','priority') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `billing_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `parent_recurring_order_id` bigint unsigned DEFAULT NULL,
  `recurring_frequency` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurring_start_date` date DEFAULT NULL,
  `recurring_end_date` date DEFAULT NULL,
  `is_recurring_active` tinyint(1) NOT NULL DEFAULT '1',
  `recurring_days_of_week` json DEFAULT NULL,
  `recurring_interval` int DEFAULT NULL,
  `last_generated_at` datetime DEFAULT NULL,
  `next_generation_date` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_status_index` (`user_id`,`status`),
  KEY `orders_harvest_date_index` (`harvest_date`),
  KEY `orders_delivery_date_index` (`delivery_date`),
  KEY `orders_order_type_index` (`order_type`),
  KEY `orders_consolidated_invoice_id_foreign` (`consolidated_invoice_id`),
  KEY `orders_parent_recurring_order_id_foreign` (`parent_recurring_order_id`),
  KEY `orders_customer_id_index` (`customer_id`),
  CONSTRAINT `orders_consolidated_invoice_id_foreign` FOREIGN KEY (`consolidated_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_parent_recurring_order_id_foreign` FOREIGN KEY (`parent_recurring_order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packaging_types`
--

DROP TABLE IF EXISTS `packaging_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packaging_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('clamshell','bag','box','jar','tray','bulk','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `unit_type` enum('count','weight') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'count',
  `capacity_volume` decimal(10,2) DEFAULT NULL,
  `volume_unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packaging_types`
--

LOCK TABLES `packaging_types` WRITE;
/*!40000 ALTER TABLE `packaging_types` DISABLE KEYS */;
INSERT INTO `packaging_types` VALUES (1,'16oz Clamshell','other','count',16.00,'oz',NULL,1,NULL,'2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `packaging_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('stripe','e-transfer','cash','invoice') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_status_index` (`order_id`,`status`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'access filament','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(2,'manage products','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(3,'view products','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(4,'edit products','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(5,'delete products','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(6,'view_own_orders','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(7,'view_own_profile','web','2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_variations`
--

DROP TABLE IF EXISTS `price_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_variations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pricing_unit` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` decimal(10,2) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `fill_weight` decimal(10,2) DEFAULT NULL,
  `packaging_type_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_global` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `price_variations_packaging_type_id_foreign` (`packaging_type_id`),
  KEY `price_variations_template_id_foreign` (`template_id`),
  KEY `price_variations_product_id_index` (`product_id`),
  KEY `price_variations_is_active_index` (`is_active`),
  CONSTRAINT `price_variations_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `price_variations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_variations_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `price_variations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_variations`
--

LOCK TABLES `price_variations` WRITE;
/*!40000 ALTER TABLE `price_variations` DISABLE KEYS */;
/*!40000 ALTER TABLE `price_variations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_inventories`
--

DROP TABLE IF EXISTS `product_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_inventories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `price_variation_id` bigint unsigned NOT NULL,
  `batch_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lot_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `reserved_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `available_quantity` decimal(10,2) GENERATED ALWAYS AS ((`quantity` - `reserved_quantity`)) STORED,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','depleted','expired','damaged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_price_variation_unique` (`product_id`,`price_variation_id`),
  KEY `product_inventories_product_id_status_index` (`product_id`,`status`),
  KEY `product_inventories_product_id_price_variation_id_index` (`product_id`,`price_variation_id`),
  KEY `product_inventories_batch_number_index` (`batch_number`),
  KEY `product_inventories_lot_number_index` (`lot_number`),
  KEY `product_inventories_expiration_date_index` (`expiration_date`),
  KEY `product_inventories_product_id_available_quantity_index` (`product_id`,`available_quantity`),
  KEY `product_inventories_price_variation_id_foreign` (`price_variation_id`),
  CONSTRAINT `product_inventories_price_variation_id_foreign` FOREIGN KEY (`price_variation_id`) REFERENCES `price_variations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inventories`
--

LOCK TABLES `product_inventories` WRITE;
/*!40000 ALTER TABLE `product_inventories` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `product_inventory_summary`
--

DROP TABLE IF EXISTS `product_inventory_summary`;
/*!50001 DROP VIEW IF EXISTS `product_inventory_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `product_inventory_summary` AS SELECT 
 1 AS `product_id`,
 1 AS `total_quantity`,
 1 AS `total_reserved`,
 1 AS `total_available`,
 1 AS `avg_cost`,
 1 AS `earliest_expiration`,
 1 AS `batch_count`,
 1 AS `location_count`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `product_mix_components`
--

DROP TABLE IF EXISTS `product_mix_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_mix_components` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_mix_id` bigint unsigned NOT NULL,
  `seed_entry_id` bigint unsigned NOT NULL,
  `percentage` decimal(8,5) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_mix_components_product_mix_id_seed_entry_id_unique` (`product_mix_id`,`seed_entry_id`),
  KEY `product_mix_components_seed_entry_id_foreign` (`seed_entry_id`),
  KEY `product_mix_components_product_mix_id_index` (`product_mix_id`),
  CONSTRAINT `product_mix_components_product_mix_id_foreign` FOREIGN KEY (`product_mix_id`) REFERENCES `product_mixes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_mix_components_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_mix_components`
--

LOCK TABLES `product_mix_components` WRITE;
/*!40000 ALTER TABLE `product_mix_components` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_mix_components` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_mixes`
--

DROP TABLE IF EXISTS `product_mixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_mixes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_mixes`
--

LOCK TABLES `product_mixes` WRITE;
/*!40000 ALTER TABLE `product_mixes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_mixes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_photos`
--

DROP TABLE IF EXISTS `product_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_photos_product_id_index` (`product_id`),
  CONSTRAINT `product_photos_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_photos`
--

LOCK TABLES `product_photos` WRITE;
/*!40000 ALTER TABLE `product_photos` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seed_entry_id` bigint unsigned DEFAULT NULL,
  `master_seed_catalog_id` bigint unsigned DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `bulk_price` decimal(10,2) DEFAULT NULL,
  `special_price` decimal(10,2) DEFAULT NULL,
  `wholesale_discount_percentage` decimal(5,2) NOT NULL DEFAULT '15.00',
  `is_visible_in_store` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `product_mix_id` bigint unsigned DEFAULT NULL,
  `total_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reserved_stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reorder_threshold` decimal(10,2) NOT NULL DEFAULT '0.00',
  `track_inventory` tinyint(1) NOT NULL DEFAULT '1',
  `stock_status` enum('in_stock','low_stock','out_of_stock','discontinued') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'in_stock',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_name_unique` (`name`),
  KEY `products_seed_entry_id_foreign` (`seed_entry_id`),
  KEY `products_master_seed_catalog_id_foreign` (`master_seed_catalog_id`),
  KEY `products_active_index` (`active`),
  KEY `products_category_id_index` (`category_id`),
  KEY `products_product_mix_id_index` (`product_mix_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_product_mix_id_foreign` FOREIGN KEY (`product_mix_id`) REFERENCES `product_mixes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_stages`
--

DROP TABLE IF EXISTS `recipe_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_stages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day` int NOT NULL,
  `duration_days` int NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `stage` enum('germination','blackout','light') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `temperature_min_celsius` decimal(5,2) DEFAULT NULL,
  `temperature_max_celsius` decimal(5,2) DEFAULT NULL,
  `humidity_min_percent` decimal(5,2) DEFAULT NULL,
  `humidity_max_percent` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipe_stages_recipe_id_stage_unique` (`recipe_id`,`stage`),
  KEY `recipe_stages_recipe_id_index` (`recipe_id`),
  CONSTRAINT `recipe_stages_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_stages`
--

LOCK TABLES `recipe_stages` WRITE;
/*!40000 ALTER TABLE `recipe_stages` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipe_watering_schedule`
--

DROP TABLE IF EXISTS `recipe_watering_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipe_watering_schedule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint unsigned NOT NULL,
  `day_number` int NOT NULL,
  `water_amount_ml` decimal(8,2) NOT NULL,
  `watering_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `needs_liquid_fertilizer` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipe_watering_schedule_recipe_id_day_number_unique` (`recipe_id`,`day_number`),
  KEY `recipe_watering_schedule_recipe_id_index` (`recipe_id`),
  CONSTRAINT `recipe_watering_schedule_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_watering_schedule`
--

LOCK TABLES `recipe_watering_schedule` WRITE;
/*!40000 ALTER TABLE `recipe_watering_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipe_watering_schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recipes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_soil_id` bigint unsigned DEFAULT NULL,
  `seed_entry_id` bigint unsigned DEFAULT NULL,
  `soil_consumable_id` bigint unsigned DEFAULT NULL,
  `seed_density` int NOT NULL,
  `seed_soak_hours` decimal(5,2) NOT NULL DEFAULT '0.00',
  `germination_days` int NOT NULL,
  `blackout_days` int NOT NULL,
  `light_days` int NOT NULL,
  `harvest_days` int NOT NULL,
  `days_to_maturity` int DEFAULT NULL,
  `expected_yield_grams` decimal(8,2) DEFAULT NULL,
  `seed_density_grams_per_tray` decimal(8,2) DEFAULT NULL,
  `buffer_percentage` decimal(5,2) NOT NULL DEFAULT '10.00',
  `suspend_watering_hours` int NOT NULL DEFAULT '0',
  `germination_notes` text COLLATE utf8mb4_unicode_ci,
  `blackout_notes` text COLLATE utf8mb4_unicode_ci,
  `light_notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipes_supplier_soil_id_foreign` (`supplier_soil_id`),
  KEY `recipes_is_active_index` (`is_active`),
  KEY `recipes_seed_entry_id_index` (`seed_entry_id`),
  KEY `recipes_soil_consumable_id_index` (`soil_consumable_id`),
  CONSTRAINT `recipes_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recipes_soil_consumable_id_foreign` FOREIGN KEY (`soil_consumable_id`) REFERENCES `consumables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recipes_supplier_soil_id_foreign` FOREIGN KEY (`supplier_soil_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(1,2),(3,2),(4,2),(3,3),(6,4),(7,4);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(2,'manager','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(3,'user','web','2025-06-29 02:32:11','2025-06-29 02:32:11'),(4,'customer','web','2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seed_cultivars`
--

DROP TABLE IF EXISTS `seed_cultivars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seed_cultivars` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seed_catalog_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `crop_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `days_to_maturity` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_cultivars_seed_catalog_id_foreign` (`seed_catalog_id`),
  KEY `seed_cultivars_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `seed_cultivars_seed_catalog_id_foreign` FOREIGN KEY (`seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seed_cultivars_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_cultivars`
--

LOCK TABLES `seed_cultivars` WRITE;
/*!40000 ALTER TABLE `seed_cultivars` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_cultivars` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seed_entries`
--

DROP TABLE IF EXISTS `seed_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seed_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seed_cultivar_id` bigint unsigned DEFAULT NULL,
  `common_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cultivar_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_product_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `supplier_sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_product_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tags` json DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_entries_seed_cultivar_id_index` (`seed_cultivar_id`),
  KEY `seed_entries_supplier_id_index` (`supplier_id`),
  KEY `seed_entries_supplier_id_supplier_sku_index` (`supplier_id`,`supplier_sku`),
  CONSTRAINT `seed_entries_seed_cultivar_id_foreign` FOREIGN KEY (`seed_cultivar_id`) REFERENCES `seed_cultivars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seed_entries_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_entries`
--

LOCK TABLES `seed_entries` WRITE;
/*!40000 ALTER TABLE `seed_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seed_price_history`
--

DROP TABLE IF EXISTS `seed_price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seed_price_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seed_variation_id` bigint unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `is_in_stock` tinyint(1) NOT NULL DEFAULT '1',
  `scraped_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_price_history_seed_variation_id_checked_at_index` (`seed_variation_id`,`scraped_at`),
  CONSTRAINT `seed_price_history_seed_variation_id_foreign` FOREIGN KEY (`seed_variation_id`) REFERENCES `seed_variations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_price_history`
--

LOCK TABLES `seed_price_history` WRITE;
/*!40000 ALTER TABLE `seed_price_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_price_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seed_scrape_uploads`
--

DROP TABLE IF EXISTS `seed_scrape_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seed_scrape_uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_entries` int NOT NULL DEFAULT '0',
  `new_entries` int NOT NULL DEFAULT '0',
  `failed_entries_count` int NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `failed_entries` json DEFAULT NULL,
  `uploaded_by` bigint unsigned NOT NULL,
  `uploaded_at` timestamp NOT NULL,
  `processed_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `successful_entries` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_scrape_uploads_supplier_id_foreign` (`supplier_id`),
  KEY `seed_scrape_uploads_uploaded_by_foreign` (`uploaded_by`),
  CONSTRAINT `seed_scrape_uploads_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seed_scrape_uploads_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_scrape_uploads`
--

LOCK TABLES `seed_scrape_uploads` WRITE;
/*!40000 ALTER TABLE `seed_scrape_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_scrape_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seed_variations`
--

DROP TABLE IF EXISTS `seed_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seed_variations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `seed_entry_id` bigint unsigned NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `consumable_id` bigint unsigned DEFAULT NULL,
  `size_description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight_kg` decimal(8,4) DEFAULT NULL,
  `original_weight_value` decimal(8,4) DEFAULT NULL,
  `original_weight_unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `is_in_stock` tinyint(1) NOT NULL DEFAULT '1',
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_variations_seed_entry_id_index` (`seed_entry_id`),
  KEY `seed_variations_consumable_id_index` (`consumable_id`),
  CONSTRAINT `seed_variations_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seed_variations_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_variations`
--

LOCK TABLES `seed_variations` WRITE;
/*!40000 ALTER TABLE `seed_variations` DISABLE KEYS */;
/*!40000 ALTER TABLE `seed_variations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('text','number','boolean','json','date') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `group` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Setting group (e.g., general, notifications, fertilizer)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_source_mappings`
--

DROP TABLE IF EXISTS `supplier_source_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_source_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` bigint unsigned NOT NULL,
  `source_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_identifier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mapping_data` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_source_unique` (`supplier_id`,`source_name`,`source_identifier`),
  CONSTRAINT `supplier_source_mappings_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_source_mappings`
--

LOCK TABLES `supplier_source_mappings` WRITE;
/*!40000 ALTER TABLE `supplier_source_mappings` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_source_mappings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('soil','seed','consumable','other','packaging') COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Uline','consumable','Customer Service','customerservice@uline.com','1-800-295-5510','12575 Uline Drive, Pleasant Prairie, WI 53158, USA',NULL,1,'2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_schedules`
--

DROP TABLE IF EXISTS `task_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `task_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `frequency` enum('once','daily','weekly','monthly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `schedule_config` json NOT NULL,
  `time_of_day` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_week` int DEFAULT NULL,
  `day_of_month` int DEFAULT NULL,
  `conditions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_schedules`
--

LOCK TABLES `task_schedules` WRITE;
/*!40000 ALTER TABLE `task_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_types`
--

DROP TABLE IF EXISTS `task_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_types_category_index` (`category`),
  KEY `task_types_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_types`
--

LOCK TABLES `task_types` WRITE;
/*!40000 ALTER TABLE `task_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `task_type` enum('watering','harvesting','seeding','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_assigned_to_status_index` (`assigned_to`,`status`),
  KEY `tasks_due_date_status_index` (`due_date`,`status`),
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_card_tasks`
--

DROP TABLE IF EXISTS `time_card_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_card_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `time_card_id` bigint unsigned NOT NULL,
  `task_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `task_type_id` bigint unsigned DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_card_tasks_task_type_id_foreign` (`task_type_id`),
  KEY `time_card_tasks_time_card_id_task_name_index` (`time_card_id`,`task_name`),
  CONSTRAINT `time_card_tasks_task_type_id_foreign` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_card_tasks_time_card_id_foreign` FOREIGN KEY (`time_card_id`) REFERENCES `time_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_card_tasks`
--

LOCK TABLES `time_card_tasks` WRITE;
/*!40000 ALTER TABLE `time_card_tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_card_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_cards`
--

DROP TABLE IF EXISTS `time_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `duration_minutes` int DEFAULT NULL,
  `work_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `max_shift_exceeded` tinyint(1) NOT NULL DEFAULT '0',
  `max_shift_exceeded_at` datetime DEFAULT NULL,
  `requires_review` tinyint(1) NOT NULL DEFAULT '0',
  `flags` json DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_cards_user_id_work_date_index` (`user_id`,`work_date`),
  KEY `time_cards_status_index` (`status`),
  KEY `time_cards_requires_review_index` (`requires_review`),
  KEY `time_cards_max_shift_exceeded_index` (`max_shift_exceeded`),
  CONSTRAINT `time_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_cards`
--

LOCK TABLES `time_cards` WRITE;
/*!40000 ALTER TABLE `time_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_type` enum('retail','wholesale','b2b') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wholesale_discount_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','charybshawn@gmail.com','250-000-0000','2025-06-29 02:32:11','$2y$12$pAjqzIpNb8pu5khlxle8MOO/oprhFop17gb7RSULVrIlUMUvu2Koe',NULL,0.00,NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-29 02:32:11','2025-06-29 02:32:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'catapult-dev'
--
