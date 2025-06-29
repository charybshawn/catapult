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
  `subject_id` bigint unsigned DEFAULT NULL,
  `event` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_subject_created_index` (`subject_type`,`subject_id`,`created_at`),
  KEY `activity_log_causer_index` (`causer_type`,`causer_id`),
  CONSTRAINT `activity_log_chk_1` CHECK (json_valid(`properties`))
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (3,'default','created','App\\Models\\User',2,'created',NULL,NULL,'{\"attributes\": {\"name\": \"Admin User\", \"email\": \"charybshawn@gmail.com\", \"phone\": \"250-515-4007\"}}',NULL,'2025-06-23 09:33:24','2025-06-23 09:33:24'),(4,'default','created','App\\Models\\Supplier',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sprouting Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-23 10:01:59','2025-06-23 10:01:59'),(5,'default','created','App\\Models\\Consumable',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sunflower (Black Oilseed)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Black Oilseed\", \"is_active\": true, \"supplier_id\": 1, \"cost_per_unit\": null, \"initial_stock\": \"20.400\", \"quantity_unit\": \"kg\", \"seed_entry_id\": null, \"total_quantity\": \"20.400\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"0.200\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 1}}',NULL,'2025-06-24 23:59:09','2025-06-24 23:59:09'),(6,'default','created','App\\Models\\Consumable',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"PRO MIX HP\", \"type\": \"soil\", \"unit\": \"bags\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": null, \"cost_per_unit\": null, \"initial_stock\": \"1.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"107.000\", \"last_ordered_at\": null, \"restock_quantity\": \"4.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"107.000\", \"restock_threshold\": \"1.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 00:00:19','2025-06-25 00:00:19'),(7,'default','created','App\\Models\\Recipe',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"SUNFLOWER - BLACK OIL - SF4K - 100G\", \"is_active\": true, \"light_days\": 5, \"blackout_days\": 1, \"seed_entry_id\": null, \"days_to_maturity\": 9, \"germination_days\": 3, \"supplier_soil_id\": null, \"expected_yield_grams\": 450, \"seed_density_grams_per_tray\": 100}}',NULL,'2025-06-25 00:00:50','2025-06-25 00:00:50'),(8,'default','created','App\\Models\\Crop',1,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 1, \"planted_at\": \"2025-06-24T17:00:59.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:00:59.000000Z\", \"tray_number\": \"11\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:00:59.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:01:12','2025-06-25 00:01:12'),(9,'default','created','App\\Models\\Crop',2,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 1, \"planted_at\": \"2025-06-24T17:00:59.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:00:59.000000Z\", \"tray_number\": \"12\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:00:59.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:01:12','2025-06-25 00:01:12'),(10,'default','updated','App\\Models\\Consumable',1,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"20.400\", \"consumed_quantity\": \"0.200\"}, \"attributes\": {\"total_quantity\": \"20.200\", \"consumed_quantity\": \"0.400\"}}',NULL,'2025-06-25 00:01:12','2025-06-25 00:01:12'),(11,'default','created','App\\Models\\Supplier',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Germina\", \"type\": \"other\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 00:51:32','2025-06-25 00:51:32'),(12,'default','created','App\\Models\\Consumable',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sunflower (Black Oilseed)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Black Oilseed\", \"is_active\": true, \"supplier_id\": 2, \"cost_per_unit\": null, \"initial_stock\": \"10.000\", \"quantity_unit\": \"kg\", \"seed_entry_id\": null, \"total_quantity\": \"10.000\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"8.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 1}}',NULL,'2025-06-25 00:55:02','2025-06-25 00:55:02'),(13,'default','created','App\\Models\\Recipe',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\", \"is_active\": true, \"light_days\": 6, \"blackout_days\": 0, \"seed_entry_id\": null, \"days_to_maturity\": 9, \"germination_days\": 3, \"supplier_soil_id\": null, \"expected_yield_grams\": null, \"seed_density_grams_per_tray\": 100}}',NULL,'2025-06-25 00:56:15','2025-06-25 00:56:15'),(14,'default','created','App\\Models\\Crop',3,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"13\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(15,'default','created','App\\Models\\Crop',4,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"14\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(16,'default','created','App\\Models\\Crop',5,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"15\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(17,'default','created','App\\Models\\Crop',6,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"16\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(18,'default','created','App\\Models\\Crop',7,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"17\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(19,'default','created','App\\Models\\Crop',8,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"18\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(20,'default','created','App\\Models\\Crop',9,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"19\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(21,'default','created','App\\Models\\Crop',10,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"20\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(22,'default','updated','App\\Models\\Consumable',3,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"10.000\", \"consumed_quantity\": \"8.000\"}, \"attributes\": {\"total_quantity\": \"9.200\", \"consumed_quantity\": \"8.800\"}}',NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(23,'default','created','App\\Models\\User',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"HANOI 36\", \"email\": \"hanoi36sa@gmail.com\", \"phone\": null}}',NULL,'2025-06-25 00:59:04','2025-06-25 00:59:04'),(24,'default','created','App\\Models\\Supplier',3,'created',NULL,NULL,'{\"attributes\": {\"name\": \"Uline\", \"type\": \"consumable\", \"is_active\": true, \"contact_name\": \"Customer Service\", \"contact_email\": \"customerservice@uline.com\", \"contact_phone\": \"1-800-295-5510\"}}',NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(25,'default','created','App\\Models\\PackagingType',1,'created',NULL,NULL,'{\"attributes\": {\"name\": \"16oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 16}}',NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(26,'default','created','App\\Models\\Consumable',4,'created',NULL,NULL,'{\"attributes\": {\"name\": \"16oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.35\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 1, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(27,'default','created','App\\Models\\PackagingType',2,'created',NULL,NULL,'{\"attributes\": {\"name\": \"24oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 24}}',NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(28,'default','created','App\\Models\\Consumable',5,'created',NULL,NULL,'{\"attributes\": {\"name\": \"24oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.45\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 2, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(29,'default','created','App\\Models\\PackagingType',3,'created',NULL,NULL,'{\"attributes\": {\"name\": \"32oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 32}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(30,'default','created','App\\Models\\Consumable',6,'created',NULL,NULL,'{\"attributes\": {\"name\": \"32oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.55\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 3, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(31,'default','created','App\\Models\\PackagingType',4,'created',NULL,NULL,'{\"attributes\": {\"name\": \"48oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 48}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(32,'default','created','App\\Models\\Consumable',7,'created',NULL,NULL,'{\"attributes\": {\"name\": \"48oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.65\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 4, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(33,'default','created','App\\Models\\PackagingType',5,'created',NULL,NULL,'{\"attributes\": {\"name\": \"64oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 64}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(34,'default','created','App\\Models\\Consumable',8,'created',NULL,NULL,'{\"attributes\": {\"name\": \"64oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.75\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 5, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(35,'default','created','App\\Models\\Supplier',4,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"William Dam Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:01:09','2025-06-25 01:01:09'),(36,'default','updated','App\\Models\\Supplier',2,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Germina\", \"type\": \"other\"}, \"attributes\": {\"name\": \"Germina Seeds\", \"type\": \"seed\"}}',NULL,'2025-06-25 01:01:28','2025-06-25 01:01:28'),(37,'default','updated','App\\Models\\Supplier',1,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Sprouting Seeds\"}, \"attributes\": {\"name\": \"Mumm\'s Sprouting Seeds\"}}',NULL,'2025-06-25 01:01:46','2025-06-25 01:01:46'),(38,'default','updated','App\\Models\\Supplier',3,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Uline\", \"type\": \"consumable\", \"contact_name\": \"Customer Service\", \"contact_email\": \"customerservice@uline.com\", \"contact_phone\": \"1-800-295-5510\"}, \"attributes\": {\"name\": \"Britelands\", \"type\": \"packaging\", \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:06:10','2025-06-25 01:06:10'),(39,'default','created','App\\Models\\Supplier',5,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Ecoline\", \"type\": \"soil\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:06:28','2025-06-25 01:06:28'),(40,'default','created','App\\Models\\Supplier',6,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Buckerfields\", \"type\": \"other\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:06:47','2025-06-25 01:06:47'),(41,'default','updated','App\\Models\\Supplier',6,'updated','App\\Models\\User',2,'{\"old\": {\"type\": \"other\"}, \"attributes\": {\"type\": \"soil\"}}',NULL,'2025-06-25 01:07:03','2025-06-25 01:07:03'),(42,'default','created','App\\Models\\Supplier',7,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Johnny\'s Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:07:22','2025-06-25 01:07:22'),(43,'default','created','App\\Models\\Supplier',8,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"True Leaf Market\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-25 01:07:43','2025-06-25 01:07:43'),(44,'default','created','App\\Models\\Consumable',9,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Basil (Genovese)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Genovese\", \"is_active\": true, \"supplier_id\": 1, \"cost_per_unit\": null, \"initial_stock\": \"1000.000\", \"quantity_unit\": \"g\", \"seed_entry_id\": null, \"total_quantity\": \"1000.000\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"515.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 2}}',NULL,'2025-06-25 04:15:30','2025-06-25 04:15:30'),(45,'default','created','App\\Models\\Recipe',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\", \"is_active\": true, \"light_days\": 17, \"blackout_days\": 0, \"seed_entry_id\": null, \"days_to_maturity\": 21, \"germination_days\": 4, \"supplier_soil_id\": null, \"expected_yield_grams\": 80, \"seed_density_grams_per_tray\": 5}}',NULL,'2025-06-25 04:16:57','2025-06-25 04:16:57'),(46,'default','created','App\\Models\\Crop',11,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 3, \"planted_at\": \"2025-06-24T21:54:53.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T21:54:53.000000Z\", \"tray_number\": \"21\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T21:54:53.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 04:55:14','2025-06-25 04:55:14'),(47,'default','created','App\\Models\\Crop',12,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 3, \"planted_at\": \"2025-06-24T21:54:53.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T21:54:53.000000Z\", \"tray_number\": \"22\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T21:54:53.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-25 04:55:14','2025-06-25 04:55:14'),(48,'default','updated','App\\Models\\Consumable',9,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"1000.000\", \"consumed_quantity\": \"515.000\"}, \"attributes\": {\"total_quantity\": \"990.000\", \"consumed_quantity\": \"525.000\"}}',NULL,'2025-06-25 04:55:14','2025-06-25 04:55:14'),(49,'default','created','App\\Models\\PriceVariation',1,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":2,\"name\":\"Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-25 01:40:05','2025-06-25 01:40:05'),(50,'default','created','App\\Models\\PriceVariation',2,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":3,\"name\":\"32oz Clamshell (32oz) (Ret)\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-25 01:40:47','2025-06-25 01:40:47'),(51,'default','updated','App\\Models\\PriceVariation',2,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell (32oz) (Ret)\"},\"old\":{\"name\":\"32oz Clamshell (32oz) (Ret)\"}}',NULL,'2025-06-25 01:42:14','2025-06-25 01:42:14'),(52,'default','updated','App\\Models\\PriceVariation',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell 24oz (Ret)\"},\"old\":{\"name\":\"Clamshell\"}}',NULL,'2025-06-25 01:42:26','2025-06-25 01:42:26'),(53,'default','updated','App\\Models\\PriceVariation',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell (24oz) (Ret)\"},\"old\":{\"name\":\"Clamshell 24oz (Ret)\"}}',NULL,'2025-06-25 01:42:41','2025-06-25 01:42:41'),(54,'default','updated','App\\Models\\PriceVariation',2,'updated','App\\Models\\User',2,'{\"attributes\":{\"fill_weight_grams\":\"70.00\"},\"old\":{\"fill_weight_grams\":null}}',NULL,'2025-06-25 01:42:58','2025-06-25 01:42:58'),(55,'default','created','App\\Models\\PriceVariation',3,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":null,\"name\":\"Bulk\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"0.20\",\"pricing_unit\":\"per_g\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-25 01:44:16','2025-06-25 01:44:16'),(56,'default','created','App\\Models\\PriceVariation',4,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":null,\"name\":\"Live Tray\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"30.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-25 01:44:47','2025-06-25 01:44:47'),(57,'default','created','App\\Models\\Category',1,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Microgreens\",\"description\":null,\"is_active\":true}}',NULL,'2025-06-25 01:45:23','2025-06-25 01:45:23'),(58,'default','created','App\\Models\\Product',1,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Sunflower\",\"description\":null,\"active\":true,\"is_visible_in_store\":true,\"category_id\":1,\"product_mix_id\":null,\"master_seed_catalog_id\":1,\"image\":null,\"base_price\":null,\"wholesale_price\":null,\"bulk_price\":null,\"special_price\":null,\"wholesale_discount_percentage\":\"30.00\"}}',NULL,'2025-06-25 01:45:58','2025-06-25 01:45:58'),(59,'default','created','App\\Models\\PriceVariation',5,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":1,\"packaging_type_id\":2,\"name\":\"24oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":true,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-25 01:45:58','2025-06-25 01:45:58'),(60,'default','created','App\\Models\\PriceVariation',6,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":2,\"packaging_type_id\":3,\"name\":\"32oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-25 01:45:58','2025-06-25 01:45:58'),(61,'default','created','App\\Models\\PriceVariation',7,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":3,\"packaging_type_id\":null,\"name\":\"Default\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"0.20\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-25 01:45:58','2025-06-25 01:45:58'),(62,'default','created','App\\Models\\PriceVariation',8,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":4,\"packaging_type_id\":null,\"name\":\"Default\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"30.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-25 01:45:58','2025-06-25 01:45:58'),(63,'default','updated','App\\Models\\PriceVariation',5,'updated','App\\Models\\User',2,'{\"attributes\":{\"packaging_type_id\":null,\"name\":\"Default\",\"fill_weight_grams\":\"80.00\",\"price\":\"30.00\"},\"old\":{\"packaging_type_id\":2,\"name\":\"24oz Clamshell\",\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\"}}',NULL,'2025-06-25 01:46:21','2025-06-25 01:46:21'),(64,'default','deleted','App\\Models\\PriceVariation',6,'deleted','App\\Models\\User',2,'{\"old\":{\"product_id\":1,\"template_id\":2,\"packaging_type_id\":3,\"name\":\"32oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-25 01:46:26','2025-06-25 01:46:26'),(65,'default','created','App\\Models\\Harvest',1,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"433.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:24:25','2025-06-25 20:24:25'),(66,'default','created','App\\Models\\Harvest',2,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"422.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:28:03','2025-06-25 20:28:03'),(67,'default','created','App\\Models\\Harvest',3,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"430.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:29:58','2025-06-25 20:29:58'),(68,'default','created','App\\Models\\Harvest',4,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"420.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:33:18','2025-06-25 20:33:18'),(69,'default','created','App\\Models\\Harvest',5,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"379.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:40:49','2025-06-25 20:40:49'),(70,'default','created','App\\Models\\Harvest',6,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"428.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:43:39','2025-06-25 20:43:39'),(71,'default','created','App\\Models\\Harvest',7,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"401.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:47:38','2025-06-25 20:47:38'),(72,'default','created','App\\Models\\Harvest',8,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":20,\"user_id\":2,\"total_weight_grams\":\"188.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 20:55:12','2025-06-25 20:55:12'),(73,'default','created','App\\Models\\Harvest',9,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":20,\"user_id\":2,\"total_weight_grams\":\"163.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":\"Small deadspot on 1 end affected growth\"}}',NULL,'2025-06-25 20:58:55','2025-06-25 20:58:55'),(74,'default','created','App\\Models\\Harvest',10,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":5,\"user_id\":2,\"total_weight_grams\":\"281.00\",\"tray_count\":3,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":\"Lower yield due to first week out of germ chamber. Left in germination too long. And heaters malfunctioned cranking heat mid week\"}}',NULL,'2025-06-25 21:09:02','2025-06-25 21:09:02'),(75,'default','created','App\\Models\\Harvest',11,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":23,\"user_id\":2,\"total_weight_grams\":\"608.00\",\"tray_count\":4,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 21:15:32','2025-06-25 21:15:32'),(76,'default','created','App\\Models\\Harvest',12,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 21:31:08','2025-06-25 21:31:08'),(77,'default','created','App\\Models\\Harvest',13,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"519.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 21:53:41','2025-06-25 21:53:41'),(78,'default','created','App\\Models\\Harvest',14,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"520.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:02:45','2025-06-25 22:02:45'),(79,'default','created','App\\Models\\Harvest',15,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:25:28','2025-06-25 22:25:28'),(80,'default','created','App\\Models\\Harvest',16,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"586.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:36:16','2025-06-25 22:36:16'),(81,'default','created','App\\Models\\Harvest',17,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"595.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:45:12','2025-06-25 22:45:12'),(82,'default','created','App\\Models\\Harvest',18,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"574.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:56:06','2025-06-25 22:56:06'),(83,'default','created','App\\Models\\Harvest',19,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"522.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 22:57:53','2025-06-25 22:57:53'),(84,'default','created','App\\Models\\Harvest',20,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"375.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:02:41','2025-06-25 23:02:41'),(85,'default','created','App\\Models\\Harvest',21,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"375.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:05:40','2025-06-25 23:05:40'),(86,'default','created','App\\Models\\Harvest',22,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"545.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:12:17','2025-06-25 23:12:17'),(87,'default','created','App\\Models\\Harvest',23,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":25,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:17:12','2025-06-25 23:17:12'),(88,'default','created','App\\Models\\Harvest',24,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"381.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:24:48','2025-06-25 23:24:48'),(89,'default','created','App\\Models\\Harvest',25,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"469.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:26:36','2025-06-25 23:26:36'),(90,'default','created','App\\Models\\Harvest',26,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"331.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:28:51','2025-06-25 23:28:51'),(91,'default','created','App\\Models\\Harvest',27,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":5,\"user_id\":2,\"total_weight_grams\":\"84.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 23:36:38','2025-06-25 23:36:38'),(92,'default','created','App\\Models\\Harvest',28,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"568.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-26 00:45:28','2025-06-26 00:45:28'),(93,'default','created','App\\Models\\Consumable',10,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Coriander (Coriander)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":10,\"cultivar\":\"Coriander\",\"initial_stock\":\"5.000\",\"consumed_quantity\":\"1.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"kg\",\"total_quantity\":\"5.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-26 17:07:19','2025-06-26 17:07:19'),(94,'default','created','App\\Models\\Recipe',4,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"CORIANDER - TRUE LEAF - 45G \",\"seed_entry_id\":null,\"supplier_soil_id\":null,\"germination_days\":6,\"blackout_days\":0,\"days_to_maturity\":14,\"light_days\":8,\"expected_yield_grams\":150,\"seed_density_grams_per_tray\":45,\"is_active\":true}}',NULL,'2025-06-26 21:47:56','2025-06-26 21:47:56'),(95,'default','created','App\\Models\\Crop',13,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":4,\"order_id\":null,\"tray_number\":\"BL1\",\"planted_at\":\"2025-06-26T21:48:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-26T21:48:04.000000Z\",\"germination_at\":\"2025-06-26T21:48:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(96,'default','created','App\\Models\\Crop',14,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":4,\"order_id\":null,\"tray_number\":\"BL2\",\"planted_at\":\"2025-06-26T21:48:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-26T21:48:04.000000Z\",\"germination_at\":\"2025-06-26T21:48:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(97,'default','created','App\\Models\\Crop',15,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":4,\"order_id\":null,\"tray_number\":\"BL3\",\"planted_at\":\"2025-06-26T21:48:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-26T21:48:04.000000Z\",\"germination_at\":\"2025-06-26T21:48:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(98,'default','updated','App\\Models\\Consumable',10,'updated','App\\Models\\User',2,'{\"attributes\":{\"consumed_quantity\":\"1.140\",\"total_quantity\":\"4.870\"},\"old\":{\"consumed_quantity\":\"1.000\",\"total_quantity\":\"5.000\"}}',NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(99,'default','updated','App\\Models\\Recipe',4,'updated','App\\Models\\User',2,'{\"attributes\":{\"expected_yield_grams\":135},\"old\":{\"expected_yield_grams\":150}}',NULL,'2025-06-27 15:28:45','2025-06-27 15:28:45'),(100,'default','created','App\\Models\\Consumable',11,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Cabbage (Red)\",\"type\":\"seed\",\"supplier_id\":4,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":8,\"cultivar\":\"Red\",\"initial_stock\":\"2000.000\",\"consumed_quantity\":\"0.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"2000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-27 16:16:22','2025-06-27 16:16:22'),(101,'default','created','App\\Models\\Recipe',5,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"CABBAGE RED - 18G\",\"seed_entry_id\":null,\"supplier_soil_id\":null,\"germination_days\":3,\"blackout_days\":0,\"days_to_maturity\":11,\"light_days\":8,\"expected_yield_grams\":180,\"seed_density_grams_per_tray\":18,\"is_active\":true}}',NULL,'2025-06-27 16:25:58','2025-06-27 16:25:58'),(102,'default','created','App\\Models\\Crop',16,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":5,\"order_id\":null,\"tray_number\":\"BL9\",\"planted_at\":\"2025-06-27T16:26:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-27T16:26:04.000000Z\",\"germination_at\":\"2025-06-27T16:26:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(103,'default','created','App\\Models\\Crop',17,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":5,\"order_id\":null,\"tray_number\":\"BL17\",\"planted_at\":\"2025-06-27T16:26:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-27T16:26:04.000000Z\",\"germination_at\":\"2025-06-27T16:26:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(104,'default','created','App\\Models\\Crop',18,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":5,\"order_id\":null,\"tray_number\":\"BL12\",\"planted_at\":\"2025-06-27T16:26:04.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-27T16:26:04.000000Z\",\"germination_at\":\"2025-06-27T16:26:04.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(105,'default','updated','App\\Models\\Consumable',11,'updated','App\\Models\\User',2,'{\"attributes\":{\"consumed_quantity\":\"54.000\",\"total_quantity\":\"1946.000\"},\"old\":{\"consumed_quantity\":\"0.000\",\"total_quantity\":\"2000.000\"}}',NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(106,'default','created','App\\Models\\Consumable',12,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Peas, (Speckled)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":20,\"cultivar\":\"Speckled\",\"initial_stock\":\"10000.000\",\"consumed_quantity\":\"2891.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"10000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-27 16:35:57','2025-06-27 16:35:57'),(107,'default','created','App\\Models\\Consumable',13,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Broccoli (Broccoli)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":7,\"cultivar\":\"Broccoli\",\"initial_stock\":\"1000.000\",\"consumed_quantity\":\"0.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"1000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-28 21:28:14','2025-06-28 21:28:14'),(108,'default','created','App\\Models\\Consumable',14,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Kale (Green)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":16,\"cultivar\":\"Red\",\"initial_stock\":\"1000.000\",\"consumed_quantity\":\"585.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"1000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-28 22:17:36','2025-06-28 22:17:36'),(109,'default','created','App\\Models\\Recipe',6,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"KALE (RED) - 20G\",\"seed_entry_id\":null,\"supplier_soil_id\":null,\"germination_days\":3,\"blackout_days\":1,\"days_to_maturity\":11,\"light_days\":7,\"expected_yield_grams\":180,\"seed_density_grams_per_tray\":20,\"is_active\":true}}',NULL,'2025-06-28 22:29:29','2025-06-28 22:29:29'),(110,'default','created','App\\Models\\Crop',19,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":6,\"order_id\":null,\"tray_number\":\"BL8\",\"planted_at\":\"2025-06-28T22:29:34.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:29:34.000000Z\",\"germination_at\":\"2025-06-28T22:29:34.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:29:54','2025-06-28 22:29:54'),(111,'default','created','App\\Models\\Crop',20,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":6,\"order_id\":null,\"tray_number\":\"BL5\",\"planted_at\":\"2025-06-28T22:29:34.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:29:34.000000Z\",\"germination_at\":\"2025-06-28T22:29:34.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:29:54','2025-06-28 22:29:54'),(112,'default','updated','App\\Models\\Consumable',14,'updated','App\\Models\\User',2,'{\"attributes\":{\"consumed_quantity\":\"625.000\",\"total_quantity\":\"960.000\"},\"old\":{\"consumed_quantity\":\"585.000\",\"total_quantity\":\"1000.000\"}}',NULL,'2025-06-28 22:29:54','2025-06-28 22:29:54'),(113,'default','created','App\\Models\\Consumable',15,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Broccoli (Broccoli)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":7,\"cultivar\":\"Broccoli\",\"initial_stock\":\"1000.000\",\"consumed_quantity\":\"50.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"1000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-28 22:31:17','2025-06-28 22:31:17'),(114,'default','deleted','App\\Models\\Consumable',15,'deleted','App\\Models\\User',2,'{\"old\":{\"name\":\"Broccoli (Broccoli)\",\"type\":\"seed\",\"supplier_id\":1,\"packaging_type_id\":null,\"seed_entry_id\":null,\"master_seed_catalog_id\":7,\"cultivar\":\"Broccoli\",\"initial_stock\":\"1000.000\",\"consumed_quantity\":\"50.000\",\"unit\":\"g\",\"restock_threshold\":\"5.000\",\"restock_quantity\":\"10.000\",\"cost_per_unit\":null,\"quantity_per_unit\":\"1.000\",\"quantity_unit\":\"g\",\"total_quantity\":\"1000.000\",\"is_active\":true,\"last_ordered_at\":null}}',NULL,'2025-06-28 22:31:28','2025-06-28 22:31:28'),(115,'default','created','App\\Models\\Recipe',7,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"BROCCOLI - 20g\",\"seed_entry_id\":null,\"supplier_soil_id\":null,\"germination_days\":3,\"blackout_days\":1,\"days_to_maturity\":11,\"light_days\":7,\"expected_yield_grams\":200,\"seed_density_grams_per_tray\":20,\"is_active\":true}}',NULL,'2025-06-28 22:32:13','2025-06-28 22:32:13'),(116,'default','created','App\\Models\\Crop',21,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"27\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(117,'default','created','App\\Models\\Crop',22,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"28\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(118,'default','created','App\\Models\\Crop',23,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"29\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(119,'default','created','App\\Models\\Crop',24,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"26\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(120,'default','created','App\\Models\\Crop',25,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"30\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(121,'default','created','App\\Models\\Crop',26,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"31\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(122,'default','created','App\\Models\\Crop',27,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"32\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(123,'default','created','App\\Models\\Crop',28,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"33\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(124,'default','created','App\\Models\\Crop',29,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"23\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(125,'default','created','App\\Models\\Crop',30,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"25\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(126,'default','created','App\\Models\\Crop',31,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"bl17\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(127,'default','created','App\\Models\\Crop',32,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":7,\"order_id\":null,\"tray_number\":\"24\",\"planted_at\":\"2025-06-28T22:32:19.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:32:19.000000Z\",\"germination_at\":\"2025-06-28T22:32:19.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(128,'default','updated','App\\Models\\Consumable',13,'updated','App\\Models\\User',2,'{\"attributes\":{\"consumed_quantity\":\"240.000\",\"total_quantity\":\"760.000\"},\"old\":{\"consumed_quantity\":\"0.000\",\"total_quantity\":\"1000.000\"}}',NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(129,'default','created','App\\Models\\Recipe',8,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"PEA - SPECKLED - 300G\",\"seed_entry_id\":null,\"supplier_soil_id\":null,\"germination_days\":4,\"blackout_days\":0,\"days_to_maturity\":11,\"light_days\":7,\"expected_yield_grams\":350,\"seed_density_grams_per_tray\":300,\"is_active\":true}}',NULL,'2025-06-28 22:52:23','2025-06-28 22:52:23'),(130,'default','created','App\\Models\\Crop',33,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"BL\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(131,'default','created','App\\Models\\Crop',34,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"BL16\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(132,'default','created','App\\Models\\Crop',35,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"1\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(133,'default','created','App\\Models\\Crop',36,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"9\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(134,'default','created','App\\Models\\Crop',37,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"BL11\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(135,'default','created','App\\Models\\Crop',38,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"BL13\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(136,'default','created','App\\Models\\Crop',39,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"BL15\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(137,'default','created','App\\Models\\Crop',40,'created','App\\Models\\User',2,'{\"attributes\":{\"recipe_id\":8,\"order_id\":null,\"tray_number\":\"4\",\"planted_at\":\"2025-06-28T22:52:29.000000Z\",\"current_stage\":\"germination\",\"planting_at\":\"2025-06-28T22:52:29.000000Z\",\"germination_at\":\"2025-06-28T22:52:29.000000Z\",\"blackout_at\":null,\"light_at\":null,\"harvested_at\":null,\"harvest_weight_grams\":null,\"watering_suspended_at\":null}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(138,'default','updated','App\\Models\\Consumable',12,'updated','App\\Models\\User',2,'{\"attributes\":{\"consumed_quantity\":\"5291.000\",\"total_quantity\":\"7600.000\"},\"old\":{\"consumed_quantity\":\"2891.000\",\"total_quantity\":\"10000.000\"}}',NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24');
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
INSERT INTO `cache` VALUES ('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:1;',1751164026),('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1751164026;',1751164026),('laravel_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:5:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"manage products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"view products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"edit products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"delete products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"access filament\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:7:\"manager\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:4:\"user\";s:1:\"c\";s:3:\"web\";}}}',1751250366);
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Microgreens',NULL,1,'2025-06-25 01:45:23','2025-06-25 01:45:23');
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
  `packaging_type_id` bigint unsigned DEFAULT NULL COMMENT 'For packaging consumables only',
  `seed_entry_id` bigint unsigned DEFAULT NULL,
  `master_seed_catalog_id` bigint unsigned DEFAULT NULL,
  `cultivar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_cultivar_id` bigint unsigned DEFAULT NULL,
  `initial_stock` decimal(12,2) NOT NULL COMMENT 'Quantity - Number of units in stock',
  `consumed_quantity` decimal(12,2) NOT NULL COMMENT 'Used quantity - Number of units consumed',
  `unit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit' COMMENT 'Packaging type (e.g., bag, box, bottle)',
  `restock_threshold` decimal(12,3) NOT NULL,
  `restock_quantity` decimal(12,3) NOT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `quantity_per_unit` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Unit size - Capacity or size of each unit (e.g., 107L per bag)',
  `quantity_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'l' COMMENT 'Unit of measurement (e.g., g, kg, l, ml, oz, lb)',
  `total_quantity` decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Calculated total: (initial_stock - consumed_quantity) * quantity_per_unit',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `lot_no` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_ordered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `consumables_type_seed_variety_id_index` (`type`),
  KEY `consumables_type_active_index` (`type`,`is_active`),
  KEY `consumables_supplier_type_index` (`supplier_id`,`type`),
  KEY `consumables_packaging_type_index` (`packaging_type_id`),
  KEY `consumables_seed_entry_id_index` (`seed_entry_id`),
  KEY `consumables_deleted_at_index` (`deleted_at`),
  KEY `consumables_master_cultivar_id_foreign` (`master_cultivar_id`),
  KEY `consumables_master_seed_catalog_id_master_cultivar_id_index` (`master_seed_catalog_id`,`master_cultivar_id`),
  KEY `consumables_master_seed_catalog_id_cultivar_index` (`master_seed_catalog_id`,`cultivar`),
  CONSTRAINT `consumables_master_cultivar_id_foreign` FOREIGN KEY (`master_cultivar_id`) REFERENCES `master_cultivars` (`id`),
  CONSTRAINT `consumables_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumables_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumables`
--

LOCK TABLES `consumables` WRITE;
/*!40000 ALTER TABLE `consumables` DISABLE KEYS */;
INSERT INTO `consumables` VALUES (1,'Sunflower (Black Oilseed)','seed',1,NULL,NULL,1,'Black Oilseed',NULL,20.40,0.40,'g',5.000,10.000,NULL,1.00,'kg',20.20,NULL,'SF4K',1,NULL,'2025-06-24 23:59:09','2025-06-25 00:01:12',NULL),(2,'PRO MIX HP','soil',NULL,NULL,NULL,NULL,NULL,NULL,1.00,0.00,'bags',1.000,4.000,NULL,107.00,'l',107.00,NULL,NULL,1,NULL,'2025-06-25 00:00:19','2025-06-25 00:00:19',NULL),(3,'Sunflower (Black Oilseed)','seed',2,NULL,NULL,1,'Black Oilseed',NULL,10.00,8.80,'g',5.000,10.000,NULL,1.00,'kg',9.20,NULL,'SFR16',1,NULL,'2025-06-25 00:55:02','2025-06-25 00:57:05',NULL),(4,'16oz Clamshell','packaging',3,1,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.35,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03',NULL),(5,'24oz Clamshell','packaging',3,2,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.45,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-25 01:01:03','2025-06-25 01:01:03',NULL),(6,'32oz Clamshell','packaging',3,3,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.55,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04',NULL),(7,'48oz Clamshell','packaging',3,4,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.65,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04',NULL),(8,'64oz Clamshell','packaging',3,5,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.75,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-25 01:01:04','2025-06-25 01:01:04',NULL),(9,'Basil (Genovese)','seed',1,NULL,NULL,2,'Genovese',NULL,1000.00,525.00,'g',5.000,10.000,NULL,1.00,'g',990.00,NULL,'BAS8Y',1,NULL,'2025-06-25 04:15:30','2025-06-25 04:55:14',NULL),(10,'Coriander (Coriander)','seed',1,NULL,NULL,10,'Coriander',NULL,5.00,1.14,'g',5.000,10.000,NULL,1.00,'kg',4.87,NULL,'COR3',1,NULL,'2025-06-26 17:07:19','2025-06-26 21:48:26',NULL),(11,'Cabbage (Red)','seed',4,NULL,NULL,8,'Red',NULL,2000.00,54.00,'g',5.000,10.000,NULL,1.00,'g',1946.00,NULL,'40297',1,NULL,'2025-06-27 16:16:22','2025-06-27 16:26:31',NULL),(12,'Peas, (Speckled)','seed',1,NULL,NULL,20,'Speckled',NULL,10000.00,5291.00,'g',5.000,10.000,NULL,1.00,'g',7600.00,NULL,'PS25',1,NULL,'2025-06-27 16:35:57','2025-06-28 22:53:24',NULL),(13,'Broccoli (Broccoli)','seed',1,NULL,NULL,7,'Broccoli',NULL,1000.00,240.00,'g',5.000,10.000,NULL,1.00,'g',760.00,NULL,'B4X2-01',1,NULL,'2025-06-28 21:28:14','2025-06-28 22:35:34',NULL),(14,'Kale (Green)','seed',1,NULL,NULL,16,'Red',NULL,1000.00,625.00,'g',5.000,10.000,NULL,1.00,'g',960.00,NULL,'KR3Y-01',1,NULL,'2025-06-28 22:17:36','2025-06-28 22:29:54',NULL);
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
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `crop_alerts_chk_1` CHECK (json_valid(`conditions`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Future table for crop alerts. Currently using task_schedules table.';
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
  `order_id` bigint unsigned NOT NULL,
  `recipe_id` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `trays_needed` int NOT NULL DEFAULT '1',
  `grams_needed` decimal(8,2) NOT NULL,
  `grams_per_tray` decimal(8,2) DEFAULT NULL,
  `plant_by_date` date NOT NULL,
  `expected_harvest_date` date NOT NULL,
  `delivery_date` date NOT NULL,
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `order_items_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` bigint unsigned DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crop_plans_recipe_id_foreign` (`recipe_id`),
  KEY `crop_plans_created_by_foreign` (`created_by`),
  KEY `crop_plans_approved_by_foreign` (`approved_by`),
  KEY `crop_plans_order_id_recipe_id_index` (`order_id`,`recipe_id`),
  KEY `crop_plans_status_plant_by_date_index` (`status`,`plant_by_date`),
  KEY `crop_plans_plant_by_date_index` (`plant_by_date`),
  CONSTRAINT `crop_plans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crop_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crop_plans_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_plans_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_plans_chk_1` CHECK (json_valid(`calculation_details`)),
  CONSTRAINT `crop_plans_chk_2` CHECK (json_valid(`order_items_included`))
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
  `recipe_id` bigint unsigned NOT NULL,
  `task_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `scheduled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crop_tasks_recipe_id_foreign` (`recipe_id`),
  KEY `crop_tasks_scheduled_at_index` (`scheduled_at`),
  KEY `crop_tasks_status_index` (`status`),
  KEY `crop_tasks_crop_id_task_type_index` (`crop_id`,`task_type`),
  CONSTRAINT `crop_tasks_crop_id_foreign` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_tasks_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_tasks_chk_1` CHECK (json_valid(`details`))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_tasks`
--

LOCK TABLES `crop_tasks` WRITE;
/*!40000 ALTER TABLE `crop_tasks` DISABLE KEYS */;
INSERT INTO `crop_tasks` VALUES (1,1,1,'end_germination','{\"target_stage\": \"blackout\"}','2025-06-28 00:00:59',NULL,'pending','2025-06-25 00:01:12','2025-06-25 00:01:12'),(2,1,1,'end_blackout','{\"target_stage\": \"light\"}','2025-06-29 00:00:59',NULL,'pending','2025-06-25 00:01:12','2025-06-25 00:01:12'),(3,1,1,'expected_harvest',NULL,'2025-07-04 00:00:59',NULL,'pending','2025-06-25 00:01:12','2025-06-25 00:01:12'),(4,1,1,'suspend_watering',NULL,'2025-07-03 00:00:59',NULL,'pending','2025-06-25 00:01:12','2025-06-25 00:01:12'),(5,3,2,'end_germination','{\"target_stage\": \"light\"}','2025-06-28 00:56:42',NULL,'pending','2025-06-25 00:57:05','2025-06-25 00:57:05'),(6,3,2,'expected_harvest',NULL,'2025-07-04 00:56:42',NULL,'pending','2025-06-25 00:57:05','2025-06-25 00:57:05'),(7,3,2,'suspend_watering',NULL,'2025-07-03 00:56:42',NULL,'pending','2025-06-25 00:57:05','2025-06-25 00:57:05'),(8,11,3,'end_germination','{\"target_stage\": \"light\"}','2025-06-29 04:54:53',NULL,'pending','2025-06-25 04:55:15','2025-06-25 04:55:15'),(9,11,3,'expected_harvest',NULL,'2025-07-16 04:54:53',NULL,'pending','2025-06-25 04:55:15','2025-06-25 04:55:15'),(10,13,4,'end_germination','{\"target_stage\":\"light\"}','2025-07-02 21:48:04',NULL,'pending','2025-06-26 21:48:26','2025-06-26 21:48:26'),(11,13,4,'expected_harvest',NULL,'2025-07-10 21:48:04',NULL,'pending','2025-06-26 21:48:26','2025-06-26 21:48:26'),(12,13,4,'suspend_watering',NULL,'2025-07-09 21:48:04',NULL,'pending','2025-06-26 21:48:26','2025-06-26 21:48:26'),(13,16,5,'end_germination','{\"target_stage\":\"light\"}','2025-06-30 16:26:04',NULL,'pending','2025-06-27 16:26:31','2025-06-27 16:26:31'),(14,16,5,'expected_harvest',NULL,'2025-07-08 16:26:04',NULL,'pending','2025-06-27 16:26:31','2025-06-27 16:26:31'),(15,16,5,'suspend_watering',NULL,'2025-07-07 12:26:04',NULL,'pending','2025-06-27 16:26:31','2025-06-27 16:26:31'),(16,19,6,'end_germination','{\"target_stage\":\"blackout\"}','2025-07-01 22:29:34',NULL,'pending','2025-06-28 22:29:54','2025-06-28 22:29:54'),(17,19,6,'end_blackout','{\"target_stage\":\"light\"}','2025-07-02 22:29:34',NULL,'pending','2025-06-28 22:29:54','2025-06-28 22:29:54'),(18,19,6,'expected_harvest',NULL,'2025-07-09 22:29:34',NULL,'pending','2025-06-28 22:29:54','2025-06-28 22:29:54'),(19,19,6,'suspend_watering',NULL,'2025-07-08 22:29:34',NULL,'pending','2025-06-28 22:29:54','2025-06-28 22:29:54'),(20,21,7,'end_germination','{\"target_stage\":\"blackout\"}','2025-07-01 22:32:19',NULL,'pending','2025-06-28 22:35:34','2025-06-28 22:35:34'),(21,21,7,'end_blackout','{\"target_stage\":\"light\"}','2025-07-02 22:32:19',NULL,'pending','2025-06-28 22:35:34','2025-06-28 22:35:34'),(22,21,7,'expected_harvest',NULL,'2025-07-09 22:32:19',NULL,'pending','2025-06-28 22:35:34','2025-06-28 22:35:34'),(23,21,7,'suspend_watering',NULL,'2025-07-08 22:32:19',NULL,'pending','2025-06-28 22:35:34','2025-06-28 22:35:34'),(24,33,8,'end_germination','{\"target_stage\":\"light\"}','2025-07-02 22:52:29',NULL,'pending','2025-06-28 22:53:24','2025-06-28 22:53:24'),(25,33,8,'expected_harvest',NULL,'2025-07-09 22:52:29',NULL,'pending','2025-06-28 22:53:24','2025-06-28 22:53:24'),(26,33,8,'suspend_watering',NULL,'2025-07-08 22:52:29',NULL,'pending','2025-06-28 22:53:24','2025-06-28 22:53:24');
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
  `planted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `current_stage` enum('germination','blackout','light','harvested') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'germination',
  `stage_updated_at` timestamp NULL DEFAULT NULL,
  `time_to_next_stage_minutes` int DEFAULT NULL,
  `time_to_next_stage_display` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Stores the human-readable time to next stage status',
  `stage_age_minutes` int DEFAULT NULL,
  `stage_age_display` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Stores the human-readable time in stage status',
  `total_age_minutes` int DEFAULT NULL,
  `total_age_display` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Stores the human-readable total age status',
  `expected_harvest_at` timestamp NULL DEFAULT NULL COMMENT 'Expected harvest date based on recipe and current stage',
  `tray_count` int unsigned NOT NULL DEFAULT '1' COMMENT 'Number of trays in this batch',
  `tray_numbers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Comma-separated list of tray numbers',
  `planting_at` timestamp NULL DEFAULT NULL,
  `germination_at` timestamp NULL DEFAULT NULL,
  `blackout_at` timestamp NULL DEFAULT NULL,
  `light_at` timestamp NULL DEFAULT NULL,
  `harvested_at` timestamp NULL DEFAULT NULL,
  `harvest_weight_grams` decimal(8,2) DEFAULT NULL,
  `watering_suspended_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crops_tray_number_current_stage_index` (`tray_number`,`current_stage`),
  KEY `crops_order_id_foreign` (`order_id`),
  KEY `crops_calc_times_index` (`stage_age_minutes`,`time_to_next_stage_minutes`,`total_age_minutes`),
  KEY `crops_recipe_id_index` (`recipe_id`),
  KEY `crops_planted_at_index` (`planted_at`),
  KEY `crops_stage_planted_index` (`current_stage`,`planted_at`),
  KEY `crops_germination_at_index` (`germination_at`),
  KEY `crops_harvested_at_index` (`harvested_at`),
  KEY `crops_batch_grouping_index` (`recipe_id`,`planted_at`,`current_stage`),
  KEY `crops_crop_plan_id_foreign` (`crop_plan_id`),
  CONSTRAINT `crops_crop_plan_id_foreign` FOREIGN KEY (`crop_plan_id`) REFERENCES `crop_plans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crops_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `crops_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crops`
--

LOCK TABLES `crops` WRITE;
/*!40000 ALTER TABLE `crops` DISABLE KEYS */;
INSERT INTO `crops` VALUES (1,1,NULL,NULL,'11','2025-06-25 00:00:59','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:00:59',1,'11','2025-06-25 00:00:59','2025-06-25 00:00:59',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:01:12','2025-06-25 00:01:12'),(2,1,NULL,NULL,'12','2025-06-25 00:00:59','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:00:59',1,'12','2025-06-25 00:00:59','2025-06-25 00:00:59',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:01:12','2025-06-25 00:01:12'),(3,2,NULL,NULL,'13','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'13','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(4,2,NULL,NULL,'14','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'14','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(5,2,NULL,NULL,'15','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'15','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(6,2,NULL,NULL,'16','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'16','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:04','2025-06-25 00:57:04'),(7,2,NULL,NULL,'17','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'17','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(8,2,NULL,NULL,'18','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'18','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(9,2,NULL,NULL,'19','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'19','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(10,2,NULL,NULL,'20','2025-06-25 00:56:42','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-04 00:56:42',1,'20','2025-06-25 00:56:42','2025-06-25 00:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 00:57:05','2025-06-25 00:57:05'),(11,3,NULL,NULL,'21','2025-06-25 04:54:53','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-16 04:54:53',1,'21','2025-06-25 04:54:53','2025-06-25 04:54:53',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 04:55:14','2025-06-25 04:55:14'),(12,3,NULL,NULL,'22','2025-06-25 04:54:53','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-16 04:54:53',1,'22','2025-06-25 04:54:53','2025-06-25 04:54:53',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-25 04:55:14','2025-06-25 04:55:14'),(13,4,NULL,NULL,'BL1','2025-06-26 21:48:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-10 21:48:04',1,'BL1','2025-06-26 21:48:04','2025-06-26 21:48:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(14,4,NULL,NULL,'BL2','2025-06-26 21:48:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-10 21:48:04',1,'BL2','2025-06-26 21:48:04','2025-06-26 21:48:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(15,4,NULL,NULL,'BL3','2025-06-26 21:48:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-10 21:48:04',1,'BL3','2025-06-26 21:48:04','2025-06-26 21:48:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-26 21:48:26','2025-06-26 21:48:26'),(16,5,NULL,NULL,'BL9','2025-06-27 16:26:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-08 16:26:04',1,'BL9','2025-06-27 16:26:04','2025-06-27 16:26:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(17,5,NULL,NULL,'BL17','2025-06-27 16:26:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-08 16:26:04',1,'BL17','2025-06-27 16:26:04','2025-06-27 16:26:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(18,5,NULL,NULL,'BL12','2025-06-27 16:26:04','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-08 16:26:04',1,'BL12','2025-06-27 16:26:04','2025-06-27 16:26:04',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-27 16:26:31','2025-06-27 16:26:31'),(19,6,NULL,NULL,'BL8','2025-06-28 22:29:34','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-09 22:29:34',1,'BL8','2025-06-28 22:29:34','2025-06-28 22:29:34',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:29:54','2025-06-28 22:29:54'),(20,6,NULL,NULL,'BL5','2025-06-28 22:29:34','germination',NULL,0,'Unknown',0,'0m',0,'0m','2025-07-09 22:29:34',1,'BL5','2025-06-28 22:29:34','2025-06-28 22:29:34',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:29:54','2025-06-28 22:29:54'),(21,7,NULL,NULL,'27','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'27','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(22,7,NULL,NULL,'28','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'28','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(23,7,NULL,NULL,'29','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'29','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(24,7,NULL,NULL,'26','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'26','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(25,7,NULL,NULL,'30','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'30','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(26,7,NULL,NULL,'31','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'31','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(27,7,NULL,NULL,'32','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'32','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(28,7,NULL,NULL,'33','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'33','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(29,7,NULL,NULL,'23','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'23','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(30,7,NULL,NULL,'25','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'25','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(31,7,NULL,NULL,'bl17','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'bl17','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(32,7,NULL,NULL,'24','2025-06-28 22:32:19','germination',NULL,0,'Unknown',0,'0m',3,'3m','2025-07-09 22:32:19',1,'24','2025-06-28 22:32:19','2025-06-28 22:32:19',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:35:34','2025-06-28 22:35:34'),(33,8,NULL,NULL,'BL','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'BL','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(34,8,NULL,NULL,'BL16','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'BL16','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(35,8,NULL,NULL,'1','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'1','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(36,8,NULL,NULL,'9','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'9','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(37,8,NULL,NULL,'BL11','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'BL11','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(38,8,NULL,NULL,'BL13','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'BL13','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(39,8,NULL,NULL,'BL15','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'BL15','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24'),(40,8,NULL,NULL,'4','2025-06-28 22:52:29','germination',NULL,0,'Unknown',0,'0m',1,'0m','2025-07-09 22:52:29',1,'4','2025-06-28 22:52:29','2025-06-28 22:52:29',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-28 22:53:24','2025-06-28 22:53:24');
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
  `average_weight_per_tray` decimal(10,2) GENERATED ALWAYS AS ((`total_weight_grams` / `tray_count`)) VIRTUAL,
  `harvest_date` date NOT NULL,
  `week_start_date` date GENERATED ALWAYS AS ((`harvest_date` - interval (dayofweek(`harvest_date`) - 4) day)) VIRTUAL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `harvests_user_id_foreign` (`user_id`),
  KEY `harvests_master_cultivar_id_harvest_date_index` (`master_cultivar_id`,`harvest_date`),
  KEY `harvests_week_start_date_index` (`week_start_date`),
  CONSTRAINT `harvests_master_cultivar_id_foreign` FOREIGN KEY (`master_cultivar_id`) REFERENCES `master_cultivars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `harvests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `harvests`
--

LOCK TABLES `harvests` WRITE;
/*!40000 ALTER TABLE `harvests` DISABLE KEYS */;
INSERT INTO `harvests` (`id`, `master_cultivar_id`, `user_id`, `total_weight_grams`, `tray_count`, `harvest_date`, `notes`, `created_at`, `updated_at`) VALUES (1,26,2,433.00,1,'2025-06-25',NULL,'2025-06-25 20:24:25','2025-06-25 20:24:25'),(2,26,2,422.00,1,'2025-06-25',NULL,'2025-06-25 20:28:03','2025-06-25 20:28:03'),(3,26,2,430.00,1,'2025-06-25',NULL,'2025-06-25 20:29:58','2025-06-25 20:29:58'),(4,26,2,420.00,1,'2025-06-25',NULL,'2025-06-25 20:33:18','2025-06-25 20:33:18'),(5,10,2,379.00,2,'2025-06-25',NULL,'2025-06-25 20:40:49','2025-06-25 20:40:49'),(6,10,2,428.00,2,'2025-06-25',NULL,'2025-06-25 20:43:39','2025-06-25 20:43:39'),(7,10,2,401.00,2,'2025-06-25',NULL,'2025-06-25 20:47:38','2025-06-25 20:47:38'),(8,20,2,188.00,1,'2025-06-25',NULL,'2025-06-25 20:55:12','2025-06-25 20:55:12'),(9,20,2,163.00,1,'2025-06-25','Small deadspot on 1 end affected growth','2025-06-25 20:58:55','2025-06-25 20:58:55'),(10,5,2,281.00,3,'2025-06-25','Lower yield due to first week out of germ chamber. Left in germination too long. And heaters malfunctioned cranking heat mid week','2025-06-25 21:09:02','2025-06-25 21:09:02'),(11,23,2,608.00,4,'2025-06-25',NULL,'2025-06-25 21:15:32','2025-06-25 21:15:32'),(12,1,2,500.00,1,'2025-06-25',NULL,'2025-06-25 21:31:08','2025-06-25 21:31:08'),(13,1,2,519.00,1,'2025-06-25',NULL,'2025-06-25 21:53:41','2025-06-25 21:53:41'),(14,1,2,520.00,1,'2025-06-25',NULL,'2025-06-25 22:02:45','2025-06-25 22:02:45'),(15,1,2,500.00,1,'2025-06-25',NULL,'2025-06-25 22:25:28','2025-06-25 22:25:28'),(16,1,2,586.00,1,'2025-06-25',NULL,'2025-06-25 22:36:16','2025-06-25 22:36:16'),(17,1,2,595.00,1,'2025-06-25',NULL,'2025-06-25 22:45:12','2025-06-25 22:45:12'),(18,1,2,574.00,1,'2025-06-25',NULL,'2025-06-25 22:56:06','2025-06-25 22:56:06'),(19,1,2,522.00,1,'2025-06-25',NULL,'2025-06-25 22:57:53','2025-06-25 22:57:53'),(20,24,2,375.00,1,'2025-06-25',NULL,'2025-06-25 23:02:41','2025-06-25 23:02:41'),(21,24,2,375.00,1,'2025-06-25',NULL,'2025-06-25 23:05:40','2025-06-25 23:05:40'),(22,1,2,545.00,1,'2025-06-25',NULL,'2025-06-25 23:12:17','2025-06-25 23:12:17'),(23,25,2,500.00,2,'2025-06-25',NULL,'2025-06-25 23:17:12','2025-06-25 23:17:12'),(24,24,2,381.00,1,'2025-06-25',NULL,'2025-06-25 23:24:48','2025-06-25 23:24:48'),(25,24,2,469.00,1,'2025-06-25',NULL,'2025-06-25 23:26:36','2025-06-25 23:26:36'),(26,24,2,331.00,1,'2025-06-25',NULL,'2025-06-25 23:28:51','2025-06-25 23:28:51'),(27,5,2,84.00,1,'2025-06-25',NULL,'2025-06-25 23:36:38','2025-06-25 23:36:38'),(28,24,2,568.00,2,'2025-06-25',NULL,'2025-06-26 00:45:28','2025-06-26 00:45:28');
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
  `product_inventory_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `type` enum('production','purchase','sale','return','adjustment','damage','expiration','transfer','reservation','release') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inventory_transactions_product_inventory_id_foreign` (`product_inventory_id`),
  KEY `inventory_transactions_user_id_foreign` (`user_id`),
  KEY `inventory_transactions_product_id_type_index` (`product_id`,`type`),
  KEY `inventory_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `inventory_transactions_created_at_index` (`created_at`),
  CONSTRAINT `inventory_transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_product_inventory_id_foreign` FOREIGN KEY (`product_inventory_id`) REFERENCES `product_inventories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inventory_transactions_chk_1` CHECK (json_valid(`metadata`))
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
  `user_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `is_consolidated` tinyint(1) NOT NULL DEFAULT '0',
  `consolidated_order_count` int DEFAULT NULL,
  `invoice_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_user_id_foreign` (`user_id`),
  KEY `invoices_order_id_foreign` (`order_id`),
  CONSTRAINT `invoices_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `invoices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  `cultivar_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aliases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` text COLLATE utf8mb4_unicode_ci,
  `growing_notes` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_cultivars_master_seed_catalog_id_cultivar_name_unique` (`master_seed_catalog_id`,`cultivar_name`),
  KEY `master_cultivars_cultivar_name_index` (`cultivar_name`),
  KEY `master_cultivars_is_active_index` (`is_active`),
  CONSTRAINT `master_cultivars_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `master_cultivars_chk_1` CHECK (json_valid(`aliases`))
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_cultivars`
--

LOCK TABLES `master_cultivars` WRITE;
/*!40000 ALTER TABLE `master_cultivars` DISABLE KEYS */;
INSERT INTO `master_cultivars` VALUES (1,1,'Black Oilseed',NULL,NULL,NULL,1,'2025-06-24 23:57:48','2025-06-24 23:57:48'),(2,2,'Genovese',NULL,NULL,NULL,1,'2025-06-25 04:14:14','2025-06-25 04:14:14'),(3,2,'Thai',NULL,NULL,NULL,1,'2025-06-25 04:14:14','2025-06-25 04:14:14'),(4,3,'Red',NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(5,4,'Arugula',NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(6,5,'Bull’s Blood',NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(7,5,'Pink',NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(8,5,'Ruby',NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(9,6,'Borage',NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(10,7,'Broccoli',NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(11,8,'Red',NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(12,9,'Red',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(13,10,'Coriander',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(14,11,'Curly (Garden )',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(15,12,'Dill',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(16,13,'Beans',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(17,14,'Fennel',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(18,15,'Fenugreek',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(19,16,'Green',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(20,16,'Red',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(21,17,'Purple',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(22,18,'Komatsuna',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(23,19,'Oriental',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(24,20,'Speckled',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(25,21,'Red',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(26,21,'Ruby Stem',NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55');
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
  `cultivars` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `category` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aliases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `growing_notes` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_seed_catalog_common_name_unique` (`common_name`),
  KEY `master_seed_catalog_category_index` (`category`),
  KEY `master_seed_catalog_is_active_index` (`is_active`),
  CONSTRAINT `master_seed_catalog_chk_1` CHECK (json_valid(`cultivars`)),
  CONSTRAINT `master_seed_catalog_chk_2` CHECK (json_valid(`aliases`))
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_seed_catalog`
--

LOCK TABLES `master_seed_catalog` WRITE;
/*!40000 ALTER TABLE `master_seed_catalog` DISABLE KEYS */;
INSERT INTO `master_seed_catalog` VALUES (1,'Sunflower','[\"Black Oilseed\"]',NULL,'[\"Black Oil\", \"sunflower\"]',NULL,NULL,1,'2025-06-24 23:57:48','2025-06-24 23:58:18'),(2,'Basil','[\"Genovese\", \"Thai\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 04:14:14','2025-06-25 04:14:14'),(3,'Amaranth','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(4,'Arugula','[\"Arugula\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(5,'Beet','[\"Bull\\u2019s Blood\",\"Pink\",\"Ruby\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:14','2025-06-25 20:22:14'),(6,'Borage','[\"Borage\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(7,'Broccoli','[\"Broccoli\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(8,'Cabbage','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:22:37','2025-06-25 20:22:37'),(9,'Clover','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(10,'Coriander','[\"Coriander\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(11,'Cress','[\"Curly (Garden )\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(12,'Dill','[\"Dill\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(13,'Fava','[\"Beans\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(14,'Fennel','[\"Fennel\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(15,'Fenugreek','[\"Fenugreek\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(16,'Kale','[\"Green\",\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(17,'Kohlrabi','[\"Purple\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(18,'Komatsuna','[\"Komatsuna\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(19,'Mustard','[\"Oriental\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(20,'Peas,','[\"Speckled\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55'),(21,'Radish','[\"Red\",\"Ruby Stem\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 20:23:55','2025-06-25 20:23:55');
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
) ENGINE=InnoDB AUTO_INCREMENT=169 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_08_15_000000_create_crop_alerts_table',1),(5,'2025_03_15_055950_create_permission_tables',1),(6,'2025_03_15_060211_create_suppliers_table',1),(7,'2025_03_15_060212_create_seed_varieties_table',1),(8,'2025_03_15_060214_create_recipes_table',1),(9,'2025_03_15_060215_create_recipe_stages_table',1),(10,'2025_03_15_060305_create_recipe_watering_schedule_table',1),(11,'2025_03_15_060319_create_recipe_mixes_table',1),(12,'2025_03_15_060335_create_crops_table',1),(13,'2025_03_15_060352_create_inventory_table',1),(14,'2025_03_15_060353_create_consumables_table',1),(15,'2025_03_15_060353_create_orders_table',1),(16,'2025_03_15_060355_create_invoices_table',1),(17,'2025_03_15_060355_create_payments_table',1),(18,'2025_03_15_060355_create_settings_table',1),(19,'2025_03_15_060355_drop_inventory_table',1),(20,'2025_03_15_060527_fix_migration_order',1),(21,'2025_03_15_063501_create_activity_log_table',1),(22,'2025_03_15_070829_create_personal_access_tokens_table',1),(23,'2025_03_21_002206_create_packaging_types_table',1),(24,'2025_03_21_002211_create_order_packagings_table',1),(25,'2025_03_21_031151_migrate_legacy_images_to_item_photos',1),(26,'2025_03_21_032617_remove_code_field_from_items_table',1),(27,'2025_03_23_192440_add_light_days_to_recipes_table',1),(28,'2025_03_25_235525_create_task_schedules_table',1),(29,'2025_03_25_235534_create_notification_settings_table',1),(30,'2025_03_26_010126_update_packaging_types_add_volume_field',1),(31,'2025_03_26_010933_remove_capacity_grams_from_packaging_types',1),(32,'2025_03_26_045009_add_soil_consumable_id_to_recipes_table',1),(33,'2025_04_09_020444_add_stage_timestamps_to_crops_table',1),(34,'2025_04_09_045210_create_tasks_table',1),(35,'2025_04_17_185454_add_packaging_type_foreign_key_to_consumables',1),(36,'2025_04_17_234148_update_consumable_unit_types',1),(37,'2025_04_17_234403_update_lot_no_to_uppercase',1),(38,'2025_04_18_003016_add_units_quantity_to_consumables_table',1),(39,'2025_04_18_003759_update_consumable_unit_types_to_simpler_values',1),(40,'2025_04_18_010330_add_consumed_quantity_to_consumables_table',1),(41,'2025_04_18_014631_update_consumables_decimal_precision',1),(42,'2025_04_18_025334_update_clamshell_packaging_types_volume',1),(43,'2025_04_18_034705_add_watering_method_to_recipe_watering_schedule',1),(44,'2025_04_18_042544_rename_seed_soak_days_to_hours_in_recipes_table',1),(45,'2025_04_18_054155_fix_recipe_seed_variety_relationship',1),(46,'2025_04_18_100000_change_seed_soak_days_to_decimal',1),(47,'2025_04_19_000000_drop_recipe_mixes_table',1),(48,'2025_04_19_031951_remove_notes_from_recipes',1),(49,'2025_04_19_035640_add_growth_phase_notes_columns_to_recipes_table',1),(50,'2025_04_19_041217_add_seed_variety_id_to_consumables',1),(51,'2025_04_19_043838_update_consumed_quantity_default_on_consumables',1),(52,'2025_04_19_044201_update_total_quantity_default_on_consumables',1),(53,'2025_04_19_045350_update_consumables_table_structure',1),(54,'2025_04_19_045809_add_missing_columns_to_seed_varieties',1),(55,'2025_04_19_050518_update_crops_recipe_foreign_key',1),(56,'2025_04_19_052750_add_crop_type_to_seed_varieties_table',1),(57,'2025_05_01_133249_add_time_to_next_stage_minutes_to_crops_table',1),(58,'2025_05_01_143431_add_stage_age_minutes_to_crops_table',1),(59,'2025_05_01_144928_add_total_age_minutes_to_crops_table',1),(60,'2025_05_02_165743_update_time_to_next_stage_minutes_column_type',1),(61,'2025_05_02_165851_update_stage_age_minutes_column_type',1),(62,'2025_05_02_165855_update_total_age_minutes_column_type',1),(63,'2025_05_02_205557_create_crop_batches_view',1),(64,'2025_05_03_000000_add_calculated_columns_to_crops_table',1),(65,'2025_05_03_222337_add_suspend_watering_to_recipes_table',1),(66,'2025_05_03_222805_remove_stage_notes_from_recipes_table',1),(67,'2025_05_03_222911_rename_suspend_watering_hours_column_in_recipes_table',1),(68,'2025_05_03_224138_create_crop_tasks_table',1),(69,'2025_05_03_224935_create_notifications_table',1),(70,'2025_05_07_094527_create_products_table',1),(71,'2025_05_07_094528_create_price_variations_for_existing_products',1),(72,'2025_05_07_094529_create_product_photos_table',1),(73,'2025_05_07_094530_remove_caption_from_product_photos',1),(74,'2025_05_09_000000_update_stage_age_minutes_column_type',1),(75,'2025_05_20_201327_add_indexes_for_optimization',1),(76,'2025_05_26_162845_create_seed_cultivars_table',1),(77,'2025_05_26_162849_create_seed_entries_table',1),(78,'2025_05_26_162852_create_seed_variations_table',1),(79,'2025_05_26_162855_create_seed_price_history_table',1),(80,'2025_05_26_162859_create_seed_scrape_uploads_table',1),(81,'2025_05_26_162902_add_consumable_id_to_seed_variations',1),(82,'2025_06_03_100000_placeholder_notes_column_decision',1),(83,'2025_06_03_141432_add_missing_foreign_key_constraints',1),(84,'2025_06_03_141453_add_critical_performance_indexes',1),(85,'2025_06_03_213058_add_recurring_order_support_to_orders_table',1),(86,'2025_06_03_220125_create_product_mixes_table',1),(87,'2025_06_03_220129_create_product_mix_components_table',1),(88,'2025_06_03_223329_add_packaging_type_to_price_variations_table',1),(89,'2025_06_03_223734_add_fill_weight_to_price_variations_table',1),(90,'2025_06_03_224520_add_active_column_to_products_table',1),(91,'2025_06_03_224602_make_sku_nullable_in_products_table',1),(92,'2025_06_04_072532_add_customer_type_to_users_table',1),(93,'2025_06_04_073839_update_orders_status_enum_values',1),(94,'2025_06_04_075015_update_invoice_foreign_key_to_cascade_on_delete',1),(95,'2025_06_04_075517_add_price_variation_id_to_order_products_table',1),(96,'2025_06_04_083155_add_preferences_to_users_table',1),(97,'2025_06_04_090627_remove_preferences_from_users_table',1),(98,'2025_06_04_100000_migrate_recipes_to_seed_cultivar',1),(99,'2025_06_04_100001_add_seed_variety_fields_to_seed_cultivars',1),(100,'2025_06_04_100002_remove_seed_variety_id_from_consumables',1),(101,'2025_06_04_100004_update_product_mix_components_to_seed_cultivar',1),(102,'2025_06_04_100005_drop_seed_varieties_table',1),(103,'2025_06_05_075524_simplify_seed_structure_add_names_to_entries',1),(104,'2025_06_05_075648_fix_common_names_in_seed_entries',1),(105,'2025_06_05_085532_make_seed_cultivar_id_nullable_in_seed_entries',1),(106,'2025_06_05_193018_add_cataloged_at_to_seed_entries_table',1),(107,'2025_06_05_193715_create_supplier_source_mappings_table',1),(108,'2025_06_08_092642_remove_cataloged_at_from_seed_entries_table',1),(109,'2025_06_09_062308_add_seed_entry_id_to_consumables_table',1),(110,'2025_06_09_063844_add_is_active_to_seed_entries_table',1),(111,'2025_06_09_064442_fix_recipes_seed_entry_foreign_key',1),(112,'2025_06_09_065222_rename_seed_cultivar_id_to_seed_entry_id_in_recipes',1),(113,'2025_06_09_065622_rename_seed_cultivar_id_to_seed_entry_id_in_product_mix_components',1),(114,'2025_06_09_111847_add_failed_entries_to_seed_scrape_uploads_table',1),(115,'2025_06_09_130054_make_current_price_nullable_in_seed_variations_table',1),(116,'2025_06_09_155051_make_cost_per_unit_nullable_in_consumables_table',1),(117,'2025_06_09_174941_make_harvest_date_nullable_in_orders_table',1),(118,'2025_06_09_180239_add_order_classification_to_orders_table',1),(119,'2025_06_09_180649_add_consolidated_invoice_support_to_invoices_table',1),(120,'2025_06_09_195832_make_order_id_nullable_in_invoices_table',1),(121,'2025_06_09_222238_add_billing_period_to_orders_table',1),(122,'2025_06_09_233139_create_crop_plans_table',1),(123,'2025_06_09_233223_add_crop_plan_id_to_crops_table',1),(124,'2025_06_11_133418_create_product_inventory_system',1),(125,'2025_06_11_151000_add_seed_entry_to_products_if_not_exists',1),(126,'2025_06_11_210426_create_master_seed_catalog_table',1),(127,'2025_06_11_210429_create_master_cultivars_table',1),(128,'2025_06_11_221240_change_scientific_name_to_json_in_master_seed_catalog',1),(129,'2025_06_11_225657_add_master_seed_catalog_id_to_consumables_table',1),(130,'2025_06_11_230351_add_cultivars_column_to_master_seed_catalog_table',1),(131,'2025_06_11_230435_rename_scientific_name_to_cultivars_in_master_seed_catalog',1),(132,'2025_06_11_231627_add_master_seed_catalog_id_to_products_table',1),(133,'2025_06_11_231700_replace_seed_entry_id_with_master_seed_catalog_id_in_products',1),(134,'2025_06_12_085856_add_template_id_to_price_variations_table',1),(135,'2025_06_12_184326_add_soft_deletes_to_consumables_table',1),(136,'2025_06_12_200016_add_master_cultivar_id_to_consumables_table',1),(137,'2025_06_12_201000_populate_master_cultivar_id_in_consumables',1),(138,'2025_06_12_204424_make_seed_entry_id_nullable_in_product_mix_components',1),(139,'2025_06_12_204633_remove_seed_entry_id_from_product_mix_components',1),(140,'2025_06_12_205054_fix_product_mix_components_unique_constraints',1),(141,'2025_06_12_add_pricing_unit_to_price_variations',1),(142,'2025_06_13_161917_add_wholesale_discount_percentage_to_products_table',1),(143,'2025_06_13_163543_update_existing_products_wholesale_discount_default',1),(144,'2025_06_13_180604_add_unique_index_to_product_name',1),(145,'2025_06_13_214716_add_cultivar_to_consumables_table',1),(146,'2025_06_13_214757_populate_cultivar_in_consumables_table',1),(147,'2025_06_13_215428_update_percentage_precision_in_product_mix_components',1),(148,'2025_06_13_add_cascade_delete_to_product_relations',1),(149,'2025_06_13_remove_batch_number_from_product_inventories',1),(150,'2025_06_18_112138_create_harvests_table',1),(151,'2025_06_18_122409_add_buffer_percentage_to_recipes_table',1),(152,'2025_06_18_142749_add_packed_status_to_orders_enum',1),(153,'2025_06_18_180519_separate_order_statuses_into_distinct_columns',1),(154,'2025_06_18_182436_create_data_exports_table',1),(155,'2025_06_19_073000_drop_data_exports_table',1),(156,'2025_06_19_111313_insert_harvest_data',1),(157,'2025_06_19_141353_add_wholesale_discount_percentage_to_users_table',1),(158,'2025_06_19_180500_make_customer_type_nullable_in_orders_table',1),(159,'2025_06_19_181751_add_b2b_to_order_type_enum',1),(160,'2025_08_15_000001_add_days_to_maturity_to_recipes_table',1),(161,'2025_06_24_091546_create_time_cards_table',2),(162,'2025_06_24_092916_add_review_fields_to_time_cards_table',3),(163,'2025_06_24_104741_add_other_type_to_suppliers_table',4),(164,'2025_06_24_110323_add_packaging_type_to_suppliers_table',5),(165,'2025_06_24_145952_create_task_types_table',6),(166,'2025_06_24_150348_create_time_card_tasks_table',7),(167,'2025_06_28_192811_fix_crops_column_names',8),(168,'2025_06_28_192944_add_stage_updated_at_to_crops_table',9);
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
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',2);
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
  `resource_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `email_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `email_subject_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_body_template` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_settings_resource_type_event_type_unique` (`resource_type`,`event_type`),
  CONSTRAINT `notification_settings_chk_1` CHECK (json_valid(`recipients`))
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
  `quantity` int NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_packagings_order_id_packaging_type_id_unique` (`order_id`,`packaging_type_id`),
  KEY `order_packagings_packaging_type_id_foreign` (`packaging_type_id`),
  KEY `order_packagings_order_packaging_index` (`order_id`,`packaging_type_id`),
  CONSTRAINT `order_packagings_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_packagings_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`)
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
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price_variation_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_products_order_id_foreign` (`order_id`),
  KEY `order_products_product_id_foreign` (`product_id`),
  KEY `order_products_price_variation_id_foreign` (`price_variation_id`),
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
  `harvest_date` date DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('draft','pending','confirmed','processing','completed','cancelled','template') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `crop_status` enum('not_started','planted','growing','ready_to_harvest','harvested','na') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `fulfillment_status` enum('pending','processing','packing','packed','ready_for_delivery','out_for_delivery','delivered','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `customer_type` enum('retail','wholesale') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_type` enum('farmers_market','b2b','b2b_recurring','website_immediate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website_immediate',
  `billing_frequency` enum('immediate','weekly','monthly','quarterly') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'immediate',
  `requires_invoice` tinyint(1) NOT NULL DEFAULT '1',
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `consolidated_invoice_id` bigint unsigned DEFAULT NULL,
  `billing_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_recurring` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_recurring_order_id` bigint unsigned DEFAULT NULL,
  `recurring_frequency` enum('weekly','biweekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recurring_start_date` date DEFAULT NULL,
  `recurring_end_date` date DEFAULT NULL,
  `billing_period` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Billing period for consolidated invoicing (e.g., 2024-01 for monthly, 2024-W15 for weekly)',
  `is_recurring_active` tinyint(1) NOT NULL DEFAULT '1',
  `recurring_days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `recurring_interval` int DEFAULT NULL,
  `last_generated_at` timestamp NULL DEFAULT NULL,
  `next_generation_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_index` (`user_id`),
  KEY `orders_harvest_date_index` (`harvest_date`),
  KEY `orders_delivery_date_index` (`delivery_date`),
  KEY `orders_status_harvest_index` (`harvest_date`),
  KEY `orders_user_created_index` (`user_id`,`created_at`),
  KEY `orders_parent_recurring_order_id_foreign` (`parent_recurring_order_id`),
  KEY `orders_consolidated_invoice_id_foreign` (`consolidated_invoice_id`),
  KEY `orders_billing_period_type_index` (`billing_period`,`order_type`),
  CONSTRAINT `orders_consolidated_invoice_id_foreign` FOREIGN KEY (`consolidated_invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_parent_recurring_order_id_foreign` FOREIGN KEY (`parent_recurring_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `orders_chk_1` CHECK (json_valid(`billing_preferences`)),
  CONSTRAINT `orders_chk_2` CHECK (json_valid(`recurring_days_of_week`))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `capacity_volume` decimal(8,2) NOT NULL DEFAULT '0.00',
  `volume_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'oz',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `cost_per_unit` decimal(8,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packaging_types`
--

LOCK TABLES `packaging_types` WRITE;
/*!40000 ALTER TABLE `packaging_types` DISABLE KEYS */;
INSERT INTO `packaging_types` VALUES (1,'16oz Clamshell',16.00,'oz',NULL,1,0.00,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(2,'24oz Clamshell',24.00,'oz',NULL,1,0.00,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(3,'32oz Clamshell',32.00,'oz',NULL,1,0.00,'2025-06-25 01:01:03','2025-06-25 01:01:03'),(4,'48oz Clamshell',48.00,'oz',NULL,1,0.00,'2025-06-25 01:01:04','2025-06-25 01:01:04'),(5,'64oz Clamshell',64.00,'oz',NULL,1,0.00,'2025-06-25 01:01:04','2025-06-25 01:01:04');
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
  KEY `payments_order_id_foreign` (`order_id`),
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'manage products','web','2025-06-23 09:33:31','2025-06-23 09:33:31'),(2,'view products','web','2025-06-23 09:33:31','2025-06-23 09:33:31'),(3,'edit products','web','2025-06-23 09:33:31','2025-06-23 09:33:31'),(4,'delete products','web','2025-06-23 09:33:31','2025-06-23 09:33:31'),(5,'access filament','web','2025-06-23 09:33:31','2025-06-23 09:33:31');
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
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `pricing_unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'per_item',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `is_global` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'When true, this variation can be used with any product',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `product_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `packaging_type_id` bigint unsigned DEFAULT NULL,
  `fill_weight_grams` decimal(8,2) DEFAULT NULL COMMENT 'Actual product weight in grams that goes into the packaging',
  PRIMARY KEY (`id`),
  KEY `price_variations_product_id_is_active_index` (`product_id`,`is_active`),
  KEY `price_variations_product_id_is_default_index` (`product_id`,`is_default`),
  KEY `price_variations_is_global_is_active_index` (`is_global`,`is_active`),
  KEY `price_variations_packaging_type_id_foreign` (`packaging_type_id`),
  KEY `price_variations_product_id_packaging_type_id_index` (`product_id`,`packaging_type_id`),
  KEY `price_variations_template_id_index` (`template_id`),
  CONSTRAINT `price_variations_packaging_type_id_foreign` FOREIGN KEY (`packaging_type_id`) REFERENCES `packaging_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `price_variations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `price_variations_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `price_variations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_variations`
--

LOCK TABLES `price_variations` WRITE;
/*!40000 ALTER TABLE `price_variations` DISABLE KEYS */;
INSERT INTO `price_variations` VALUES (1,'Clamshell (24oz) (Ret)',NULL,5.00,'per_item',0,1,1,NULL,NULL,'2025-06-25 01:40:05','2025-06-25 01:42:41',2,70.00),(2,'Clamshell (32oz) (Ret)',NULL,5.00,'per_item',0,1,1,NULL,NULL,'2025-06-25 01:40:47','2025-06-25 01:42:58',3,70.00),(3,'Bulk',NULL,0.20,'per_g',0,1,1,NULL,NULL,'2025-06-25 01:44:16','2025-06-25 01:44:16',NULL,NULL),(4,'Live Tray',NULL,30.00,'per_item',0,1,1,NULL,NULL,'2025-06-25 01:44:47','2025-06-25 01:44:47',NULL,NULL),(5,'Default',NULL,30.00,'per_item',1,0,1,1,1,'2025-06-25 01:45:58','2025-06-25 01:46:21',NULL,80.00),(7,'Default',NULL,0.20,'per_item',0,0,1,1,3,'2025-06-25 01:45:58','2025-06-25 01:45:58',NULL,NULL),(8,'Default',NULL,30.00,'per_item',0,0,1,1,4,'2025-06-25 01:45:58','2025-06-25 01:45:58',NULL,NULL);
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
  `price_variation_id` bigint unsigned DEFAULT NULL,
  `lot_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `reserved_quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `available_quantity` decimal(10,2) GENERATED ALWAYS AS ((`quantity` - `reserved_quantity`)) VIRTUAL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','depleted','expired','damaged') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_inventories_price_variation_id_foreign` (`price_variation_id`),
  KEY `product_inventories_product_id_status_index` (`product_id`,`status`),
  KEY `product_inventories_product_id_expiration_date_index` (`product_id`,`expiration_date`),
  KEY `product_inventories_lot_number_index` (`lot_number`),
  KEY `product_inventories_expiration_date_index` (`expiration_date`),
  KEY `product_inventories_product_id_available_quantity_index` (`product_id`,`available_quantity`),
  CONSTRAINT `product_inventories_price_variation_id_foreign` FOREIGN KEY (`price_variation_id`) REFERENCES `price_variations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_inventories_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_inventories`
--

LOCK TABLES `product_inventories` WRITE;
/*!40000 ALTER TABLE `product_inventories` DISABLE KEYS */;
INSERT INTO `product_inventories` (`id`, `product_id`, `price_variation_id`, `lot_number`, `quantity`, `reserved_quantity`, `cost_per_unit`, `expiration_date`, `production_date`, `location`, `notes`, `status`, `created_at`, `updated_at`) VALUES (1,1,5,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for 24oz Clamshell variation','active','2025-06-25 01:45:58','2025-06-25 01:45:58'),(2,1,NULL,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for 32oz Clamshell variation','active','2025-06-25 01:45:58','2025-06-25 01:45:58'),(3,1,7,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for Default variation','active','2025-06-25 01:45:58','2025-06-25 01:45:58'),(4,1,8,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for Default variation','active','2025-06-25 01:45:58','2025-06-25 01:45:58');
/*!40000 ALTER TABLE `product_inventories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `product_inventory_summary`
--

DROP TABLE IF EXISTS `product_inventory_summary`;
