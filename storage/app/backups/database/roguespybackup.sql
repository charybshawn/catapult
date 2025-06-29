-- mysqldump-php https://github.com/ifsnop/mysqldump-php
--
-- Host: 127.0.0.1	Database: catapult
-- ------------------------------------------------------
-- Server version 	10.6.22-MariaDB-0ubuntu0.22.04.1
-- Date: Thu, 26 Jun 2025 08:18:24 -0700

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40101 SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_subject_created_index` (`subject_type`,`subject_id`,`created_at`),
  KEY `activity_log_causer_index` (`causer_type`,`causer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `activity_log` VALUES (3,'default','created','App\\Models\\User',2,'created',NULL,NULL,'{\"attributes\": {\"name\": \"Admin User\", \"email\": \"charybshawn@gmail.com\", \"phone\": \"250-515-4007\"}}',NULL,'2025-06-23 02:33:24','2025-06-23 02:33:24'),(4,'default','created','App\\Models\\Supplier',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sprouting Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-23 03:01:59','2025-06-23 03:01:59'),(5,'default','created','App\\Models\\Consumable',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sunflower (Black Oilseed)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Black Oilseed\", \"is_active\": true, \"supplier_id\": 1, \"cost_per_unit\": null, \"initial_stock\": \"20.400\", \"quantity_unit\": \"kg\", \"seed_entry_id\": null, \"total_quantity\": \"20.400\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"0.200\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 1}}',NULL,'2025-06-24 16:59:09','2025-06-24 16:59:09'),(6,'default','created','App\\Models\\Consumable',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"PRO MIX HP\", \"type\": \"soil\", \"unit\": \"bags\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": null, \"cost_per_unit\": null, \"initial_stock\": \"1.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"107.000\", \"last_ordered_at\": null, \"restock_quantity\": \"4.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"107.000\", \"restock_threshold\": \"1.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 17:00:19','2025-06-24 17:00:19'),(7,'default','created','App\\Models\\Recipe',1,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"SUNFLOWER - BLACK OIL - SF4K - 100G\", \"is_active\": true, \"light_days\": 5, \"blackout_days\": 1, \"seed_entry_id\": null, \"days_to_maturity\": 9, \"germination_days\": 3, \"supplier_soil_id\": null, \"expected_yield_grams\": 450, \"seed_density_grams_per_tray\": 100}}',NULL,'2025-06-24 17:00:50','2025-06-24 17:00:50'),(8,'default','created','App\\Models\\Crop',1,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 1, \"planted_at\": \"2025-06-24T17:00:59.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:00:59.000000Z\", \"tray_number\": \"11\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:00:59.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:01:12','2025-06-24 17:01:12'),(9,'default','created','App\\Models\\Crop',2,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 1, \"planted_at\": \"2025-06-24T17:00:59.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:00:59.000000Z\", \"tray_number\": \"12\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:00:59.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:01:12','2025-06-24 17:01:12'),(10,'default','updated','App\\Models\\Consumable',1,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"20.400\", \"consumed_quantity\": \"0.200\"}, \"attributes\": {\"total_quantity\": \"20.200\", \"consumed_quantity\": \"0.400\"}}',NULL,'2025-06-24 17:01:12','2025-06-24 17:01:12'),(11,'default','created','App\\Models\\Supplier',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Germina\", \"type\": \"other\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 17:51:32','2025-06-24 17:51:32'),(12,'default','created','App\\Models\\Consumable',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Sunflower (Black Oilseed)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Black Oilseed\", \"is_active\": true, \"supplier_id\": 2, \"cost_per_unit\": null, \"initial_stock\": \"10.000\", \"quantity_unit\": \"kg\", \"seed_entry_id\": null, \"total_quantity\": \"10.000\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"8.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 1}}',NULL,'2025-06-24 17:55:02','2025-06-24 17:55:02'),(13,'default','created','App\\Models\\Recipe',2,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\", \"is_active\": true, \"light_days\": 6, \"blackout_days\": 0, \"seed_entry_id\": null, \"days_to_maturity\": 9, \"germination_days\": 3, \"supplier_soil_id\": null, \"expected_yield_grams\": null, \"seed_density_grams_per_tray\": 100}}',NULL,'2025-06-24 17:56:15','2025-06-24 17:56:15'),(14,'default','created','App\\Models\\Crop',3,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"13\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(15,'default','created','App\\Models\\Crop',4,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"14\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(16,'default','created','App\\Models\\Crop',5,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"15\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(17,'default','created','App\\Models\\Crop',6,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"16\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(18,'default','created','App\\Models\\Crop',7,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"17\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(19,'default','created','App\\Models\\Crop',8,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"18\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(20,'default','created','App\\Models\\Crop',9,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"19\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(21,'default','created','App\\Models\\Crop',10,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 2, \"planted_at\": \"2025-06-24T17:56:42.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T17:56:42.000000Z\", \"tray_number\": \"20\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T17:56:42.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(22,'default','updated','App\\Models\\Consumable',3,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"10.000\", \"consumed_quantity\": \"8.000\"}, \"attributes\": {\"total_quantity\": \"9.200\", \"consumed_quantity\": \"8.800\"}}',NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(23,'default','created','App\\Models\\User',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"HANOI 36\", \"email\": \"hanoi36sa@gmail.com\", \"phone\": null}}',NULL,'2025-06-24 17:59:04','2025-06-24 17:59:04'),(24,'default','created','App\\Models\\Supplier',3,'created',NULL,NULL,'{\"attributes\": {\"name\": \"Uline\", \"type\": \"consumable\", \"is_active\": true, \"contact_name\": \"Customer Service\", \"contact_email\": \"customerservice@uline.com\", \"contact_phone\": \"1-800-295-5510\"}}',NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(25,'default','created','App\\Models\\PackagingType',1,'created',NULL,NULL,'{\"attributes\": {\"name\": \"16oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 16}}',NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(26,'default','created','App\\Models\\Consumable',4,'created',NULL,NULL,'{\"attributes\": {\"name\": \"16oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.35\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 1, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(27,'default','created','App\\Models\\PackagingType',2,'created',NULL,NULL,'{\"attributes\": {\"name\": \"24oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 24}}',NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(28,'default','created','App\\Models\\Consumable',5,'created',NULL,NULL,'{\"attributes\": {\"name\": \"24oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.45\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 2, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(29,'default','created','App\\Models\\PackagingType',3,'created',NULL,NULL,'{\"attributes\": {\"name\": \"32oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 32}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(30,'default','created','App\\Models\\Consumable',6,'created',NULL,NULL,'{\"attributes\": {\"name\": \"32oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.55\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 3, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(31,'default','created','App\\Models\\PackagingType',4,'created',NULL,NULL,'{\"attributes\": {\"name\": \"48oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 48}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(32,'default','created','App\\Models\\Consumable',7,'created',NULL,NULL,'{\"attributes\": {\"name\": \"48oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.65\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 4, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(33,'default','created','App\\Models\\PackagingType',5,'created',NULL,NULL,'{\"attributes\": {\"name\": \"64oz Clamshell\", \"is_active\": true, \"description\": null, \"volume_unit\": \"oz\", \"cost_per_unit\": 0, \"capacity_volume\": 64}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(34,'default','created','App\\Models\\Consumable',8,'created',NULL,NULL,'{\"attributes\": {\"name\": \"64oz Clamshell\", \"type\": \"packaging\", \"unit\": \"case\", \"cultivar\": null, \"is_active\": true, \"supplier_id\": 3, \"cost_per_unit\": \"0.75\", \"initial_stock\": \"100.000\", \"quantity_unit\": \"l\", \"seed_entry_id\": null, \"total_quantity\": \"0.000\", \"last_ordered_at\": null, \"restock_quantity\": \"50.000\", \"consumed_quantity\": \"0.000\", \"packaging_type_id\": 5, \"quantity_per_unit\": \"0.000\", \"restock_threshold\": \"10.000\", \"master_seed_catalog_id\": null}}',NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(35,'default','created','App\\Models\\Supplier',4,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"William Dam Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:01:09','2025-06-24 18:01:09'),(36,'default','updated','App\\Models\\Supplier',2,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Germina\", \"type\": \"other\"}, \"attributes\": {\"name\": \"Germina Seeds\", \"type\": \"seed\"}}',NULL,'2025-06-24 18:01:28','2025-06-24 18:01:28'),(37,'default','updated','App\\Models\\Supplier',1,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Sprouting Seeds\"}, \"attributes\": {\"name\": \"Mumm\'s Sprouting Seeds\"}}',NULL,'2025-06-24 18:01:46','2025-06-24 18:01:46'),(38,'default','updated','App\\Models\\Supplier',3,'updated','App\\Models\\User',2,'{\"old\": {\"name\": \"Uline\", \"type\": \"consumable\", \"contact_name\": \"Customer Service\", \"contact_email\": \"customerservice@uline.com\", \"contact_phone\": \"1-800-295-5510\"}, \"attributes\": {\"name\": \"Britelands\", \"type\": \"packaging\", \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:06:10','2025-06-24 18:06:10'),(39,'default','created','App\\Models\\Supplier',5,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Ecoline\", \"type\": \"soil\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:06:28','2025-06-24 18:06:28'),(40,'default','created','App\\Models\\Supplier',6,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Buckerfields\", \"type\": \"other\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:06:47','2025-06-24 18:06:47'),(41,'default','updated','App\\Models\\Supplier',6,'updated','App\\Models\\User',2,'{\"old\": {\"type\": \"other\"}, \"attributes\": {\"type\": \"soil\"}}',NULL,'2025-06-24 18:07:03','2025-06-24 18:07:03'),(42,'default','created','App\\Models\\Supplier',7,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Johnny\'s Seeds\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:07:22','2025-06-24 18:07:22'),(43,'default','created','App\\Models\\Supplier',8,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"True Leaf Market\", \"type\": \"seed\", \"is_active\": true, \"contact_name\": null, \"contact_email\": null, \"contact_phone\": null}}',NULL,'2025-06-24 18:07:43','2025-06-24 18:07:43'),(44,'default','created','App\\Models\\Consumable',9,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"Basil (Genovese)\", \"type\": \"seed\", \"unit\": \"g\", \"cultivar\": \"Genovese\", \"is_active\": true, \"supplier_id\": 1, \"cost_per_unit\": null, \"initial_stock\": \"1000.000\", \"quantity_unit\": \"g\", \"seed_entry_id\": null, \"total_quantity\": \"1000.000\", \"last_ordered_at\": null, \"restock_quantity\": \"10.000\", \"consumed_quantity\": \"515.000\", \"packaging_type_id\": null, \"quantity_per_unit\": \"1.000\", \"restock_threshold\": \"5.000\", \"master_seed_catalog_id\": 2}}',NULL,'2025-06-24 21:15:30','2025-06-24 21:15:30'),(45,'default','created','App\\Models\\Recipe',3,'created','App\\Models\\User',2,'{\"attributes\": {\"name\": \"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\", \"is_active\": true, \"light_days\": 17, \"blackout_days\": 0, \"seed_entry_id\": null, \"days_to_maturity\": 21, \"germination_days\": 4, \"supplier_soil_id\": null, \"expected_yield_grams\": 80, \"seed_density_grams_per_tray\": 5}}',NULL,'2025-06-24 21:16:57','2025-06-24 21:16:57'),(46,'default','created','App\\Models\\Crop',11,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 3, \"planted_at\": \"2025-06-24T21:54:53.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T21:54:53.000000Z\", \"tray_number\": \"21\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T21:54:53.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 21:55:14','2025-06-24 21:55:14'),(47,'default','created','App\\Models\\Crop',12,'created','App\\Models\\User',2,'{\"attributes\": {\"light_at\": null, \"order_id\": null, \"recipe_id\": 3, \"planted_at\": \"2025-06-24T21:54:53.000000Z\", \"blackout_at\": null, \"planting_at\": \"2025-06-24T21:54:53.000000Z\", \"tray_number\": \"22\", \"harvested_at\": null, \"current_stage\": \"germination\", \"germination_at\": \"2025-06-24T21:54:53.000000Z\", \"harvest_weight_grams\": null, \"watering_suspended_at\": null}}',NULL,'2025-06-24 21:55:14','2025-06-24 21:55:14'),(48,'default','updated','App\\Models\\Consumable',9,'updated','App\\Models\\User',2,'{\"old\": {\"total_quantity\": \"1000.000\", \"consumed_quantity\": \"515.000\"}, \"attributes\": {\"total_quantity\": \"990.000\", \"consumed_quantity\": \"525.000\"}}',NULL,'2025-06-24 21:55:14','2025-06-24 21:55:14'),(49,'default','created','App\\Models\\PriceVariation',1,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":2,\"name\":\"Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-24 18:40:05','2025-06-24 18:40:05'),(50,'default','created','App\\Models\\PriceVariation',2,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":3,\"name\":\"32oz Clamshell (32oz) (Ret)\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-24 18:40:47','2025-06-24 18:40:47'),(51,'default','updated','App\\Models\\PriceVariation',2,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell (32oz) (Ret)\"},\"old\":{\"name\":\"32oz Clamshell (32oz) (Ret)\"}}',NULL,'2025-06-24 18:42:14','2025-06-24 18:42:14'),(52,'default','updated','App\\Models\\PriceVariation',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell 24oz (Ret)\"},\"old\":{\"name\":\"Clamshell\"}}',NULL,'2025-06-24 18:42:26','2025-06-24 18:42:26'),(53,'default','updated','App\\Models\\PriceVariation',1,'updated','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Clamshell (24oz) (Ret)\"},\"old\":{\"name\":\"Clamshell 24oz (Ret)\"}}',NULL,'2025-06-24 18:42:41','2025-06-24 18:42:41'),(54,'default','updated','App\\Models\\PriceVariation',2,'updated','App\\Models\\User',2,'{\"attributes\":{\"fill_weight_grams\":\"70.00\"},\"old\":{\"fill_weight_grams\":null}}',NULL,'2025-06-24 18:42:58','2025-06-24 18:42:58'),(55,'default','created','App\\Models\\PriceVariation',3,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":null,\"name\":\"Bulk\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"0.20\",\"pricing_unit\":\"per_g\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-24 18:44:16','2025-06-24 18:44:16'),(56,'default','created','App\\Models\\PriceVariation',4,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":null,\"template_id\":null,\"packaging_type_id\":null,\"name\":\"Live Tray\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"30.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":true,\"is_active\":true}}',NULL,'2025-06-24 18:44:47','2025-06-24 18:44:47'),(57,'default','created','App\\Models\\Category',1,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Microgreens\",\"description\":null,\"is_active\":true}}',NULL,'2025-06-24 18:45:23','2025-06-24 18:45:23'),(58,'default','created','App\\Models\\Product',1,'created','App\\Models\\User',2,'{\"attributes\":{\"name\":\"Sunflower\",\"description\":null,\"active\":true,\"is_visible_in_store\":true,\"category_id\":1,\"product_mix_id\":null,\"master_seed_catalog_id\":1,\"image\":null,\"base_price\":null,\"wholesale_price\":null,\"bulk_price\":null,\"special_price\":null,\"wholesale_discount_percentage\":\"30.00\"}}',NULL,'2025-06-24 18:45:58','2025-06-24 18:45:58'),(59,'default','created','App\\Models\\PriceVariation',5,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":1,\"packaging_type_id\":2,\"name\":\"24oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":true,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-24 18:45:58','2025-06-24 18:45:58'),(60,'default','created','App\\Models\\PriceVariation',6,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":2,\"packaging_type_id\":3,\"name\":\"32oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-24 18:45:58','2025-06-24 18:45:58'),(61,'default','created','App\\Models\\PriceVariation',7,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":3,\"packaging_type_id\":null,\"name\":\"Default\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"0.20\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-24 18:45:58','2025-06-24 18:45:58'),(62,'default','created','App\\Models\\PriceVariation',8,'created','App\\Models\\User',2,'{\"attributes\":{\"product_id\":1,\"template_id\":4,\"packaging_type_id\":null,\"name\":\"Default\",\"sku\":null,\"fill_weight_grams\":null,\"price\":\"30.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-24 18:45:58','2025-06-24 18:45:58'),(63,'default','updated','App\\Models\\PriceVariation',5,'updated','App\\Models\\User',2,'{\"attributes\":{\"packaging_type_id\":null,\"name\":\"Default\",\"fill_weight_grams\":\"80.00\",\"price\":\"30.00\"},\"old\":{\"packaging_type_id\":2,\"name\":\"24oz Clamshell\",\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\"}}',NULL,'2025-06-24 18:46:21','2025-06-24 18:46:21'),(64,'default','deleted','App\\Models\\PriceVariation',6,'deleted','App\\Models\\User',2,'{\"old\":{\"product_id\":1,\"template_id\":2,\"packaging_type_id\":3,\"name\":\"32oz Clamshell\",\"sku\":null,\"fill_weight_grams\":\"70.00\",\"price\":\"5.00\",\"pricing_unit\":\"per_item\",\"is_default\":false,\"is_global\":false,\"is_active\":true}}',NULL,'2025-06-24 18:46:26','2025-06-24 18:46:26'),(65,'default','created','App\\Models\\Harvest',1,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"433.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:24:25','2025-06-25 13:24:25'),(66,'default','created','App\\Models\\Harvest',2,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"422.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:28:03','2025-06-25 13:28:03'),(67,'default','created','App\\Models\\Harvest',3,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"430.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:29:58','2025-06-25 13:29:58'),(68,'default','created','App\\Models\\Harvest',4,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":26,\"user_id\":2,\"total_weight_grams\":\"420.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:33:18','2025-06-25 13:33:18'),(69,'default','created','App\\Models\\Harvest',5,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"379.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:40:49','2025-06-25 13:40:49'),(70,'default','created','App\\Models\\Harvest',6,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"428.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:43:39','2025-06-25 13:43:39'),(71,'default','created','App\\Models\\Harvest',7,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":10,\"user_id\":2,\"total_weight_grams\":\"401.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:47:38','2025-06-25 13:47:38'),(72,'default','created','App\\Models\\Harvest',8,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":20,\"user_id\":2,\"total_weight_grams\":\"188.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 13:55:12','2025-06-25 13:55:12'),(73,'default','created','App\\Models\\Harvest',9,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":20,\"user_id\":2,\"total_weight_grams\":\"163.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":\"Small deadspot on 1 end affected growth\"}}',NULL,'2025-06-25 13:58:55','2025-06-25 13:58:55'),(74,'default','created','App\\Models\\Harvest',10,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":5,\"user_id\":2,\"total_weight_grams\":\"281.00\",\"tray_count\":3,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":\"Lower yield due to first week out of germ chamber. Left in germination too long. And heaters malfunctioned cranking heat mid week\"}}',NULL,'2025-06-25 14:09:02','2025-06-25 14:09:02'),(75,'default','created','App\\Models\\Harvest',11,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":23,\"user_id\":2,\"total_weight_grams\":\"608.00\",\"tray_count\":4,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 14:15:32','2025-06-25 14:15:32'),(76,'default','created','App\\Models\\Harvest',12,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 14:31:08','2025-06-25 14:31:08'),(77,'default','created','App\\Models\\Harvest',13,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"519.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 14:53:41','2025-06-25 14:53:41'),(78,'default','created','App\\Models\\Harvest',14,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"520.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:02:45','2025-06-25 15:02:45'),(79,'default','created','App\\Models\\Harvest',15,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:25:28','2025-06-25 15:25:28'),(80,'default','created','App\\Models\\Harvest',16,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"586.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:36:16','2025-06-25 15:36:16'),(81,'default','created','App\\Models\\Harvest',17,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"595.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:45:12','2025-06-25 15:45:12'),(82,'default','created','App\\Models\\Harvest',18,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"574.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:56:06','2025-06-25 15:56:06'),(83,'default','created','App\\Models\\Harvest',19,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"522.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 15:57:53','2025-06-25 15:57:53'),(84,'default','created','App\\Models\\Harvest',20,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"375.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:02:41','2025-06-25 16:02:41'),(85,'default','created','App\\Models\\Harvest',21,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"375.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:05:40','2025-06-25 16:05:40'),(86,'default','created','App\\Models\\Harvest',22,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":1,\"user_id\":2,\"total_weight_grams\":\"545.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:12:17','2025-06-25 16:12:17'),(87,'default','created','App\\Models\\Harvest',23,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":25,\"user_id\":2,\"total_weight_grams\":\"500.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:17:12','2025-06-25 16:17:12'),(88,'default','created','App\\Models\\Harvest',24,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"381.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:24:48','2025-06-25 16:24:48'),(89,'default','created','App\\Models\\Harvest',25,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"469.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:26:36','2025-06-25 16:26:36'),(90,'default','created','App\\Models\\Harvest',26,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"331.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:28:51','2025-06-25 16:28:51'),(91,'default','created','App\\Models\\Harvest',27,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":5,\"user_id\":2,\"total_weight_grams\":\"84.00\",\"tray_count\":1,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 16:36:38','2025-06-25 16:36:38'),(92,'default','created','App\\Models\\Harvest',28,'created','App\\Models\\User',2,'{\"attributes\":{\"master_cultivar_id\":24,\"user_id\":2,\"total_weight_grams\":\"568.00\",\"tray_count\":2,\"harvest_date\":\"2025-06-25T07:00:00.000000Z\",\"notes\":null}}',NULL,'2025-06-25 17:45:28','2025-06-25 17:45:28');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `activity_log` with 90 row(s)
--

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `cache` VALUES ('laravel_cache_da4b9237bacccdf19c0760cab7aec4a8359010b0','i:1;',1750781174),('laravel_cache_da4b9237bacccdf19c0760cab7aec4a8359010b0:timer','i:1750781174;',1750781174),('laravel_cache_livewire-rate-limiter:195e334b3aeece8435cd5183436feeb96619d55d','i:1;',1750882574),('laravel_cache_livewire-rate-limiter:195e334b3aeece8435cd5183436feeb96619d55d:timer','i:1750882574;',1750882574),('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3','i:1;',1750813585),('laravel_cache_livewire-rate-limiter:a17961fa74e9275d529f489537f179c05d50c2f3:timer','i:1750813585;',1750813585),('laravel_cache_livewire-rate-limiter:c6dcfed831a2979378f734c3d82bde3f2bdf15ce','i:1;',1750949997),('laravel_cache_livewire-rate-limiter:c6dcfed831a2979378f734c3d82bde3f2bdf15ce:timer','i:1750949997;',1750949997),('laravel_cache_spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:5:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"manage products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:13:\"view products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:13:\"edit products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:15:\"delete products\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:15:\"access filament\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:7:\"manager\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:4:\"user\";s:1:\"c\";s:3:\"web\";}}}',1750966869);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `cache` with 9 row(s)
--

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `cache_locks` with 0 row(s)
--

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
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
SET autocommit=0;
INSERT INTO `categories` VALUES (1,'Microgreens',NULL,1,'2025-06-24 18:45:23','2025-06-24 18:45:23');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `categories` with 1 row(s)
--

--
-- Table structure for table `consumables`
--

DROP TABLE IF EXISTS `consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `consumables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('packaging','soil','seed','label','other') NOT NULL,
  `supplier_id` bigint(20) unsigned DEFAULT NULL,
  `packaging_type_id` bigint(20) unsigned DEFAULT NULL COMMENT 'For packaging consumables only',
  `seed_entry_id` bigint(20) unsigned DEFAULT NULL,
  `master_seed_catalog_id` bigint(20) unsigned DEFAULT NULL,
  `cultivar` varchar(255) DEFAULT NULL,
  `master_cultivar_id` bigint(20) unsigned DEFAULT NULL,
  `initial_stock` decimal(12,2) NOT NULL COMMENT 'Quantity - Number of units in stock',
  `consumed_quantity` decimal(12,2) NOT NULL COMMENT 'Used quantity - Number of units consumed',
  `unit` varchar(255) NOT NULL DEFAULT 'unit' COMMENT 'Packaging type (e.g., bag, box, bottle)',
  `restock_threshold` decimal(12,3) NOT NULL,
  `restock_quantity` decimal(12,3) NOT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `quantity_per_unit` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Unit size - Capacity or size of each unit (e.g., 107L per bag)',
  `quantity_unit` varchar(20) NOT NULL DEFAULT 'l' COMMENT 'Unit of measurement (e.g., g, kg, l, ml, oz, lb)',
  `total_quantity` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Calculated total: (initial_stock - consumed_quantity) * quantity_per_unit',
  `notes` text DEFAULT NULL,
  `lot_no` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `consumables`
--

LOCK TABLES `consumables` WRITE;
/*!40000 ALTER TABLE `consumables` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `consumables` VALUES (1,'Sunflower (Black Oilseed)','seed',1,NULL,NULL,1,'Black Oilseed',NULL,20.40,0.40,'g',5.000,10.000,NULL,1.00,'kg',20.20,NULL,'SF4K',1,NULL,'2025-06-24 16:59:09','2025-06-24 17:01:12',NULL),(2,'PRO MIX HP','soil',NULL,NULL,NULL,NULL,NULL,NULL,1.00,0.00,'bags',1.000,4.000,NULL,107.00,'l',107.00,NULL,NULL,1,NULL,'2025-06-24 17:00:19','2025-06-24 17:00:19',NULL),(3,'Sunflower (Black Oilseed)','seed',2,NULL,NULL,1,'Black Oilseed',NULL,10.00,8.80,'g',5.000,10.000,NULL,1.00,'kg',9.20,NULL,'SFR16',1,NULL,'2025-06-24 17:55:02','2025-06-24 17:57:05',NULL),(4,'16oz Clamshell','packaging',3,1,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.35,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03',NULL),(5,'24oz Clamshell','packaging',3,2,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.45,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-24 18:01:03','2025-06-24 18:01:03',NULL),(6,'32oz Clamshell','packaging',3,3,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.55,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04',NULL),(7,'48oz Clamshell','packaging',3,4,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.65,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04',NULL),(8,'64oz Clamshell','packaging',3,5,NULL,NULL,NULL,NULL,100.00,0.00,'case',10.000,50.000,0.75,0.00,'l',0.00,NULL,NULL,1,NULL,'2025-06-24 18:01:04','2025-06-24 18:01:04',NULL),(9,'Basil (Genovese)','seed',1,NULL,NULL,2,'Genovese',NULL,1000.00,525.00,'g',5.000,10.000,NULL,1.00,'g',990.00,NULL,'BAS8Y',1,NULL,'2025-06-24 21:15:30','2025-06-24 21:55:14',NULL);
/*!40000 ALTER TABLE `consumables` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `consumables` with 9 row(s)
--

--
-- Table structure for table `crops`
--

DROP TABLE IF EXISTS `crops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `crop_plan_id` bigint(20) unsigned DEFAULT NULL,
  `tray_number` varchar(255) NOT NULL,
  `planted_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `current_stage` enum('germination','blackout','light','harvested') NOT NULL DEFAULT 'germination',
  `time_to_next_stage_minutes` int(11) DEFAULT NULL,
  `time_to_next_stage_display` varchar(50) DEFAULT NULL COMMENT 'Stores the human-readable time to next stage status',
  `stage_age_minutes` int(11) DEFAULT NULL,
  `stage_age_display` varchar(50) DEFAULT NULL COMMENT 'Stores the human-readable time in stage status',
  `total_age_minutes` int(11) DEFAULT NULL,
  `total_age_display` varchar(50) DEFAULT NULL COMMENT 'Stores the human-readable total age status',
  `expected_harvest_at` timestamp NULL DEFAULT NULL COMMENT 'Expected harvest date based on recipe and current stage',
  `tray_count` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'Number of trays in this batch',
  `tray_numbers` varchar(255) DEFAULT NULL COMMENT 'Comma-separated list of tray numbers',
  `planting_at` timestamp NULL DEFAULT NULL,
  `germination_at` timestamp NULL DEFAULT NULL,
  `blackout_at` timestamp NULL DEFAULT NULL,
  `light_at` timestamp NULL DEFAULT NULL,
  `harvested_at` timestamp NULL DEFAULT NULL,
  `harvest_weight_grams` decimal(8,2) DEFAULT NULL,
  `watering_suspended_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crops`
--

LOCK TABLES `crops` WRITE;
/*!40000 ALTER TABLE `crops` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `crops` VALUES (1,1,NULL,NULL,'11','2025-06-24 17:00:59','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:00:59',1,'11','2025-06-24 17:00:59','2025-06-24 17:00:59',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:01:12','2025-06-24 17:01:12'),(2,1,NULL,NULL,'12','2025-06-24 17:00:59','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:00:59',1,'12','2025-06-24 17:00:59','2025-06-24 17:00:59',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:01:12','2025-06-24 17:01:12'),(3,2,NULL,NULL,'13','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'13','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(4,2,NULL,NULL,'14','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'14','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(5,2,NULL,NULL,'15','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'15','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(6,2,NULL,NULL,'16','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'16','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:04','2025-06-24 17:57:04'),(7,2,NULL,NULL,'17','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'17','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(8,2,NULL,NULL,'18','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'18','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(9,2,NULL,NULL,'19','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'19','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(10,2,NULL,NULL,'20','2025-06-24 17:56:42','germination',0,'Unknown',0,'0m',0,'0m','2025-07-03 17:56:42',1,'20','2025-06-24 17:56:42','2025-06-24 17:56:42',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 17:57:05','2025-06-24 17:57:05'),(11,3,NULL,NULL,'21','2025-06-24 21:54:53','germination',0,'Unknown',0,'0m',0,'0m','2025-07-15 21:54:53',1,'21','2025-06-24 21:54:53','2025-06-24 21:54:53',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 21:55:14','2025-06-24 21:55:14'),(12,3,NULL,NULL,'22','2025-06-24 21:54:53','germination',0,'Unknown',0,'0m',0,'0m','2025-07-15 21:54:53',1,'22','2025-06-24 21:54:53','2025-06-24 21:54:53',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-24 21:55:14','2025-06-24 21:55:14');
/*!40000 ALTER TABLE `crops` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `crops` with 12 row(s)
--

--
-- Table structure for table `crop_alerts`
--

DROP TABLE IF EXISTS `crop_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crop_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_type` varchar(255) NOT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_executed_at` timestamp NULL DEFAULT NULL,
  `scheduled_for` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Future table for crop alerts. Currently using task_schedules table.';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_alerts`
--

LOCK TABLES `crop_alerts` WRITE;
/*!40000 ALTER TABLE `crop_alerts` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `crop_alerts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `crop_alerts` with 0 row(s)
--

--
-- Table structure for table `crop_plans`
--

DROP TABLE IF EXISTS `crop_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crop_plans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `trays_needed` int(11) NOT NULL DEFAULT 1,
  `grams_needed` decimal(8,2) NOT NULL,
  `grams_per_tray` decimal(8,2) DEFAULT NULL,
  `plant_by_date` date NOT NULL,
  `expected_harvest_date` date NOT NULL,
  `delivery_date` date NOT NULL,
  `calculation_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`calculation_details`)),
  `order_items_included` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`order_items_included`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
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
  CONSTRAINT `crop_plans_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_plans`
--

LOCK TABLES `crop_plans` WRITE;
/*!40000 ALTER TABLE `crop_plans` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `crop_plans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `crop_plans` with 0 row(s)
--

--
-- Table structure for table `crop_tasks`
--

DROP TABLE IF EXISTS `crop_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crop_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `crop_id` bigint(20) unsigned NOT NULL,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `task_type` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `scheduled_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `triggered_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crop_tasks_recipe_id_foreign` (`recipe_id`),
  KEY `crop_tasks_scheduled_at_index` (`scheduled_at`),
  KEY `crop_tasks_status_index` (`status`),
  KEY `crop_tasks_crop_id_task_type_index` (`crop_id`,`task_type`),
  CONSTRAINT `crop_tasks_crop_id_foreign` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crop_tasks_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crop_tasks`
--

LOCK TABLES `crop_tasks` WRITE;
/*!40000 ALTER TABLE `crop_tasks` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `crop_tasks` VALUES (1,1,1,'end_germination','{\"target_stage\": \"blackout\"}','2025-06-27 17:00:59',NULL,'pending','2025-06-24 17:01:12','2025-06-24 17:01:12'),(2,1,1,'end_blackout','{\"target_stage\": \"light\"}','2025-06-28 17:00:59',NULL,'pending','2025-06-24 17:01:12','2025-06-24 17:01:12'),(3,1,1,'expected_harvest',NULL,'2025-07-03 17:00:59',NULL,'pending','2025-06-24 17:01:12','2025-06-24 17:01:12'),(4,1,1,'suspend_watering',NULL,'2025-07-02 17:00:59',NULL,'pending','2025-06-24 17:01:12','2025-06-24 17:01:12'),(5,3,2,'end_germination','{\"target_stage\": \"light\"}','2025-06-27 17:56:42',NULL,'pending','2025-06-24 17:57:05','2025-06-24 17:57:05'),(6,3,2,'expected_harvest',NULL,'2025-07-03 17:56:42',NULL,'pending','2025-06-24 17:57:05','2025-06-24 17:57:05'),(7,3,2,'suspend_watering',NULL,'2025-07-02 17:56:42',NULL,'pending','2025-06-24 17:57:05','2025-06-24 17:57:05'),(8,11,3,'end_germination','{\"target_stage\": \"light\"}','2025-06-28 21:54:53',NULL,'pending','2025-06-24 21:55:15','2025-06-24 21:55:15'),(9,11,3,'expected_harvest',NULL,'2025-07-15 21:54:53',NULL,'pending','2025-06-24 21:55:15','2025-06-24 21:55:15');
/*!40000 ALTER TABLE `crop_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `crop_tasks` with 9 row(s)
--

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `failed_jobs` with 0 row(s)
--

--
-- Table structure for table `harvests`
--

DROP TABLE IF EXISTS `harvests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `harvests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `master_cultivar_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `total_weight_grams` decimal(10,2) NOT NULL,
  `tray_count` int(11) NOT NULL,
  `average_weight_per_tray` decimal(10,2) GENERATED ALWAYS AS (`total_weight_grams` / `tray_count`) VIRTUAL,
  `harvest_date` date NOT NULL,
  `week_start_date` date GENERATED ALWAYS AS (`harvest_date` - interval dayofweek(`harvest_date`) - 4 day) VIRTUAL,
  `notes` text DEFAULT NULL,
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
SET autocommit=0;
INSERT INTO `harvests` (`id`, `master_cultivar_id`, `user_id`, `total_weight_grams`, `tray_count`, `harvest_date`, `notes`, `created_at`, `updated_at`) VALUES (1,26,2,433.00,1,'2025-06-25',NULL,'2025-06-25 13:24:25','2025-06-25 13:24:25'),(2,26,2,422.00,1,'2025-06-25',NULL,'2025-06-25 13:28:03','2025-06-25 13:28:03'),(3,26,2,430.00,1,'2025-06-25',NULL,'2025-06-25 13:29:58','2025-06-25 13:29:58'),(4,26,2,420.00,1,'2025-06-25',NULL,'2025-06-25 13:33:18','2025-06-25 13:33:18'),(5,10,2,379.00,2,'2025-06-25',NULL,'2025-06-25 13:40:49','2025-06-25 13:40:49'),(6,10,2,428.00,2,'2025-06-25',NULL,'2025-06-25 13:43:39','2025-06-25 13:43:39'),(7,10,2,401.00,2,'2025-06-25',NULL,'2025-06-25 13:47:38','2025-06-25 13:47:38'),(8,20,2,188.00,1,'2025-06-25',NULL,'2025-06-25 13:55:12','2025-06-25 13:55:12'),(9,20,2,163.00,1,'2025-06-25','Small deadspot on 1 end affected growth','2025-06-25 13:58:55','2025-06-25 13:58:55'),(10,5,2,281.00,3,'2025-06-25','Lower yield due to first week out of germ chamber. Left in germination too long. And heaters malfunctioned cranking heat mid week','2025-06-25 14:09:02','2025-06-25 14:09:02'),(11,23,2,608.00,4,'2025-06-25',NULL,'2025-06-25 14:15:32','2025-06-25 14:15:32'),(12,1,2,500.00,1,'2025-06-25',NULL,'2025-06-25 14:31:08','2025-06-25 14:31:08'),(13,1,2,519.00,1,'2025-06-25',NULL,'2025-06-25 14:53:41','2025-06-25 14:53:41'),(14,1,2,520.00,1,'2025-06-25',NULL,'2025-06-25 15:02:45','2025-06-25 15:02:45'),(15,1,2,500.00,1,'2025-06-25',NULL,'2025-06-25 15:25:28','2025-06-25 15:25:28'),(16,1,2,586.00,1,'2025-06-25',NULL,'2025-06-25 15:36:16','2025-06-25 15:36:16'),(17,1,2,595.00,1,'2025-06-25',NULL,'2025-06-25 15:45:12','2025-06-25 15:45:12'),(18,1,2,574.00,1,'2025-06-25',NULL,'2025-06-25 15:56:06','2025-06-25 15:56:06'),(19,1,2,522.00,1,'2025-06-25',NULL,'2025-06-25 15:57:53','2025-06-25 15:57:53'),(20,24,2,375.00,1,'2025-06-25',NULL,'2025-06-25 16:02:41','2025-06-25 16:02:41'),(21,24,2,375.00,1,'2025-06-25',NULL,'2025-06-25 16:05:40','2025-06-25 16:05:40'),(22,1,2,545.00,1,'2025-06-25',NULL,'2025-06-25 16:12:17','2025-06-25 16:12:17'),(23,25,2,500.00,2,'2025-06-25',NULL,'2025-06-25 16:17:12','2025-06-25 16:17:12'),(24,24,2,381.00,1,'2025-06-25',NULL,'2025-06-25 16:24:48','2025-06-25 16:24:48'),(25,24,2,469.00,1,'2025-06-25',NULL,'2025-06-25 16:26:36','2025-06-25 16:26:36'),(26,24,2,331.00,1,'2025-06-25',NULL,'2025-06-25 16:28:51','2025-06-25 16:28:51'),(27,5,2,84.00,1,'2025-06-25',NULL,'2025-06-25 16:36:38','2025-06-25 16:36:38'),(28,24,2,568.00,2,'2025-06-25',NULL,'2025-06-25 17:45:28','2025-06-25 17:45:28');
/*!40000 ALTER TABLE `harvests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `harvests` with 28 row(s)
--

--
-- Table structure for table `inventory_reservations`
--

DROP TABLE IF EXISTS `inventory_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_reservations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_inventory_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','fulfilled','cancelled') NOT NULL DEFAULT 'pending',
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
SET autocommit=0;
/*!40000 ALTER TABLE `inventory_reservations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `inventory_reservations` with 0 row(s)
--

--
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_inventory_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `type` enum('production','purchase','sale','return','adjustment','damage','expiration','transfer','reservation','release') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
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
  CONSTRAINT `inventory_transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `inventory_transactions` with 0 row(s)
--

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `is_consolidated` tinyint(1) NOT NULL DEFAULT 0,
  `consolidated_order_count` int(11) DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `invoices` with 0 row(s)
--

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `jobs` with 0 row(s)
--

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `job_batches` with 0 row(s)
--

--
-- Table structure for table `master_cultivars`
--

DROP TABLE IF EXISTS `master_cultivars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_cultivars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `master_seed_catalog_id` bigint(20) unsigned NOT NULL,
  `cultivar_name` varchar(255) NOT NULL,
  `aliases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aliases`)),
  `description` text DEFAULT NULL,
  `growing_notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_cultivars_master_seed_catalog_id_cultivar_name_unique` (`master_seed_catalog_id`,`cultivar_name`),
  KEY `master_cultivars_cultivar_name_index` (`cultivar_name`),
  KEY `master_cultivars_is_active_index` (`is_active`),
  CONSTRAINT `master_cultivars_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_cultivars`
--

LOCK TABLES `master_cultivars` WRITE;
/*!40000 ALTER TABLE `master_cultivars` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `master_cultivars` (`id`, `master_seed_catalog_id`, `cultivar_name`, `aliases`, `description`, `growing_notes`, `is_active`, `created_at`, `updated_at`) VALUES (1,1,'Black Oilseed',NULL,NULL,NULL,1,'2025-06-24 16:57:48','2025-06-24 16:57:48'),(2,2,'Genovese',NULL,NULL,NULL,1,'2025-06-24 21:14:14','2025-06-24 21:14:14'),(3,2,'Thai',NULL,NULL,NULL,1,'2025-06-24 21:14:14','2025-06-24 21:14:14'),(4,3,'Red',NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(5,4,'Arugula',NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(6,5,'Bull’s Blood',NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(7,5,'Pink',NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(8,5,'Ruby',NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(9,6,'Borage',NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(10,7,'Broccoli',NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(11,8,'Red',NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(12,9,'Red',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(13,10,'Coriander',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(14,11,'Curly (Garden )',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(15,12,'Dill',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(16,13,'Beans',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(17,14,'Fennel',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(18,15,'Fenugreek',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(19,16,'Green',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(20,16,'Red',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(21,17,'Purple',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(22,18,'Komatsuna',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(23,19,'Oriental',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(24,20,'Speckled',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(25,21,'Red',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(26,21,'Ruby Stem',NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55');
/*!40000 ALTER TABLE `master_cultivars` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `master_cultivars` with 26 row(s)
--

--
-- Table structure for table `master_seed_catalog`
--

DROP TABLE IF EXISTS `master_seed_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_seed_catalog` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `common_name` varchar(255) NOT NULL,
  `cultivars` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cultivars`)),
  `category` varchar(255) DEFAULT NULL,
  `aliases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aliases`)),
  `growing_notes` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_seed_catalog_common_name_unique` (`common_name`),
  KEY `master_seed_catalog_category_index` (`category`),
  KEY `master_seed_catalog_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `master_seed_catalog`
--

LOCK TABLES `master_seed_catalog` WRITE;
/*!40000 ALTER TABLE `master_seed_catalog` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `master_seed_catalog` (`id`, `common_name`, `cultivars`, `category`, `aliases`, `growing_notes`, `description`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Sunflower','[\"Black Oilseed\"]',NULL,'[\"Black Oil\", \"sunflower\"]',NULL,NULL,1,'2025-06-24 16:57:48','2025-06-24 16:58:18'),(2,'Basil','[\"Genovese\", \"Thai\"]',NULL,NULL,NULL,NULL,1,'2025-06-24 21:14:14','2025-06-24 21:14:14'),(3,'Amaranth','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(4,'Arugula','[\"Arugula\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(5,'Beet','[\"Bull\\u2019s Blood\",\"Pink\",\"Ruby\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:14','2025-06-25 13:22:14'),(6,'Borage','[\"Borage\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(7,'Broccoli','[\"Broccoli\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(8,'Cabbage','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:22:37','2025-06-25 13:22:37'),(9,'Clover','[\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(10,'Coriander','[\"Coriander\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(11,'Cress','[\"Curly (Garden )\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(12,'Dill','[\"Dill\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(13,'Fava','[\"Beans\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(14,'Fennel','[\"Fennel\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(15,'Fenugreek','[\"Fenugreek\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(16,'Kale','[\"Green\",\"Red\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(17,'Kohlrabi','[\"Purple\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(18,'Komatsuna','[\"Komatsuna\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(19,'Mustard','[\"Oriental\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(20,'Peas,','[\"Speckled\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55'),(21,'Radish','[\"Red\",\"Ruby Stem\"]',NULL,NULL,NULL,NULL,1,'2025-06-25 13:23:55','2025-06-25 13:23:55');
/*!40000 ALTER TABLE `master_seed_catalog` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `master_seed_catalog` with 21 row(s)
--

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=167 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_08_15_000000_create_crop_alerts_table',1),(5,'2025_03_15_055950_create_permission_tables',1),(6,'2025_03_15_060211_create_suppliers_table',1),(7,'2025_03_15_060212_create_seed_varieties_table',1),(8,'2025_03_15_060214_create_recipes_table',1),(9,'2025_03_15_060215_create_recipe_stages_table',1),(10,'2025_03_15_060305_create_recipe_watering_schedule_table',1),(11,'2025_03_15_060319_create_recipe_mixes_table',1),(12,'2025_03_15_060335_create_crops_table',1),(13,'2025_03_15_060352_create_inventory_table',1),(14,'2025_03_15_060353_create_consumables_table',1),(15,'2025_03_15_060353_create_orders_table',1),(16,'2025_03_15_060355_create_invoices_table',1),(17,'2025_03_15_060355_create_payments_table',1),(18,'2025_03_15_060355_create_settings_table',1),(19,'2025_03_15_060355_drop_inventory_table',1),(20,'2025_03_15_060527_fix_migration_order',1),(21,'2025_03_15_063501_create_activity_log_table',1),(22,'2025_03_15_070829_create_personal_access_tokens_table',1),(23,'2025_03_21_002206_create_packaging_types_table',1),(24,'2025_03_21_002211_create_order_packagings_table',1),(25,'2025_03_21_031151_migrate_legacy_images_to_item_photos',1),(26,'2025_03_21_032617_remove_code_field_from_items_table',1),(27,'2025_03_23_192440_add_light_days_to_recipes_table',1),(28,'2025_03_25_235525_create_task_schedules_table',1),(29,'2025_03_25_235534_create_notification_settings_table',1),(30,'2025_03_26_010126_update_packaging_types_add_volume_field',1),(31,'2025_03_26_010933_remove_capacity_grams_from_packaging_types',1),(32,'2025_03_26_045009_add_soil_consumable_id_to_recipes_table',1),(33,'2025_04_09_020444_add_stage_timestamps_to_crops_table',1),(34,'2025_04_09_045210_create_tasks_table',1),(35,'2025_04_17_185454_add_packaging_type_foreign_key_to_consumables',1),(36,'2025_04_17_234148_update_consumable_unit_types',1),(37,'2025_04_17_234403_update_lot_no_to_uppercase',1),(38,'2025_04_18_003016_add_units_quantity_to_consumables_table',1),(39,'2025_04_18_003759_update_consumable_unit_types_to_simpler_values',1),(40,'2025_04_18_010330_add_consumed_quantity_to_consumables_table',1),(41,'2025_04_18_014631_update_consumables_decimal_precision',1),(42,'2025_04_18_025334_update_clamshell_packaging_types_volume',1),(43,'2025_04_18_034705_add_watering_method_to_recipe_watering_schedule',1),(44,'2025_04_18_042544_rename_seed_soak_days_to_hours_in_recipes_table',1),(45,'2025_04_18_054155_fix_recipe_seed_variety_relationship',1),(46,'2025_04_18_100000_change_seed_soak_days_to_decimal',1),(47,'2025_04_19_000000_drop_recipe_mixes_table',1),(48,'2025_04_19_031951_remove_notes_from_recipes',1),(49,'2025_04_19_035640_add_growth_phase_notes_columns_to_recipes_table',1),(50,'2025_04_19_041217_add_seed_variety_id_to_consumables',1),(51,'2025_04_19_043838_update_consumed_quantity_default_on_consumables',1),(52,'2025_04_19_044201_update_total_quantity_default_on_consumables',1),(53,'2025_04_19_045350_update_consumables_table_structure',1),(54,'2025_04_19_045809_add_missing_columns_to_seed_varieties',1),(55,'2025_04_19_050518_update_crops_recipe_foreign_key',1),(56,'2025_04_19_052750_add_crop_type_to_seed_varieties_table',1),(57,'2025_05_01_133249_add_time_to_next_stage_minutes_to_crops_table',1),(58,'2025_05_01_143431_add_stage_age_minutes_to_crops_table',1),(59,'2025_05_01_144928_add_total_age_minutes_to_crops_table',1),(60,'2025_05_02_165743_update_time_to_next_stage_minutes_column_type',1),(61,'2025_05_02_165851_update_stage_age_minutes_column_type',1),(62,'2025_05_02_165855_update_total_age_minutes_column_type',1),(63,'2025_05_02_205557_create_crop_batches_view',1),(64,'2025_05_03_000000_add_calculated_columns_to_crops_table',1),(65,'2025_05_03_222337_add_suspend_watering_to_recipes_table',1),(66,'2025_05_03_222805_remove_stage_notes_from_recipes_table',1),(67,'2025_05_03_222911_rename_suspend_watering_hours_column_in_recipes_table',1),(68,'2025_05_03_224138_create_crop_tasks_table',1),(69,'2025_05_03_224935_create_notifications_table',1),(70,'2025_05_07_094527_create_products_table',1),(71,'2025_05_07_094528_create_price_variations_for_existing_products',1),(72,'2025_05_07_094529_create_product_photos_table',1),(73,'2025_05_07_094530_remove_caption_from_product_photos',1),(74,'2025_05_09_000000_update_stage_age_minutes_column_type',1),(75,'2025_05_20_201327_add_indexes_for_optimization',1),(76,'2025_05_26_162845_create_seed_cultivars_table',1),(77,'2025_05_26_162849_create_seed_entries_table',1),(78,'2025_05_26_162852_create_seed_variations_table',1),(79,'2025_05_26_162855_create_seed_price_history_table',1),(80,'2025_05_26_162859_create_seed_scrape_uploads_table',1),(81,'2025_05_26_162902_add_consumable_id_to_seed_variations',1),(82,'2025_06_03_100000_placeholder_notes_column_decision',1),(83,'2025_06_03_141432_add_missing_foreign_key_constraints',1),(84,'2025_06_03_141453_add_critical_performance_indexes',1),(85,'2025_06_03_213058_add_recurring_order_support_to_orders_table',1),(86,'2025_06_03_220125_create_product_mixes_table',1),(87,'2025_06_03_220129_create_product_mix_components_table',1),(88,'2025_06_03_223329_add_packaging_type_to_price_variations_table',1),(89,'2025_06_03_223734_add_fill_weight_to_price_variations_table',1),(90,'2025_06_03_224520_add_active_column_to_products_table',1),(91,'2025_06_03_224602_make_sku_nullable_in_products_table',1),(92,'2025_06_04_072532_add_customer_type_to_users_table',1),(93,'2025_06_04_073839_update_orders_status_enum_values',1),(94,'2025_06_04_075015_update_invoice_foreign_key_to_cascade_on_delete',1),(95,'2025_06_04_075517_add_price_variation_id_to_order_products_table',1),(96,'2025_06_04_083155_add_preferences_to_users_table',1),(97,'2025_06_04_090627_remove_preferences_from_users_table',1),(98,'2025_06_04_100000_migrate_recipes_to_seed_cultivar',1),(99,'2025_06_04_100001_add_seed_variety_fields_to_seed_cultivars',1),(100,'2025_06_04_100002_remove_seed_variety_id_from_consumables',1),(101,'2025_06_04_100004_update_product_mix_components_to_seed_cultivar',1),(102,'2025_06_04_100005_drop_seed_varieties_table',1),(103,'2025_06_05_075524_simplify_seed_structure_add_names_to_entries',1),(104,'2025_06_05_075648_fix_common_names_in_seed_entries',1),(105,'2025_06_05_085532_make_seed_cultivar_id_nullable_in_seed_entries',1),(106,'2025_06_05_193018_add_cataloged_at_to_seed_entries_table',1),(107,'2025_06_05_193715_create_supplier_source_mappings_table',1),(108,'2025_06_08_092642_remove_cataloged_at_from_seed_entries_table',1),(109,'2025_06_09_062308_add_seed_entry_id_to_consumables_table',1),(110,'2025_06_09_063844_add_is_active_to_seed_entries_table',1),(111,'2025_06_09_064442_fix_recipes_seed_entry_foreign_key',1),(112,'2025_06_09_065222_rename_seed_cultivar_id_to_seed_entry_id_in_recipes',1),(113,'2025_06_09_065622_rename_seed_cultivar_id_to_seed_entry_id_in_product_mix_components',1),(114,'2025_06_09_111847_add_failed_entries_to_seed_scrape_uploads_table',1),(115,'2025_06_09_130054_make_current_price_nullable_in_seed_variations_table',1),(116,'2025_06_09_155051_make_cost_per_unit_nullable_in_consumables_table',1),(117,'2025_06_09_174941_make_harvest_date_nullable_in_orders_table',1),(118,'2025_06_09_180239_add_order_classification_to_orders_table',1),(119,'2025_06_09_180649_add_consolidated_invoice_support_to_invoices_table',1),(120,'2025_06_09_195832_make_order_id_nullable_in_invoices_table',1),(121,'2025_06_09_222238_add_billing_period_to_orders_table',1),(122,'2025_06_09_233139_create_crop_plans_table',1),(123,'2025_06_09_233223_add_crop_plan_id_to_crops_table',1),(124,'2025_06_11_133418_create_product_inventory_system',1),(125,'2025_06_11_151000_add_seed_entry_to_products_if_not_exists',1),(126,'2025_06_11_210426_create_master_seed_catalog_table',1),(127,'2025_06_11_210429_create_master_cultivars_table',1),(128,'2025_06_11_221240_change_scientific_name_to_json_in_master_seed_catalog',1),(129,'2025_06_11_225657_add_master_seed_catalog_id_to_consumables_table',1),(130,'2025_06_11_230351_add_cultivars_column_to_master_seed_catalog_table',1),(131,'2025_06_11_230435_rename_scientific_name_to_cultivars_in_master_seed_catalog',1),(132,'2025_06_11_231627_add_master_seed_catalog_id_to_products_table',1),(133,'2025_06_11_231700_replace_seed_entry_id_with_master_seed_catalog_id_in_products',1),(134,'2025_06_12_085856_add_template_id_to_price_variations_table',1),(135,'2025_06_12_184326_add_soft_deletes_to_consumables_table',1),(136,'2025_06_12_200016_add_master_cultivar_id_to_consumables_table',1),(137,'2025_06_12_201000_populate_master_cultivar_id_in_consumables',1),(138,'2025_06_12_204424_make_seed_entry_id_nullable_in_product_mix_components',1),(139,'2025_06_12_204633_remove_seed_entry_id_from_product_mix_components',1),(140,'2025_06_12_205054_fix_product_mix_components_unique_constraints',1),(141,'2025_06_12_add_pricing_unit_to_price_variations',1),(142,'2025_06_13_161917_add_wholesale_discount_percentage_to_products_table',1),(143,'2025_06_13_163543_update_existing_products_wholesale_discount_default',1),(144,'2025_06_13_180604_add_unique_index_to_product_name',1),(145,'2025_06_13_214716_add_cultivar_to_consumables_table',1),(146,'2025_06_13_214757_populate_cultivar_in_consumables_table',1),(147,'2025_06_13_215428_update_percentage_precision_in_product_mix_components',1),(148,'2025_06_13_add_cascade_delete_to_product_relations',1),(149,'2025_06_13_remove_batch_number_from_product_inventories',1),(150,'2025_06_18_112138_create_harvests_table',1),(151,'2025_06_18_122409_add_buffer_percentage_to_recipes_table',1),(152,'2025_06_18_142749_add_packed_status_to_orders_enum',1),(153,'2025_06_18_180519_separate_order_statuses_into_distinct_columns',1),(154,'2025_06_18_182436_create_data_exports_table',1),(155,'2025_06_19_073000_drop_data_exports_table',1),(156,'2025_06_19_111313_insert_harvest_data',1),(157,'2025_06_19_141353_add_wholesale_discount_percentage_to_users_table',1),(158,'2025_06_19_180500_make_customer_type_nullable_in_orders_table',1),(159,'2025_06_19_181751_add_b2b_to_order_type_enum',1),(160,'2025_08_15_000001_add_days_to_maturity_to_recipes_table',1),(161,'2025_06_24_091546_create_time_cards_table',2),(162,'2025_06_24_092916_add_review_fields_to_time_cards_table',3),(163,'2025_06_24_104741_add_other_type_to_suppliers_table',4),(164,'2025_06_24_110323_add_packaging_type_to_suppliers_table',5),(165,'2025_06_24_145952_create_task_types_table',6),(166,'2025_06_24_150348_create_time_card_tasks_table',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `migrations` with 166 row(s)
--

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `model_has_permissions` with 0 row(s)
--

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
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
SET autocommit=0;
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES (1,'App\\Models\\User',2);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `model_has_roles` with 1 row(s)
--

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `notifications` with 0 row(s)
--

--
-- Table structure for table `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(255) NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`recipients`)),
  `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `email_subject_template` varchar(255) NOT NULL,
  `email_body_template` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_settings_resource_type_event_type_unique` (`resource_type`,`event_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `notification_settings` with 0 row(s)
--

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `harvest_date` date DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `status` enum('draft','pending','confirmed','processing','completed','cancelled','template') NOT NULL DEFAULT 'pending',
  `crop_status` enum('not_started','planted','growing','ready_to_harvest','harvested','na') NOT NULL DEFAULT 'not_started',
  `fulfillment_status` enum('pending','processing','packing','packed','ready_for_delivery','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `customer_type` enum('retail','wholesale') DEFAULT NULL,
  `order_type` enum('farmers_market','b2b','b2b_recurring','website_immediate') NOT NULL DEFAULT 'website_immediate',
  `billing_frequency` enum('immediate','weekly','monthly','quarterly') NOT NULL DEFAULT 'immediate',
  `requires_invoice` tinyint(1) NOT NULL DEFAULT 1,
  `billing_period_start` date DEFAULT NULL,
  `billing_period_end` date DEFAULT NULL,
  `consolidated_invoice_id` bigint(20) unsigned DEFAULT NULL,
  `billing_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`billing_preferences`)),
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_recurring_order_id` bigint(20) unsigned DEFAULT NULL,
  `recurring_frequency` enum('weekly','biweekly','monthly') DEFAULT NULL,
  `recurring_start_date` date DEFAULT NULL,
  `recurring_end_date` date DEFAULT NULL,
  `billing_period` varchar(255) DEFAULT NULL COMMENT 'Billing period for consolidated invoicing (e.g., 2024-01 for monthly, 2024-W15 for weekly)',
  `is_recurring_active` tinyint(1) NOT NULL DEFAULT 1,
  `recurring_days_of_week` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recurring_days_of_week`)),
  `recurring_interval` int(11) DEFAULT NULL,
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
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `orders` with 0 row(s)
--

--
-- Table structure for table `order_packagings`
--

DROP TABLE IF EXISTS `order_packagings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_packagings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `packaging_type_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `order_packagings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `order_packagings` with 0 row(s)
--

--
-- Table structure for table `order_products`
--

DROP TABLE IF EXISTS `order_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `price_variation_id` bigint(20) unsigned DEFAULT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `order_products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `order_products` with 0 row(s)
--

--
-- Table structure for table `packaging_types`
--

DROP TABLE IF EXISTS `packaging_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `packaging_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `capacity_volume` decimal(8,2) NOT NULL DEFAULT 0.00,
  `volume_unit` varchar(20) NOT NULL DEFAULT 'oz',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `cost_per_unit` decimal(8,2) NOT NULL DEFAULT 0.00,
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
SET autocommit=0;
INSERT INTO `packaging_types` (`id`, `name`, `capacity_volume`, `volume_unit`, `description`, `is_active`, `cost_per_unit`, `created_at`, `updated_at`) VALUES (1,'16oz Clamshell',16.00,'oz',NULL,1,0.00,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(2,'24oz Clamshell',24.00,'oz',NULL,1,0.00,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(3,'32oz Clamshell',32.00,'oz',NULL,1,0.00,'2025-06-24 18:01:03','2025-06-24 18:01:03'),(4,'48oz Clamshell',48.00,'oz',NULL,1,0.00,'2025-06-24 18:01:04','2025-06-24 18:01:04'),(5,'64oz Clamshell',64.00,'oz',NULL,1,0.00,'2025-06-24 18:01:04','2025-06-24 18:01:04');
/*!40000 ALTER TABLE `packaging_types` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `packaging_types` with 5 row(s)
--

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `password_reset_tokens` with 0 row(s)
--

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` enum('stripe','e-transfer','cash','invoice') NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `payments` with 0 row(s)
--

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
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
SET autocommit=0;
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (1,'manage products','web','2025-06-23 02:33:31','2025-06-23 02:33:31'),(2,'view products','web','2025-06-23 02:33:31','2025-06-23 02:33:31'),(3,'edit products','web','2025-06-23 02:33:31','2025-06-23 02:33:31'),(4,'delete products','web','2025-06-23 02:33:31','2025-06-23 02:33:31'),(5,'access filament','web','2025-06-23 02:33:31','2025-06-23 02:33:31');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `permissions` with 5 row(s)
--

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
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
SET autocommit=0;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `personal_access_tokens` with 0 row(s)
--

--
-- Table structure for table `price_variations`
--

DROP TABLE IF EXISTS `price_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `price_variations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `pricing_unit` varchar(20) NOT NULL DEFAULT 'per_item',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_global` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'When true, this variation can be used with any product',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `template_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `packaging_type_id` bigint(20) unsigned DEFAULT NULL,
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
SET autocommit=0;
INSERT INTO `price_variations` (`id`, `name`, `sku`, `price`, `pricing_unit`, `is_default`, `is_global`, `is_active`, `product_id`, `template_id`, `created_at`, `updated_at`, `packaging_type_id`, `fill_weight_grams`) VALUES (1,'Clamshell (24oz) (Ret)',NULL,5.00,'per_item',0,1,1,NULL,NULL,'2025-06-24 18:40:05','2025-06-24 18:42:41',2,70.00),(2,'Clamshell (32oz) (Ret)',NULL,5.00,'per_item',0,1,1,NULL,NULL,'2025-06-24 18:40:47','2025-06-24 18:42:58',3,70.00),(3,'Bulk',NULL,0.20,'per_g',0,1,1,NULL,NULL,'2025-06-24 18:44:16','2025-06-24 18:44:16',NULL,NULL),(4,'Live Tray',NULL,30.00,'per_item',0,1,1,NULL,NULL,'2025-06-24 18:44:47','2025-06-24 18:44:47',NULL,NULL),(5,'Default',NULL,30.00,'per_item',1,0,1,1,1,'2025-06-24 18:45:58','2025-06-24 18:46:21',NULL,80.00),(7,'Default',NULL,0.20,'per_item',0,0,1,1,3,'2025-06-24 18:45:58','2025-06-24 18:45:58',NULL,NULL),(8,'Default',NULL,30.00,'per_item',0,0,1,1,4,'2025-06-24 18:45:58','2025-06-24 18:45:58',NULL,NULL);
/*!40000 ALTER TABLE `price_variations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `price_variations` with 7 row(s)
--

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `total_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reserved_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `available_stock` decimal(10,2) GENERATED ALWAYS AS (`total_stock` - `reserved_stock`) VIRTUAL,
  `reorder_threshold` decimal(10,2) NOT NULL DEFAULT 0.00,
  `track_inventory` tinyint(1) NOT NULL DEFAULT 1,
  `stock_status` enum('in_stock','low_stock','out_of_stock','discontinued') NOT NULL DEFAULT 'in_stock',
  `wholesale_discount_percentage` decimal(5,2) NOT NULL DEFAULT 25.00,
  `sku` varchar(255) DEFAULT NULL,
  `base_price` decimal(10,2) DEFAULT NULL,
  `wholesale_price` decimal(10,2) DEFAULT NULL,
  `bulk_price` decimal(10,2) DEFAULT NULL,
  `special_price` decimal(10,2) DEFAULT NULL,
  `is_visible_in_store` tinyint(1) NOT NULL DEFAULT 1,
  `image` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `product_mix_id` bigint(20) unsigned DEFAULT NULL,
  `master_seed_catalog_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  UNIQUE KEY `products_name_unique` (`name`,`deleted_at`),
  KEY `products_is_visible_in_store_index` (`is_visible_in_store`),
  KEY `products_category_id_index` (`category_id`),
  KEY `products_product_mix_id_index` (`product_mix_id`),
  KEY `products_master_seed_catalog_id_foreign` (`master_seed_catalog_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_product_mix_id_foreign` FOREIGN KEY (`product_mix_id`) REFERENCES `product_mixes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `products` (`id`, `name`, `description`, `active`, `total_stock`, `reserved_stock`, `reorder_threshold`, `track_inventory`, `stock_status`, `wholesale_discount_percentage`, `sku`, `base_price`, `wholesale_price`, `bulk_price`, `special_price`, `is_visible_in_store`, `image`, `category_id`, `product_mix_id`, `master_seed_catalog_id`, `created_at`, `updated_at`, `deleted_at`) VALUES (1,'Sunflower',NULL,1,0.00,0.00,0.00,1,'out_of_stock',30.00,NULL,NULL,NULL,NULL,NULL,1,NULL,1,NULL,1,'2025-06-24 18:45:58','2025-06-24 18:45:58',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `products` with 1 row(s)
--

--
-- Table structure for table `product_inventories`
--

DROP TABLE IF EXISTS `product_inventories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_inventories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `price_variation_id` bigint(20) unsigned DEFAULT NULL,
  `lot_number` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reserved_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `available_quantity` decimal(10,2) GENERATED ALWAYS AS (`quantity` - `reserved_quantity`) VIRTUAL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `production_date` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','depleted','expired','damaged') NOT NULL DEFAULT 'active',
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
SET autocommit=0;
INSERT INTO `product_inventories` (`id`, `product_id`, `price_variation_id`, `lot_number`, `quantity`, `reserved_quantity`, `cost_per_unit`, `expiration_date`, `production_date`, `location`, `notes`, `status`, `created_at`, `updated_at`) VALUES (1,1,5,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for 24oz Clamshell variation','active','2025-06-24 18:45:58','2025-06-24 18:45:58'),(2,1,NULL,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for 32oz Clamshell variation','active','2025-06-24 18:45:58','2025-06-24 18:45:58'),(3,1,7,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for Default variation','active','2025-06-24 18:45:58','2025-06-24 18:45:58'),(4,1,8,NULL,0.00,0.00,0.00,NULL,'2025-06-24',NULL,'Auto-created for Default variation','active','2025-06-24 18:45:58','2025-06-24 18:45:58');
/*!40000 ALTER TABLE `product_inventories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `product_inventories` with 4 row(s)
--

--
-- Table structure for table `product_mixes`
--

DROP TABLE IF EXISTS `product_mixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_mixes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
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
SET autocommit=0;
/*!40000 ALTER TABLE `product_mixes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `product_mixes` with 0 row(s)
--

--
-- Table structure for table `product_mix_components`
--

DROP TABLE IF EXISTS `product_mix_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_mix_components` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_mix_id` bigint(20) unsigned NOT NULL,
  `master_seed_catalog_id` bigint(20) unsigned NOT NULL,
  `cultivar` varchar(255) NOT NULL,
  `percentage` decimal(6,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mix_components_unique` (`product_mix_id`,`master_seed_catalog_id`,`cultivar`),
  KEY `product_mix_components_product_mix_id_index` (`product_mix_id`),
  KEY `product_mix_components_master_seed_catalog_id_foreign` (`master_seed_catalog_id`),
  CONSTRAINT `product_mix_components_master_seed_catalog_id_foreign` FOREIGN KEY (`master_seed_catalog_id`) REFERENCES `master_seed_catalog` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_mix_components_product_mix_id_foreign` FOREIGN KEY (`product_mix_id`) REFERENCES `product_mixes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_mix_components`
--

LOCK TABLES `product_mix_components` WRITE;
/*!40000 ALTER TABLE `product_mix_components` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `product_mix_components` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `product_mix_components` with 0 row(s)
--

--
-- Table structure for table `product_photos`
--

DROP TABLE IF EXISTS `product_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_photos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `photo` varchar(255) NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_photos_product_id_foreign` (`product_id`),
  CONSTRAINT `product_photos_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_photos`
--

LOCK TABLES `product_photos` WRITE;
/*!40000 ALTER TABLE `product_photos` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `product_photos` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `product_photos` with 0 row(s)
--

--
-- Table structure for table `recipes`
--

DROP TABLE IF EXISTS `recipes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `supplier_soil_id` bigint(20) unsigned DEFAULT NULL,
  `soil_consumable_id` bigint(20) unsigned DEFAULT NULL,
  `seed_consumable_id` bigint(20) unsigned DEFAULT NULL,
  `seed_density` decimal(8,2) DEFAULT NULL,
  `seed_soak_hours` int(11) NOT NULL DEFAULT 0,
  `germination_days` int(11) NOT NULL DEFAULT 3,
  `blackout_days` int(11) NOT NULL DEFAULT 0,
  `days_to_maturity` double DEFAULT NULL COMMENT 'Total days to maturity (harvest) from planting',
  `light_days` int(11) NOT NULL DEFAULT 0,
  `harvest_days` int(11) NOT NULL DEFAULT 7,
  `expected_yield_grams` decimal(8,2) DEFAULT NULL,
  `buffer_percentage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `seed_density_grams_per_tray` decimal(8,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `suspend_water_hours` int(11) DEFAULT 12,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `seed_entry_id` bigint(20) unsigned DEFAULT NULL,
  `common_name` varchar(255) DEFAULT NULL,
  `cultivar_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recipes_supplier_soil_id_foreign` (`supplier_soil_id`),
  KEY `recipes_soil_consumable_id_foreign` (`soil_consumable_id`),
  KEY `recipes_common_name_cultivar_name_index` (`common_name`,`cultivar_name`),
  KEY `recipes_seed_entry_id_foreign` (`seed_entry_id`),
  CONSTRAINT `recipes_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recipes_soil_consumable_id_foreign` FOREIGN KEY (`soil_consumable_id`) REFERENCES `consumables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recipes_supplier_soil_id_foreign` FOREIGN KEY (`supplier_soil_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (1,'SUNFLOWER - BLACK OIL - SF4K - 100G',NULL,2,1,NULL,9,3,1,9,5,7,450.00,15.00,100.00,NULL,24,1,'2025-06-24 17:00:50','2025-06-24 17:00:50',NULL,NULL,NULL),(2,'SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS',NULL,2,3,NULL,4,3,0,9,6,7,NULL,15.00,100.00,NULL,24,1,'2025-06-24 17:56:15','2025-06-24 17:56:15',NULL,NULL,NULL),(3,'BASIL (GENOVESE) - BAS8Y - 5G -21 DAY',NULL,2,9,NULL,0,4,0,21,17,7,80.00,10.00,5.00,NULL,0,1,'2025-06-24 21:16:57','2025-06-24 21:16:57',NULL,NULL,NULL);
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `recipes` with 3 row(s)
--

--
-- Table structure for table `recipe_stages`
--

DROP TABLE IF EXISTS `recipe_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `day` int(11) DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `stage` enum('germination','blackout','light') NOT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `temperature_min_celsius` double DEFAULT NULL,
  `temperature_max_celsius` double DEFAULT NULL,
  `humidity_min_percent` int(11) DEFAULT NULL,
  `humidity_max_percent` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipe_stages_recipe_id_stage_unique` (`recipe_id`,`stage`),
  CONSTRAINT `recipe_stages_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_stages`
--

LOCK TABLES `recipe_stages` WRITE;
/*!40000 ALTER TABLE `recipe_stages` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `recipe_stages` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `recipe_stages` with 0 row(s)
--

--
-- Table structure for table `recipe_watering_schedule`
--

DROP TABLE IF EXISTS `recipe_watering_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recipe_watering_schedule` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipe_id` bigint(20) unsigned NOT NULL,
  `day_number` int(11) NOT NULL,
  `water_amount_ml` int(11) NOT NULL,
  `watering_method` varchar(255) NOT NULL DEFAULT 'bottom' COMMENT 'bottom, top, mist',
  `needs_liquid_fertilizer` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipe_watering_schedule_recipe_id_day_number_unique` (`recipe_id`,`day_number`),
  CONSTRAINT `recipe_watering_schedule_recipe_id_foreign` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recipe_watering_schedule`
--

LOCK TABLES `recipe_watering_schedule` WRITE;
/*!40000 ALTER TABLE `recipe_watering_schedule` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `recipe_watering_schedule` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `recipe_watering_schedule` with 0 row(s)
--

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES (1,'admin','web','2025-06-23 02:33:23','2025-06-23 02:33:23'),(2,'manager','web','2025-06-23 02:33:31','2025-06-23 02:33:31'),(3,'user','web','2025-06-23 02:33:31','2025-06-23 02:33:31');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `roles` with 3 row(s)
--

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
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
SET autocommit=0;
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES (1,1),(2,1),(2,2),(2,3),(3,1),(3,2),(4,1),(5,1),(5,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `role_has_permissions` with 9 row(s)
--

--
-- Table structure for table `seed_cultivars`
--

DROP TABLE IF EXISTS `seed_cultivars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seed_cultivars` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `crop_type` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seed_cultivars_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_cultivars`
--

LOCK TABLES `seed_cultivars` WRITE;
/*!40000 ALTER TABLE `seed_cultivars` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `seed_cultivars` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `seed_cultivars` with 0 row(s)
--

--
-- Table structure for table `seed_entries`
--

DROP TABLE IF EXISTS `seed_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seed_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seed_cultivar_id` bigint(20) unsigned DEFAULT NULL,
  `cultivar_name` varchar(255) DEFAULT NULL,
  `common_name` varchar(255) DEFAULT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `supplier_product_title` varchar(255) NOT NULL,
  `supplier_product_url` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seed_entries_supplier_id_supplier_product_url_unique` (`supplier_id`,`supplier_product_url`),
  KEY `seed_entries_cultivar_supplier_index` (`seed_cultivar_id`,`supplier_id`),
  KEY `seed_entries_supplier_index` (`supplier_id`),
  KEY `seed_entries_common_name_cultivar_name_index` (`common_name`,`cultivar_name`),
  CONSTRAINT `seed_entries_seed_cultivar_id_foreign` FOREIGN KEY (`seed_cultivar_id`) REFERENCES `seed_cultivars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `seed_entries_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_entries`
--

LOCK TABLES `seed_entries` WRITE;
/*!40000 ALTER TABLE `seed_entries` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `seed_entries` (`id`, `seed_cultivar_id`, `cultivar_name`, `common_name`, `supplier_id`, `supplier_product_title`, `supplier_product_url`, `image_url`, `description`, `tags`, `is_active`, `created_at`, `updated_at`) VALUES (1,NULL,'Red','Amaranth',1,'Amaranth, Red','https://sprouting.com/product/amaranth-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(2,NULL,'Arugula','Arugula',1,'Arugula','https://sprouting.com/product/arugula/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(3,NULL,'Rocky Wild','Arugula',1,'Arugula, Rocky Wild','https://sprouting.com/product/arugula-rocky-wild/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(4,NULL,'Barley','Barley',1,'Barley, hulls on','https://sprouting.com/product/barley-hulls-on/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(5,NULL,'Genovese','Basil',1,'Basil, Genovese','https://sprouting.com/product/basil-genovese/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(6,NULL,'Purple','Basil',1,'Basil, Purple','https://sprouting.com/product/basil-purple/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(7,NULL,'Thai','Basil',1,'Basil, Thai','https://sprouting.com/product/basil-thai/',NULL,NULL,'[]',1,'2025-06-24 16:05:14','2025-06-24 16:05:14'),(8,NULL,'Bull’s Blood','Beet',1,'Beet, Bull’s Blood','https://sprouting.com/product/beet-bulls-blood/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(9,NULL,'Pink','Beet',1,'Beet, Pink','https://sprouting.com/product/beet-pink/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(10,NULL,'Ruby','Beet',1,'Beet, Ruby','https://sprouting.com/product/beet-ruby/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(11,NULL,'(Pak Choi)','Bok Choy',1,'Bok Choy (Pak Choi)','https://sprouting.com/product/bok-choy-pak-choi/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(12,NULL,'Borage','Borage',1,'Borage','https://sprouting.com/product/borage/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(13,NULL,'Blend','Brilliant',1,'Brilliant Blend','https://sprouting.com/product/brilliant-blend/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(14,NULL,'Broccoli','Broccoli',1,'Broccoli','https://sprouting.com/product/broccoli/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(15,NULL,'Raab (Rapini)','Broccoli',1,'Broccoli Raab (Rapini)','https://sprouting.com/product/broccoli-raab-rapini/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(16,NULL,'Buckwheat','Buckwheat',1,'Buckwheat','https://sprouting.com/product/buckwheat/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(17,NULL,'Red','Cabbage',1,'Cabbage, Red','https://sprouting.com/product/cabbage-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(18,NULL,'Carrot','Carrot',1,'Carrot','https://sprouting.com/product/carrot/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(19,NULL,'Celery','Celery',1,'Celery','https://sprouting.com/product/celery/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(20,NULL,'Chervil','Chervil',1,'Chervil','https://sprouting.com/product/chervil/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(21,NULL,'Black','Chia',1,'Chia, Black','https://sprouting.com/product/chia-black/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(22,NULL,'Chicory','Chicory',1,'Chicory','https://sprouting.com/product/chicory/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(23,NULL,'Crimson','Clover',1,'Clover, Crimson','https://sprouting.com/product/clover-crimson/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(24,NULL,'Red','Clover',1,'Clover, Red','https://sprouting.com/product/clover-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(25,NULL,'Collard','Collard',1,'Collard','https://sprouting.com/product/collard/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(26,NULL,'Coriander','Coriander',1,'Coriander','https://sprouting.com/product/coriander/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(27,NULL,'Corn Salad','Corn Salad',1,'Corn Salad (Mache)','https://sprouting.com/product/corn-salad/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(28,NULL,'Cranberry','Bean',1,'Cranberry Bean','https://sprouting.com/product/cranberry-bean/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(29,NULL,'Curly (Garden )','Cress',1,'Curly Cress (Garden Cress)','https://sprouting.com/product/curly-cress/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(30,NULL,'Dill','Dill',1,'Dill','https://sprouting.com/product/dill/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(31,NULL,'Endive','Endive',1,'Endive','https://sprouting.com/product/endive/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(32,NULL,'Beans','Fava',1,'Fava Beans','https://sprouting.com/product/fava-beans/',NULL,NULL,'[]',1,'2025-06-24 16:05:15','2025-06-24 16:05:15'),(33,NULL,'Fennel','Fennel',1,'Fennel','https://sprouting.com/product/fennel/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(34,NULL,'Fenugreek','Fenugreek',1,'Fenugreek','https://sprouting.com/product/fenugreek/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(35,NULL,'Garlic Chives','Garlic Chives',1,'Garlic Chives','https://sprouting.com/product/garlic-chives/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(36,NULL,'Green','Kale',1,'Kale, Green','https://sprouting.com/product/kale-green/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(37,NULL,'Red','Kale',1,'Kale, Red','https://sprouting.com/product/kale-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(38,NULL,'Kamut','Kamut',1,'Kamut®','https://sprouting.com/product/kamut/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(39,NULL,'Purple','Kohlrabi',1,'Kohlrabi, Purple','https://sprouting.com/product/kohlrabi-purple/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(40,NULL,'Komatsuna','Komatsuna',1,'Komatsuna','https://sprouting.com/product/komatsuna/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(41,NULL,'Purple','Komatsuna',1,'Komatsuna, Purple','https://sprouting.com/product/purple-komatsuna/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(42,NULL,'Leek','Leek',1,'Leek','https://sprouting.com/product/leek/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(43,NULL,'Lemon Balm','Lemon Balm',1,'Lemon Balm','https://sprouting.com/product/lemon-balm/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(44,NULL,'Black','Lentils,',1,'Lentils, Black','https://sprouting.com/product/lentils-black/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(45,NULL,'Crimson','Lentils,',1,'Lentils, Crimson','https://sprouting.com/product/lentils-crimson/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(46,NULL,'French','Lentils,',1,'Lentils, French','https://sprouting.com/product/lentils-french/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(47,NULL,'Large Green','Lentils,',1,'Lentils, Large Green','https://sprouting.com/product/lentils-large-green/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(48,NULL,'Small Green','Lentils,',1,'Lentils, Small Green','https://sprouting.com/product/lentils-small-green/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(49,NULL,'Mellow Microgreen Mix','Mellow Microgreen Mix',1,'Mellow Microgreen Mix','https://sprouting.com/product/mellow-microgreen-mix/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(50,NULL,'Microgreen Salad Mix','Microgreen Salad Mix',1,'Microgreen Salad Mix','https://sprouting.com/product/microgreen-salad-mix/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(51,NULL,'Beans','Mung',1,'Mung Beans','https://sprouting.com/product/mung-beans/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(52,NULL,'Brown','Mustard',1,'Mustard, Brown','https://sprouting.com/product/mustard-brown/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(53,NULL,'Mizuna','Mustard',1,'Mustard, Mizuna','https://sprouting.com/product/mustard-mizuna/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(54,NULL,'Oriental','Mustard',1,'Mustard, Oriental','https://sprouting.com/product/mustard-oriental/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(55,NULL,'Red','Mustard',1,'Mustard, Red','https://sprouting.com/product/mustard-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(56,NULL,'Red Mizuna','Mustard',1,'Mustard, Red Mizuna','https://sprouting.com/product/mustard-red-mizuna/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(57,NULL,'Ruby Streaks','Mustard',1,'Mustard, Ruby Streaks','https://sprouting.com/product/mustard-scarlett-frills/',NULL,NULL,'[]',1,'2025-06-24 16:05:16','2025-06-24 16:05:16'),(58,NULL,'Tat Soi','Mustard',1,'Mustard, Tat Soi','https://sprouting.com/product/mustard-tat-soi/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(59,NULL,'Tokyo Bekana','Mustard',1,'Mustard, Tokyo Bekana','https://sprouting.com/product/mustard-tokyo-bekana/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(60,NULL,'Yellow','Mustard',1,'Mustard, Yellow','https://sprouting.com/product/mustard-yellow/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(61,NULL,'Emerald','Nasturtium',1,'Nasturtium, Emerald','https://sprouting.com/product/nasturtium/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(62,NULL,'Red','Nasturtium',1,'Nasturtium, Red','https://sprouting.com/product/red-nasturtium/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(63,NULL,'Hulless','Oats,',1,'Oats, Hulless','https://sprouting.com/product/oats-hulless/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(64,NULL,'Onion','Onion',1,'Onion','https://sprouting.com/product/onion/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(65,NULL,'Parsley','Parsley',1,'Parsley','https://sprouting.com/product/parsley/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(66,NULL,'Dwarf Grey Sugar','Peas,',1,'Peas, Dwarf Grey Sugar','https://sprouting.com/product/peas-dwarf-grey-sugar/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(67,NULL,'Green','Peas,',1,'Peas, Green','https://sprouting.com/product/peas-green/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(68,NULL,'Oregon Giant','Peas,',1,'Peas, Oregon Giant','https://sprouting.com/product/peas-oregon-giant/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(69,NULL,'Speckled','Peas,',1,'Peas, Speckled','https://sprouting.com/product/peas-speckled/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(70,NULL,'Yellow','Peas,',1,'Peas, Yellow','https://sprouting.com/product/peas-yellow/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(71,NULL,'Popcorn','Popcorn',1,'Popcorn','https://sprouting.com/product/popcorn/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(72,NULL,'Daikon','Radish',1,'Radish, Daikon','https://sprouting.com/product/radish-daikon/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(73,NULL,'Red','Radish',1,'Radish, Red','https://sprouting.com/product/radish-red/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(74,NULL,'Ruby Stem','Radish',1,'Radish, Ruby Stem','https://sprouting.com/product/radish-ruby-stem/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(75,NULL,'Triton','Radish',1,'Radish, Triton','https://sprouting.com/product/radish-triton/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(76,NULL,'Rainbow','Radish',1,'Rainbow Radish','https://sprouting.com/product/rainbow-radish/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(77,NULL,'Red Shiso','Red Shiso',1,'Red Shiso (Perilla)','https://sprouting.com/product/perilla/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(78,NULL,'Microgreen Blend','Spicy',1,'Spicy Microgreen Blend','https://sprouting.com/product/spicy-microgreen-blend/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(79,NULL,'Spigarello','Spigarello',1,'Spigarello','https://sprouting.com/product/spigarello/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(80,NULL,'Black Oilseed','Sunflower',1,'Sunflower, Black Oilseed','https://sprouting.com/product/sunflower-black-oilseed/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(81,NULL,'Red','Swiss Chard',1,'Swiss Chard, Red','https://sprouting.com/product/swiss-chard-ruby/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(82,NULL,'Yellow','Swiss Chard',1,'Swiss Chard, Yellow','https://sprouting.com/product/swiss-chard-yellow/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(83,NULL,'Thyme','Thyme',1,'Thyme','https://sprouting.com/product/thyme/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(84,NULL,'Turnip','Turnip',1,'Turnip','https://sprouting.com/product/turnip/',NULL,NULL,'[]',1,'2025-06-24 16:05:17','2025-06-24 16:05:17'),(85,NULL,'Watercress','Watercress',1,'Watercress','https://sprouting.com/product/watercress/',NULL,NULL,'[]',1,'2025-06-24 16:05:18','2025-06-24 16:05:18'),(86,NULL,'Hard Red Spring','Wheat',1,'Wheat, Hard Red Spring','https://sprouting.com/product/wheat-hard-red-spring/',NULL,NULL,'[]',1,'2025-06-24 16:05:18','2025-06-24 16:05:18'),(87,NULL,'Hard Red Winter','Wheat',1,'Wheat, Hard Red Winter','https://sprouting.com/product/wheat-hard-red-winter/',NULL,NULL,'[]',1,'2025-06-24 16:05:18','2025-06-24 16:05:18');
/*!40000 ALTER TABLE `seed_entries` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `seed_entries` with 87 row(s)
--

--
-- Table structure for table `seed_price_history`
--

DROP TABLE IF EXISTS `seed_price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seed_price_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seed_variation_id` bigint(20) unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `is_in_stock` tinyint(1) NOT NULL,
  `scraped_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seed_price_variation_scraped_index` (`seed_variation_id`,`scraped_at`),
  KEY `seed_price_scraped_at_index` (`scraped_at`),
  CONSTRAINT `seed_price_history_seed_variation_id_foreign` FOREIGN KEY (`seed_variation_id`) REFERENCES `seed_variations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=417 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_price_history`
--

LOCK TABLES `seed_price_history` WRITE;
/*!40000 ALTER TABLE `seed_price_history` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `seed_price_history` (`id`, `seed_variation_id`, `price`, `currency`, `is_in_stock`, `scraped_at`, `created_at`, `updated_at`) VALUES (1,1,16.09,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(2,2,231.16,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(3,3,1150.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(4,4,7.12,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(5,5,12.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(6,6,34.60,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(7,7,167.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(8,8,333.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(9,9,829.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(10,10,27.54,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(11,11,163.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(12,12,810.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(13,13,1.56,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(14,14,2.86,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(15,15,9.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(16,16,15.76,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(17,17,35.14,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(18,18,16.35,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(19,19,118.50,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(20,20,586.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(21,21,1172.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(22,22,13.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(23,23,93.02,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(24,24,459.40,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(25,25,917.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(26,26,147.86,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:14','2025-06-24 16:05:14'),(27,27,733.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(28,28,1465.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(29,29,3.50,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(30,31,40.42,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(31,32,196.40,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(32,33,391.97,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(33,34,43.78,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(34,35,213.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(35,36,425.57,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(36,37,4.99,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(37,38,7.02,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(38,39,42.34,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(39,40,214.47,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(40,41,424.84,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(41,42,7.01,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(42,43,12.11,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(43,44,42.00,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(44,45,204.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(45,46,407.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(46,47,1014.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(47,48,11.34,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(48,49,37.34,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(49,50,181.00,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(50,51,360.44,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(51,52,3.58,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(52,53,7.18,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(53,54,13.79,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(54,55,63.25,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(55,56,124.94,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(56,57,6.33,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(57,58,10.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(58,59,36.41,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(59,60,176.34,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(60,61,351.12,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(61,62,874.57,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(62,63,4.23,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(63,64,7.05,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(64,65,19.20,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(65,66,90.30,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(66,67,179.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(67,68,444.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(68,69,2.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(69,70,4.86,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(70,71,7.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(71,72,33.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(72,73,65.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(73,74,159.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(74,75,9.22,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(75,76,16.13,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(76,77,60.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(77,78,294.80,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(78,79,588.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(79,80,1466.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(80,81,9.29,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(81,82,119.78,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(82,83,593.20,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(83,84,1184.84,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(84,85,11.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(85,86,152.92,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(86,87,758.90,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(87,88,1516.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(88,89,16.20,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(89,90,155.78,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(90,91,773.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(91,92,1544.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(92,93,5.55,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(93,94,12.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(94,95,56.50,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(95,96,111.14,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(96,97,275.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(97,98,5.07,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(98,99,51.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(99,100,247.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(100,101,493.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(101,102,1101.08,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(102,103,5.23,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(103,104,8.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(104,105,22.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(105,106,105.50,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(106,107,209.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(107,108,520.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(108,109,4.55,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(109,110,7.62,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(110,111,17.74,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(111,112,83.00,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(112,113,164.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(113,114,407.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(114,115,7.94,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(115,116,65.56,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(116,117,322.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(117,118,642.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(118,119,1603.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(119,120,4.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(120,121,2.38,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(121,122,9.74,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(122,123,43.00,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(123,124,84.44,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(124,125,5.59,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(125,126,109.96,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(126,127,544.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(127,128,1086.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(128,129,5.19,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(129,130,8.98,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(130,131,39.20,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(131,132,76.84,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(132,133,188.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(133,134,5.05,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(134,135,8.54,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(135,136,21.06,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(136,137,99.60,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(137,138,197.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(138,139,490.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(139,140,5.49,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(140,141,9.34,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(141,142,57.46,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(142,143,281.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(143,144,561.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(144,145,8.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(145,146,109.96,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(146,147,544.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(147,148,1086.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(148,149,4.66,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(149,150,6.16,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(150,151,25.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(151,152,48.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:15','2025-06-24 16:05:15'),(152,153,118.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(153,154,6.94,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(154,155,81.28,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(155,156,400.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(156,157,799.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(157,158,3.57,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(158,159,5.85,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(159,160,11.36,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(160,161,49.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(161,162,100.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(162,163,248.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(163,164,12.32,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(164,165,103.16,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(165,166,504.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(166,167,1006.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(167,168,9.94,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(168,169,17.43,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(169,170,65.96,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(170,171,324.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(171,172,646.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(172,173,1613.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(173,174,8.95,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(174,175,57.86,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(175,176,283.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(176,177,565.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(177,178,1410.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(178,179,2.69,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(179,180,4.46,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(180,181,5.56,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(181,182,22.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(182,183,42.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(183,184,103.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(184,185,8.71,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(185,186,15.19,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(186,187,55.90,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(187,188,273.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(188,189,546.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(189,190,1361.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(190,191,7.96,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(191,192,72.02,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(192,193,354.40,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(193,194,707.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(194,195,1764.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(195,196,8.27,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(196,197,72.02,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(197,198,354.40,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(198,199,603.35,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(199,200,11.72,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(200,201,97.30,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(201,202,480.80,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(202,203,960.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(203,204,22.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(204,205,368.30,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(205,206,1835.80,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(206,207,3670.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(207,210,8.32,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(208,211,35.90,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(209,212,70.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(210,213,172.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(211,214,2.50,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(212,215,4.53,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(213,216,6.16,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(214,217,25.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(215,218,48.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(216,219,118.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(217,220,2.90,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(218,221,5.41,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(219,222,9.02,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(220,223,39.40,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(221,224,77.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(222,225,189.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(223,228,6.78,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(224,229,28.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(225,230,54.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(226,231,133.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(227,234,11.28,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(228,235,50.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(229,236,99.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(230,237,246.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(231,238,5.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(232,239,11.11,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(233,240,32.96,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(234,241,159.08,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(235,242,788.27,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(236,243,316.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(237,244,2.85,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(238,245,5.15,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(239,246,6.65,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(240,247,27.55,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(241,248,53.54,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(242,249,130.63,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(243,250,3.14,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(244,251,5.07,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(245,252,8.56,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(246,253,37.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(247,254,72.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(248,255,178.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(249,256,2.66,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(250,257,4.72,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(251,258,8.74,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(252,259,38.00,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(253,260,74.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(254,261,182.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(255,262,45.82,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(256,263,223.40,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(257,264,445.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(258,265,1109.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(259,266,2.74,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(260,267,4.88,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(261,268,9.46,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(262,269,41.60,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(263,270,81.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(264,271,200.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(265,272,65.80,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(266,273,323.30,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(267,274,645.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(268,275,1609.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(269,276,60.46,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(270,277,296.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(271,278,591.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(272,279,1475.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(273,280,19.34,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(274,281,81.26,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(275,282,400.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:16','2025-06-24 16:05:16'),(276,283,799.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(277,285,83.68,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(278,286,412.70,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(279,287,823.84,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(280,288,2056.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(281,289,121.46,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(282,290,601.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(283,291,1201.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(284,293,2.55,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(285,294,4.49,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(286,295,7.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(287,296,32.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(288,297,58.62,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(289,298,156.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(290,299,7.67,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(291,300,83.76,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(292,301,413.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(293,302,8.12,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(294,303,89.54,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(295,304,442.00,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(296,305,882.44,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(297,306,25.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(298,307,6.16,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(299,308,118.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(300,309,48.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(301,310,11.25,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(302,311,101.76,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(303,312,503.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(304,313,1004.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(305,316,913.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(306,317,2.51,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(307,318,3.99,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(308,319,4.38,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(309,320,16.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(310,321,30.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(311,322,73.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(312,323,2.41,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(313,324,3.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(314,325,3.76,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(315,326,13.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(316,327,24.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(317,328,58.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(318,329,3.85,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(319,330,3.92,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(320,331,13.90,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(321,332,26.24,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(322,333,62.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(323,334,2.42,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(324,335,3.83,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(325,336,3.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(326,337,13.50,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(327,338,25.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(328,339,60.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(329,342,3.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(330,343,13.30,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(331,344,25.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(332,345,59.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(333,346,5.03,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(334,347,7.78,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(335,348,33.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(336,349,64.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(337,350,158.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(338,351,3.42,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(339,352,5.57,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(340,353,12.56,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(341,354,57.10,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(342,355,112.64,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(343,356,278.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(344,357,7.32,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(345,358,12.67,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(346,359,44.52,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(347,360,216.90,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(348,361,432.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(349,362,1077.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(350,363,4.59,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(351,364,7.71,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(352,365,22.18,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(353,366,105.20,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(354,367,208.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(355,368,518.87,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(356,369,4.05,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(357,370,6.72,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(358,371,17.74,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(359,372,83.00,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(360,373,164.44,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(361,374,407.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(362,375,4.91,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(363,376,9.11,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(364,377,24.75,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(365,378,118.05,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(366,379,234.54,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(367,380,583.12,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(368,381,49.75,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(369,382,550.88,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(370,383,2748.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(371,384,5495.84,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(372,385,3.13,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(373,386,6.19,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(374,387,12.79,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(375,388,58.27,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(376,389,6.85,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(377,390,11.82,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(378,391,40.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(379,392,197.80,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(380,393,394.04,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(381,394,981.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(382,395,2.93,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(383,396,4.69,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(384,397,10.94,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(385,398,49.00,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(386,399,96.44,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(387,400,3.58,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(388,401,6.55,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(389,402,42.62,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(390,403,207.40,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(391,404,413.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(392,405,49.16,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(393,406,240.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(394,407,478.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(395,408,16.35,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(396,409,111.50,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(397,410,551.80,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(398,411,1102.04,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(399,412,33.46,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(400,413,161.60,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:17','2025-06-24 16:05:17'),(401,414,321.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(402,415,800.87,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(403,416,59.90,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(404,417,672.22,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(405,418,3355.40,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(406,419,6709.24,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(407,420,2.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(408,421,3.77,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(409,422,3.48,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(410,423,11.70,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(411,424,21.84,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(412,425,51.37,'CAD',1,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(413,426,5.56,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(414,427,22.10,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(415,428,42.64,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18'),(416,429,103.37,'CAD',0,'2025-06-03 17:39:12','2025-06-24 16:05:18','2025-06-24 16:05:18');
/*!40000 ALTER TABLE `seed_price_history` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `seed_price_history` with 416 row(s)
--

--
-- Table structure for table `seed_scrape_uploads`
--

DROP TABLE IF EXISTS `seed_scrape_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seed_scrape_uploads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `original_filename` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `failed_entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`failed_entries`)),
  `total_entries` int(11) NOT NULL DEFAULT 0,
  `successful_entries` int(11) NOT NULL DEFAULT 0,
  `failed_entries_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_scrape_uploads`
--

LOCK TABLES `seed_scrape_uploads` WRITE;
/*!40000 ALTER TABLE `seed_scrape_uploads` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `seed_scrape_uploads` (`id`, `original_filename`, `status`, `uploaded_at`, `processed_at`, `notes`, `failed_entries`, `total_entries`, `successful_entries`, `failed_entries_count`, `created_at`, `updated_at`) VALUES (1,'sprouting_com_detailed_20250609_103826.json','processing','2025-06-23 03:02:01',NULL,NULL,NULL,0,0,0,'2025-06-23 03:02:01','2025-06-23 03:02:01'),(2,'sprouting_com_detailed_20250603_103912.json','completed','2025-06-24 16:05:14','2025-06-24 16:05:18','Processed 87/87 products successfully with supplier: Sprouting Seeds.','[]',87,87,0,'2025-06-24 16:05:14','2025-06-24 16:05:18');
/*!40000 ALTER TABLE `seed_scrape_uploads` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `seed_scrape_uploads` with 2 row(s)
--

--
-- Table structure for table `seed_variations`
--

DROP TABLE IF EXISTS `seed_variations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `seed_variations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `seed_entry_id` bigint(20) unsigned NOT NULL,
  `size_description` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `weight_kg` decimal(10,4) DEFAULT NULL,
  `original_weight_value` varchar(255) DEFAULT NULL,
  `original_weight_unit` varchar(255) DEFAULT NULL,
  `current_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'USD',
  `is_in_stock` tinyint(1) NOT NULL DEFAULT 1,
  `last_checked_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `consumable_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seed_variations_seed_entry_id_size_description_unique` (`seed_entry_id`,`size_description`),
  KEY `seed_variations_consumable_id_foreign` (`consumable_id`),
  KEY `seed_variations_last_checked_index` (`last_checked_at`),
  KEY `seed_variations_stock_status_index` (`is_in_stock`,`last_checked_at`),
  KEY `seed_variations_entry_size_index` (`seed_entry_id`,`size_description`),
  CONSTRAINT `seed_variations_consumable_id_foreign` FOREIGN KEY (`consumable_id`) REFERENCES `consumables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `seed_variations_seed_entry_id_foreign` FOREIGN KEY (`seed_entry_id`) REFERENCES `seed_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=430 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seed_variations`
--

LOCK TABLES `seed_variations` WRITE;
/*!40000 ALTER TABLE `seed_variations` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `seed_variations` (`id`, `seed_entry_id`, `size_description`, `sku`, `weight_kg`, `original_weight_value`, `original_weight_unit`, `current_price`, `currency`, `is_in_stock`, `last_checked_at`, `created_at`, `updated_at`, `consumable_id`) VALUES (1,1,'75 grams',NULL,0.0750,'75','g',16.09,'CAD',0,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(2,1,'1 kilogram',NULL,1.0000,'1','kg',231.16,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(3,1,'5 kilograms',NULL,5.0000,'5','kg',1150.10,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(4,2,'125 grams',NULL,0.1250,'125','g',7.12,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(5,2,'250 grams',NULL,0.2500,'250','g',12.30,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(6,2,'1 kilogram',NULL,1.0000,'1','kg',34.60,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(7,2,'5 kilograms',NULL,5.0000,'5','kg',167.30,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(8,2,'10 kilograms',NULL,10.0000,'10','kg',333.64,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(9,2,'25 kilograms',NULL,25.0000,'25','kg',829.37,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(10,3,'125 grams',NULL,0.1250,'125','g',27.54,'CAD',0,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(11,3,'1 kilogram',NULL,1.0000,'1','kg',163.20,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(12,3,'5 kilograms',NULL,5.0000,'5','kg',810.30,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(13,4,'100 grams',NULL,0.1000,'100','g',1.56,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(14,4,'1 kilogram',NULL,1.0000,'1','kg',2.86,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(15,4,'5 kilograms',NULL,5.0000,'5','kg',9.87,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(16,4,'10 kilograms',NULL,10.0000,'10','kg',15.76,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(17,4,'25 kilograms',NULL,25.0000,'25','kg',35.14,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(18,5,'100 grams',NULL,0.1000,'100','g',16.35,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(19,5,'1 kilogram',NULL,1.0000,'1','kg',118.50,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(20,5,'5 kilograms',NULL,5.0000,'5','kg',586.80,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(21,5,'10 kilograms',NULL,10.0000,'10','kg',1172.04,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(22,6,'100 grams',NULL,0.1000,'100','g',13.24,'CAD',0,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(23,6,'1 kilogram',NULL,1.0000,'1','kg',93.02,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(24,6,'5 kilograms',NULL,5.0000,'5','kg',459.40,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(25,6,'10 kilograms',NULL,10.0000,'10','kg',917.24,'CAD',1,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(26,7,'1 kilogram',NULL,1.0000,'1','kg',147.86,'CAD',0,'2025-06-24 16:05:14','2025-06-24 16:05:14','2025-06-24 16:05:14',NULL),(27,7,'5 kilograms',NULL,5.0000,'5','kg',733.60,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(28,7,'10 kilograms',NULL,10.0000,'10','kg',1465.64,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(29,8,'30 grams',NULL,0.0300,'30','g',3.50,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(30,8,'100 grams',NULL,0.1000,'100','g',NULL,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(31,8,'1 kilogram',NULL,1.0000,'1','kg',40.42,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(32,8,'5 kilograms',NULL,5.0000,'5','kg',196.40,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(33,8,'10 kilograms',NULL,10.0000,'10','kg',391.97,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(34,9,'1 kilogram',NULL,1.0000,'1','kg',43.78,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(35,9,'5 kilograms',NULL,5.0000,'5','kg',213.20,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(36,9,'10 kilograms',NULL,10.0000,'10','kg',425.57,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(37,10,'30 grams',NULL,0.0300,'30','g',4.99,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(38,10,'100 grams',NULL,0.1000,'100','g',7.02,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(39,10,'1 kilogram',NULL,1.0000,'1','kg',42.34,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(40,10,'5 kilograms',NULL,5.0000,'5','kg',214.47,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(41,10,'10 kilograms',NULL,10.0000,'10','kg',424.84,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(42,11,'100 grams',NULL,0.1000,'100','g',7.01,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(43,11,'200 grams',NULL,0.2000,'200','g',12.11,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(44,11,'1 kilogram',NULL,1.0000,'1','kg',42.00,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(45,11,'5 kilograms',NULL,5.0000,'5','kg',204.30,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(46,11,'10 kilograms',NULL,10.0000,'10','kg',407.04,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(47,11,'25 kilograms',NULL,25.0000,'25','kg',1014.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(48,12,'50 grams',NULL,0.0500,'50','g',11.34,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(49,12,'1 kilogram',NULL,1.0000,'1','kg',37.34,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(50,12,'5 kilograms',NULL,5.0000,'5','kg',181.00,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(51,12,'10 kilograms',NULL,10.0000,'10','kg',360.44,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(52,13,'125 grams',NULL,0.1250,'125','g',3.58,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(53,13,'250 grams',NULL,0.2500,'250','g',7.18,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(54,13,'1 kilogram',NULL,1.0000,'1','kg',13.79,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(55,13,'5 kilograms',NULL,5.0000,'5','kg',63.25,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(56,13,'10 kilograms',NULL,10.0000,'10','kg',124.94,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(57,14,'100 grams',NULL,0.1000,'100','g',6.33,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(58,14,'200 grams',NULL,0.2000,'200','g',10.87,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(59,14,'1 kilogram',NULL,1.0000,'1','kg',36.41,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(60,14,'5 kilograms',NULL,5.0000,'5','kg',176.34,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(61,14,'10 kilograms',NULL,10.0000,'10','kg',351.12,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(62,14,'25 kilograms',NULL,25.0000,'25','kg',874.57,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(63,15,'100 grams',NULL,0.1000,'100','g',4.23,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(64,15,'200 grams',NULL,0.2000,'200','g',7.05,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(65,15,'1 kilogram',NULL,1.0000,'1','kg',19.20,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(66,15,'5 kilograms',NULL,5.0000,'5','kg',90.30,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(67,15,'10 kilograms',NULL,10.0000,'10','kg',179.04,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(68,15,'25 kilograms',NULL,25.0000,'25','kg',444.37,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(69,16,'100 grams',NULL,0.1000,'100','g',2.84,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(70,16,'250 grams',NULL,0.2500,'250','g',4.86,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(71,16,'1 kilogram',NULL,1.0000,'1','kg',7.80,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(72,16,'5 kilograms',NULL,5.0000,'5','kg',33.30,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(73,16,'10 kilograms',NULL,10.0000,'10','kg',65.04,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(74,16,'25 kilograms',NULL,25.0000,'25','kg',159.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(75,17,'100 grams',NULL,0.1000,'100','g',9.22,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(76,17,'200 grams',NULL,0.2000,'200','g',16.13,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(77,17,'1 kilogram',NULL,1.0000,'1','kg',60.10,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(78,17,'5 kilograms',NULL,5.0000,'5','kg',294.80,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(79,17,'10 kilograms',NULL,10.0000,'10','kg',588.04,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(80,17,'25 kilograms',NULL,25.0000,'25','kg',1466.87,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(81,18,'50 grams',NULL,0.0500,'50','g',9.29,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(82,18,'1 kilogram',NULL,1.0000,'1','kg',119.78,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(83,18,'5 kilograms',NULL,5.0000,'5','kg',593.20,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(84,18,'10 kilograms',NULL,10.0000,'10','kg',1184.84,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(85,19,'50 grams',NULL,0.0500,'50','g',11.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(86,19,'1 kilogram',NULL,1.0000,'1','kg',152.92,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(87,19,'5 kilograms',NULL,5.0000,'5','kg',758.90,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(88,19,'10 kilograms',NULL,10.0000,'10','kg',1516.24,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(89,20,'75 grams',NULL,0.0750,'75','g',16.20,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(90,20,'1 kilogram',NULL,1.0000,'1','kg',155.78,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(91,20,'5 kilograms',NULL,5.0000,'5','kg',773.20,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(92,20,'10 kilograms',NULL,10.0000,'10','kg',1544.84,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(93,21,'200 grams',NULL,0.2000,'200','g',5.55,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(94,21,'1 kilogram',NULL,1.0000,'1','kg',12.44,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(95,21,'5 kilograms',NULL,5.0000,'5','kg',56.50,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(96,21,'10 kilograms',NULL,10.0000,'10','kg',111.14,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(97,21,'25 kilograms',NULL,25.0000,'25','kg',275.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(98,22,'50 grams',NULL,0.0500,'50','g',5.07,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(99,22,'1 kilogram',NULL,1.0000,'1','kg',51.80,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(100,22,'5 kilograms',NULL,5.0000,'5','kg',247.30,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(101,22,'10 kilograms',NULL,10.0000,'10','kg',493.04,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(102,22,'25 kilograms',NULL,25.0000,'25','kg',1101.08,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(103,23,'125 grams',NULL,0.1250,'125','g',5.23,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(104,23,'250 grams',NULL,0.2500,'250','g',8.87,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(105,23,'1 kilogram',NULL,1.0000,'1','kg',22.24,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(106,23,'5 kilograms',NULL,5.0000,'5','kg',105.50,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(107,23,'10 kilograms',NULL,10.0000,'10','kg',209.44,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(108,23,'25 kilograms',NULL,25.0000,'25','kg',520.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(109,24,'125 grams',NULL,0.1250,'125','g',4.55,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(110,24,'250 grams',NULL,0.2500,'250','g',7.62,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(111,24,'1 kilogram',NULL,1.0000,'1','kg',17.74,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(112,24,'5 kilograms',NULL,5.0000,'5','kg',83.00,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(113,24,'10 kilograms',NULL,10.0000,'10','kg',164.44,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(114,24,'25 kilograms',NULL,25.0000,'25','kg',407.87,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(115,25,'75 grams',NULL,0.0750,'75','g',7.94,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(116,25,'1 kilogram',NULL,1.0000,'1','kg',65.56,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(117,25,'5 kilograms',NULL,5.0000,'5','kg',322.10,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(118,25,'10 kilograms',NULL,10.0000,'10','kg',642.64,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(119,25,'25 kilograms',NULL,25.0000,'25','kg',1603.37,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(120,26,'100 grams',NULL,0.1000,'100','g',4.04,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(121,26,'30 grams',NULL,0.0300,'30','g',2.38,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(122,26,'1 kilogram',NULL,1.0000,'1','kg',9.74,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(123,26,'5 kilograms',NULL,5.0000,'5','kg',43.00,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(124,26,'10 kilograms',NULL,10.0000,'10','kg',84.44,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(125,27,'30 grams',NULL,0.0300,'30','g',5.59,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(126,27,'1 kilogram',NULL,1.0000,'1','kg',109.96,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(127,27,'5 kilograms',NULL,5.0000,'5','kg',544.10,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(128,27,'10 kilograms',NULL,10.0000,'10','kg',1086.64,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(129,28,'250 grams',NULL,0.2500,'250','g',5.19,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(130,28,'1 kilogram',NULL,1.0000,'1','kg',8.98,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(131,28,'5 kilograms',NULL,5.0000,'5','kg',39.20,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(132,28,'10 kilograms',NULL,10.0000,'10','kg',76.84,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(133,28,'25 kilograms',NULL,25.0000,'25','kg',188.87,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(134,29,'125 grams',NULL,0.1250,'125','g',5.05,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(135,29,'250 grams',NULL,0.2500,'250','g',8.54,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(136,29,'1 kilogram',NULL,1.0000,'1','kg',21.06,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(137,29,'5 kilograms',NULL,5.0000,'5','kg',99.60,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(138,29,'10 kilograms',NULL,10.0000,'10','kg',197.64,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(139,29,'25 kilograms',NULL,25.0000,'25','kg',490.87,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(140,30,'50 grams',NULL,0.0500,'50','g',5.49,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(141,30,'100 grams',NULL,0.1000,'100','g',9.34,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(142,30,'1 kilogram',NULL,1.0000,'1','kg',57.46,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(143,30,'5 kilograms',NULL,5.0000,'5','kg',281.60,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(144,30,'10 kilograms',NULL,10.0000,'10','kg',561.64,'CAD',0,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(145,31,'50 grams',NULL,0.0500,'50','g',8.84,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(146,31,'1 kilogram',NULL,1.0000,'1','kg',109.96,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(147,31,'5 kilograms',NULL,5.0000,'5','kg',544.10,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(148,31,'10 kilograms',NULL,10.0000,'10','kg',1086.64,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(149,32,'300 grams',NULL,0.3000,'300','g',4.66,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(150,32,'1 kilogram',NULL,1.0000,'1','kg',6.16,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(151,32,'5 kilograms',NULL,5.0000,'5','kg',25.10,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(152,32,'10 kilograms',NULL,10.0000,'10','kg',48.64,'CAD',1,'2025-06-24 16:05:15','2025-06-24 16:05:15','2025-06-24 16:05:15',NULL),(153,32,'25 kilograms',NULL,25.0000,'25','kg',118.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(154,33,'50 grams',NULL,0.0500,'50','g',6.94,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(155,33,'1 kilogram',NULL,1.0000,'1','kg',81.28,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(156,33,'5 kilograms',NULL,5.0000,'5','kg',400.70,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(157,33,'10 kilograms',NULL,10.0000,'10','kg',799.84,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(158,34,'125 grams',NULL,0.1250,'125','g',3.57,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(159,34,'250 grams',NULL,0.2500,'250','g',5.85,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(160,34,'1 kilogram',NULL,1.0000,'1','kg',11.36,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(161,34,'5 kilograms',NULL,5.0000,'5','kg',49.20,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(162,34,'10 kilograms',NULL,10.0000,'10','kg',100.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(163,34,'25 kilograms',NULL,25.0000,'25','kg',248.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(164,35,'75 grams',NULL,0.0750,'75','g',12.32,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(165,35,'1 kilogram',NULL,1.0000,'1','kg',103.16,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(166,35,'5 kilograms',NULL,5.0000,'5','kg',504.10,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(167,35,'10 kilograms',NULL,10.0000,'10','kg',1006.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(168,36,'100 grams',NULL,0.1000,'100','g',9.94,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(169,36,'200 grams',NULL,0.2000,'200','g',17.43,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(170,36,'1 kilogram',NULL,1.0000,'1','kg',65.96,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(171,36,'5 kilograms',NULL,5.0000,'5','kg',324.10,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(172,36,'10 kilograms',NULL,10.0000,'10','kg',646.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(173,36,'25 kilograms',NULL,25.0000,'25','kg',1613.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(174,37,'100 grams',NULL,0.1000,'100','g',8.95,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(175,37,'1 kilogram',NULL,1.0000,'1','kg',57.86,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(176,37,'5 kilograms',NULL,5.0000,'5','kg',283.60,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(177,37,'10 kilograms',NULL,10.0000,'10','kg',565.64,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(178,37,'25 kilograms',NULL,25.0000,'25','kg',1410.87,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(179,38,'125 grams',NULL,0.1250,'125','g',2.69,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(180,38,'300 grams',NULL,0.3000,'300','g',4.46,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(181,38,'1 kilogram',NULL,1.0000,'1','kg',5.56,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(182,38,'5 kilograms',NULL,5.0000,'5','kg',22.10,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(183,38,'10 kilograms',NULL,10.0000,'10','kg',42.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(184,38,'25 kilograms',NULL,25.0000,'25','kg',103.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(185,39,'100 grams',NULL,0.1000,'100','g',8.71,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(186,39,'200 grams',NULL,0.2000,'200','g',15.19,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(187,39,'1 kilogram',NULL,1.0000,'1','kg',55.90,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(188,39,'5 kilograms',NULL,5.0000,'5','kg',273.80,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(189,39,'10 kilograms',NULL,10.0000,'10','kg',546.04,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(190,39,'25 kilograms',NULL,25.0000,'25','kg',1361.87,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(191,40,'75 grams',NULL,0.0750,'75','g',7.96,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(192,40,'1 kilogram',NULL,1.0000,'1','kg',72.02,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(193,40,'5 kilograms',NULL,5.0000,'5','kg',354.40,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(194,40,'10 kilograms',NULL,10.0000,'10','kg',707.24,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(195,40,'25 kilograms',NULL,25.0000,'25','kg',1764.87,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(196,41,'75 grams',NULL,0.0750,'75','g',8.27,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(197,41,'1 kilogram',NULL,1.0000,'1','kg',72.02,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(198,41,'5 kilograms',NULL,5.0000,'5','kg',354.40,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(199,41,'10 kilograms',NULL,10.0000,'10','kg',603.35,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(200,42,'75 grams',NULL,0.0750,'75','g',11.72,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(201,42,'1 kilogram',NULL,1.0000,'1','kg',97.30,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(202,42,'5 kilograms',NULL,5.0000,'5','kg',480.80,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(203,42,'10 kilograms',NULL,10.0000,'10','kg',960.04,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(204,43,'50 grams',NULL,0.0500,'50','g',22.24,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(205,43,'1 kilogram',NULL,1.0000,'1','kg',368.30,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(206,43,'5 kilograms',NULL,5.0000,'5','kg',1835.80,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(207,43,'10 kilograms',NULL,10.0000,'10','kg',3670.04,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(208,44,'100 grams',NULL,0.1000,'100','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(209,44,'200 grams',NULL,0.2000,'200','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(210,44,'1 kilogram',NULL,1.0000,'1','kg',8.32,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(211,44,'5 kilograms',NULL,5.0000,'5','kg',35.90,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(212,44,'10 kilograms',NULL,10.0000,'10','kg',70.24,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(213,44,'25 kilograms',NULL,25.0000,'25','kg',172.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(214,45,'125 grams',NULL,0.1250,'125','g',2.50,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(215,45,'275 grams',NULL,0.2750,'275','g',4.53,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(216,45,'1 kilogram',NULL,1.0000,'1','kg',6.16,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(217,45,'5 kilograms',NULL,5.0000,'5','kg',25.10,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(218,45,'10 kilograms',NULL,10.0000,'10','kg',48.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(219,45,'25 kilograms',NULL,25.0000,'25','kg',118.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(220,46,'125 grams',NULL,0.1250,'125','g',2.90,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(221,46,'275 grams',NULL,0.2750,'275','g',5.41,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(222,46,'1 kilogram',NULL,1.0000,'1','kg',9.02,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(223,46,'5 kilograms',NULL,5.0000,'5','kg',39.40,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(224,46,'10 kilograms',NULL,10.0000,'10','kg',77.24,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(225,46,'25 kilograms',NULL,25.0000,'25','kg',189.87,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(226,47,'100 grams',NULL,0.1000,'100','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(227,47,'200 grams',NULL,0.2000,'200','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(228,47,'1 kilogram',NULL,1.0000,'1','kg',6.78,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(229,47,'5 kilograms',NULL,5.0000,'5','kg',28.20,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(230,47,'10 kilograms',NULL,10.0000,'10','kg',54.84,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(231,47,'25 kilograms',NULL,25.0000,'25','kg',133.87,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(232,48,'100 grams',NULL,0.1000,'100','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(233,48,'200 grams',NULL,0.2000,'200','g',NULL,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(234,48,'1 kilogram',NULL,1.0000,'1','kg',11.28,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(235,48,'5 kilograms',NULL,5.0000,'5','kg',50.70,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(236,48,'10 kilograms',NULL,10.0000,'10','kg',99.84,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(237,48,'25 kilograms',NULL,25.0000,'25','kg',246.37,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(238,49,'100 grams',NULL,0.1000,'100','g',5.37,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(239,49,'200 grams',NULL,0.2000,'200','g',11.11,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(240,49,'1 kilogram',NULL,1.0000,'1','kg',32.96,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(241,49,'5 kilograms',NULL,5.0000,'5','kg',159.08,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(242,49,'25 kilograms',NULL,25.0000,'25','kg',788.27,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(243,49,'10 kilograms',NULL,10.0000,'10','kg',316.60,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(244,50,'125 grams',NULL,0.1250,'125','g',2.85,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(245,50,'275 grams',NULL,0.2750,'275','g',5.15,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(246,50,'1 kilogram',NULL,1.0000,'1','kg',6.65,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(247,50,'5 kilograms',NULL,5.0000,'5','kg',27.55,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(248,50,'10 kilograms',NULL,10.0000,'10','kg',53.54,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(249,50,'25 kilograms',NULL,25.0000,'25','kg',130.63,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(250,51,'125 grams',NULL,0.1250,'125','g',3.14,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(251,51,'250 grams',NULL,0.2500,'250','g',5.07,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(252,51,'1 kilogram',NULL,1.0000,'1','kg',8.56,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(253,51,'5 kilograms',NULL,5.0000,'5','kg',37.10,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(254,51,'10 kilograms',NULL,10.0000,'10','kg',72.64,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(255,51,'25 kilograms',NULL,25.0000,'25','kg',178.37,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(256,52,'100 grams',NULL,0.1000,'100','g',2.66,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(257,52,'200 grams',NULL,0.2000,'200','g',4.72,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(258,52,'1 kilogram',NULL,1.0000,'1','kg',8.74,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(259,52,'5 kilograms',NULL,5.0000,'5','kg',38.00,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(260,52,'10 kilograms',NULL,10.0000,'10','kg',74.44,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(261,52,'25 kilograms',NULL,25.0000,'25','kg',182.87,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(262,53,'1 kilogram',NULL,1.0000,'1','kg',45.82,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(263,53,'5 kilograms',NULL,5.0000,'5','kg',223.40,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(264,53,'10 kilograms',NULL,10.0000,'10','kg',445.24,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(265,53,'25 kilograms',NULL,25.0000,'25','kg',1109.87,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(266,54,'100 grams',NULL,0.1000,'100','g',2.74,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(267,54,'200 grams',NULL,0.2000,'200','g',4.88,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(268,54,'1 kilogram',NULL,1.0000,'1','kg',9.46,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(269,54,'5 kilograms',NULL,5.0000,'5','kg',41.60,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(270,54,'10 kilograms',NULL,10.0000,'10','kg',81.64,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(271,54,'25 kilograms',NULL,25.0000,'25','kg',200.87,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(272,55,'1 kilogram',NULL,1.0000,'1','kg',65.80,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(273,55,'5 kilograms',NULL,5.0000,'5','kg',323.30,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(274,55,'10 kilograms',NULL,10.0000,'10','kg',645.04,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(275,55,'25 kilograms',NULL,25.0000,'25','kg',1609.37,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(276,56,'1 kilogram',NULL,1.0000,'1','kg',60.46,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(277,56,'5 kilograms',NULL,5.0000,'5','kg',296.60,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(278,56,'10 kilograms',NULL,10.0000,'10','kg',591.64,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(279,56,'25 kilograms',NULL,25.0000,'25','kg',1475.87,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(280,57,'100 grams',NULL,0.1000,'100','g',19.34,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(281,57,'1 kilogram',NULL,1.0000,'1','kg',81.26,'CAD',1,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(282,57,'5 kilograms',NULL,5.0000,'5','kg',400.60,'CAD',0,'2025-06-24 16:05:16','2025-06-24 16:05:16','2025-06-24 16:05:16',NULL),(283,57,'10 kilograms',NULL,10.0000,'10','kg',799.64,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(284,57,'25 kilograms',NULL,25.0000,'25','kg',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(285,58,'1 kilogram',NULL,1.0000,'1','kg',83.68,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(286,58,'5 kilograms',NULL,5.0000,'5','kg',412.70,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(287,58,'10 kilograms',NULL,10.0000,'10','kg',823.84,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(288,58,'25 kilograms',NULL,25.0000,'25','kg',2056.37,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(289,59,'1 kilogram',NULL,1.0000,'1','kg',121.46,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(290,59,'5 kilograms',NULL,5.0000,'5','kg',601.60,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(291,59,'10 kilograms',NULL,10.0000,'10','kg',1201.64,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(292,59,'25 kilograms',NULL,25.0000,'25','kg',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(293,60,'100 grams',NULL,0.1000,'100','g',2.55,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(294,60,'200 grams',NULL,0.2000,'200','g',4.49,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(295,60,'1 kilogram',NULL,1.0000,'1','kg',7.70,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(296,60,'5 kilograms',NULL,5.0000,'5','kg',32.80,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(297,60,'10 kilograms',NULL,10.0000,'10','kg',58.62,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(298,60,'25 kilograms',NULL,25.0000,'25','kg',156.87,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(299,61,'50 grams',NULL,0.0500,'50','g',7.67,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(300,61,'1 kilogram',NULL,1.0000,'1','kg',83.76,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(301,61,'5 kilograms',NULL,5.0000,'5','kg',413.10,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(302,62,'50 grams',NULL,0.0500,'50','g',8.12,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(303,62,'1 kilogram',NULL,1.0000,'1','kg',89.54,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(304,62,'5 kilograms',NULL,5.0000,'5','kg',442.00,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(305,62,'10 kilograms',NULL,10.0000,'10','kg',882.44,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(306,63,'5 kilograms',NULL,5.0000,'5','kg',25.10,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(307,63,'1 kilogram',NULL,1.0000,'1','kg',6.16,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(308,63,'25 kilograms',NULL,25.0000,'25','kg',118.37,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(309,63,'10 kilograms',NULL,10.0000,'10','kg',48.64,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(310,64,'75 grams',NULL,0.0750,'75','g',11.25,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(311,64,'1 kilogram',NULL,1.0000,'1','kg',101.76,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(312,64,'5 kilograms',NULL,5.0000,'5','kg',503.10,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(313,64,'10 kilograms',NULL,10.0000,'10','kg',1004.64,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(314,65,'1 kilogram',NULL,1.0000,'1','kg',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(315,65,'5 kilograms',NULL,5.0000,'5','kg',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(316,65,'10 kilograms',NULL,10.0000,'10','kg',913.64,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(317,66,'125 grams',NULL,0.1250,'125','g',2.51,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(318,66,'275 grams',NULL,0.2750,'275','g',3.99,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(319,66,'1 kilogram',NULL,1.0000,'1','kg',4.38,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(320,66,'5 kilograms',NULL,5.0000,'5','kg',16.20,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(321,66,'10 kilograms',NULL,10.0000,'10','kg',30.84,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(322,66,'25 kilograms',NULL,25.0000,'25','kg',73.87,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(323,67,'125 grams',NULL,0.1250,'125','g',2.41,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(324,67,'275 grams',NULL,0.2750,'275','g',3.80,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(325,67,'1 kilogram',NULL,1.0000,'1','kg',3.76,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(326,67,'5 kilograms',NULL,5.0000,'5','kg',13.10,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(327,67,'10 kilograms',NULL,10.0000,'10','kg',24.64,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(328,67,'25 kilograms',NULL,25.0000,'25','kg',58.37,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(329,68,'275 grams',NULL,0.2750,'275','g',3.85,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(330,68,'1 kilogram',NULL,1.0000,'1','kg',3.92,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(331,68,'5 kilograms',NULL,5.0000,'5','kg',13.90,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(332,68,'10 kilograms',NULL,10.0000,'10','kg',26.24,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(333,68,'25 kilograms',NULL,25.0000,'25','kg',62.37,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(334,69,'125 grams',NULL,0.1250,'125','g',2.42,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(335,69,'275 grams',NULL,0.2750,'275','g',3.83,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(336,69,'1 kilogram',NULL,1.0000,'1','kg',3.84,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(337,69,'5 kilograms',NULL,5.0000,'5','kg',13.50,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(338,69,'10 kilograms',NULL,10.0000,'10','kg',25.44,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(339,69,'25 kilograms',NULL,25.0000,'25','kg',60.37,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(340,70,'125 grams',NULL,0.1250,'125','g',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(341,70,'275 grams',NULL,0.2750,'275','g',NULL,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(342,70,'1 kilogram',NULL,1.0000,'1','kg',3.80,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(343,70,'5 kilograms',NULL,5.0000,'5','kg',13.30,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(344,70,'10 kilograms',NULL,10.0000,'10','kg',25.04,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(345,70,'25 kilograms',NULL,25.0000,'25','kg',59.37,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(346,71,'275 grams',NULL,0.2750,'275','g',5.03,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(347,71,'1 kilogram',NULL,1.0000,'1','kg',7.78,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(348,71,'5 kilograms',NULL,5.0000,'5','kg',33.20,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(349,71,'10 kilograms',NULL,10.0000,'10','kg',64.84,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(350,71,'25 kilograms',NULL,25.0000,'25','kg',158.87,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(351,72,'100 grams',NULL,0.1000,'100','g',3.42,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(352,72,'200 grams',NULL,0.2000,'200','g',5.57,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(353,72,'1 kilogram',NULL,1.0000,'1','kg',12.56,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(354,72,'5 kilograms',NULL,5.0000,'5','kg',57.10,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(355,72,'10 kilograms',NULL,10.0000,'10','kg',112.64,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(356,72,'25 kilograms',NULL,25.0000,'25','kg',278.37,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(357,73,'100 grams',NULL,0.1000,'100','g',7.32,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(358,73,'200 grams',NULL,0.2000,'200','g',12.67,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(359,73,'1 kilogram',NULL,1.0000,'1','kg',44.52,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(360,73,'5 kilograms',NULL,5.0000,'5','kg',216.90,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(361,73,'10 kilograms',NULL,10.0000,'10','kg',432.24,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(362,73,'25 kilograms',NULL,25.0000,'25','kg',1077.37,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(363,74,'100 grams',NULL,0.1000,'100','g',4.59,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(364,74,'200 grams',NULL,0.2000,'200','g',7.71,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(365,74,'1 kilogram',NULL,1.0000,'1','kg',22.18,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(366,74,'5 kilograms',NULL,5.0000,'5','kg',105.20,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(367,74,'10 kilograms',NULL,10.0000,'10','kg',208.84,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(368,74,'25 kilograms',NULL,25.0000,'25','kg',518.87,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(369,75,'100 grams',NULL,0.1000,'100','g',4.05,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(370,75,'200 grams',NULL,0.2000,'200','g',6.72,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(371,75,'1 kilogram',NULL,1.0000,'1','kg',17.74,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(372,75,'5 kilograms',NULL,5.0000,'5','kg',83.00,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(373,75,'10 kilograms',NULL,10.0000,'10','kg',164.44,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(374,75,'25 kilograms',NULL,25.0000,'25','kg',407.87,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(375,76,'100 grams',NULL,0.1000,'100','g',4.91,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(376,76,'200 grams',NULL,0.2000,'200','g',9.11,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(377,76,'1 kilogram',NULL,1.0000,'1','kg',24.75,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(378,76,'5 kilograms',NULL,5.0000,'5','kg',118.05,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(379,76,'10 kilograms',NULL,10.0000,'10','kg',234.54,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(380,76,'25 kilograms',NULL,25.0000,'25','kg',583.12,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(381,77,'75 grams',NULL,0.0750,'75','g',49.75,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(382,77,'1 kilogram',NULL,1.0000,'1','kg',550.88,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(383,77,'5 kilograms',NULL,5.0000,'5','kg',2748.70,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(384,77,'10 kilograms',NULL,10.0000,'10','kg',5495.84,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(385,78,'100 grams',NULL,0.1000,'100','g',3.13,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(386,78,'200 grams',NULL,0.2000,'200','g',6.19,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(387,78,'1 kilogram',NULL,1.0000,'1','kg',12.79,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(388,78,'5 kilograms',NULL,5.0000,'5','kg',58.27,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(389,79,'100 grams',NULL,0.1000,'100','g',6.85,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(390,79,'200 grams',NULL,0.2000,'200','g',11.82,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(391,79,'1 kilogram',NULL,1.0000,'1','kg',40.70,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(392,79,'5 kilograms',NULL,5.0000,'5','kg',197.80,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(393,79,'10 kilograms',NULL,10.0000,'10','kg',394.04,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(394,79,'25 kilograms',NULL,25.0000,'25','kg',981.87,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(395,80,'75 grams',NULL,0.0750,'75','g',2.93,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(396,80,'150 grams',NULL,0.1500,'150','g',4.69,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(397,80,'1 kilogram',NULL,1.0000,'1','kg',10.94,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(398,80,'5 kilograms',NULL,5.0000,'5','kg',49.00,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(399,80,'10 kilograms',NULL,10.0000,'10','kg',96.44,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(400,81,'30 grams',NULL,0.0300,'30','g',3.58,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(401,81,'75 grams',NULL,0.0750,'75','g',6.55,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(402,81,'1 kilogram',NULL,1.0000,'1','kg',42.62,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(403,81,'5 kilograms',NULL,5.0000,'5','kg',207.40,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(404,81,'10 kilograms',NULL,10.0000,'10','kg',413.24,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(405,82,'1 kilogram',NULL,1.0000,'1','kg',49.16,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(406,82,'5 kilograms',NULL,5.0000,'5','kg',240.10,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(407,82,'10 kilograms',NULL,10.0000,'10','kg',478.64,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(408,83,'100 grams',NULL,0.1000,'100','g',16.35,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(409,83,'1 kilogram',NULL,1.0000,'1','kg',111.50,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(410,83,'5 kilograms',NULL,5.0000,'5','kg',551.80,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(411,83,'10 kilograms',NULL,10.0000,'10','kg',1102.04,'CAD',1,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(412,84,'1 kilogram',NULL,1.0000,'1','kg',33.46,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(413,84,'5 kilograms',NULL,5.0000,'5','kg',161.60,'CAD',0,'2025-06-24 16:05:17','2025-06-24 16:05:17','2025-06-24 16:05:17',NULL),(414,84,'10 kilograms',NULL,10.0000,'10','kg',321.64,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(415,84,'25 kilograms',NULL,25.0000,'25','kg',800.87,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(416,85,'50 grams',NULL,0.0500,'50','g',59.90,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(417,85,'1 kilogram',NULL,1.0000,'1','kg',672.22,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(418,85,'5 kilograms',NULL,5.0000,'5','kg',3355.40,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(419,85,'10 kilograms',NULL,10.0000,'10','kg',6709.24,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(420,86,'125 grams',NULL,0.1250,'125','g',2.37,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(421,86,'300 grams',NULL,0.3000,'300','g',3.77,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(422,86,'1 kilogram',NULL,1.0000,'1','kg',3.48,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(423,86,'5 kilograms',NULL,5.0000,'5','kg',11.70,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(424,86,'10 kilograms',NULL,10.0000,'10','kg',21.84,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(425,86,'25 kilograms',NULL,25.0000,'25','kg',51.37,'CAD',1,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(426,87,'1 kilogram',NULL,1.0000,'1','kg',5.56,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(427,87,'5 kilograms',NULL,5.0000,'5','kg',22.10,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(428,87,'10 kilograms',NULL,10.0000,'10','kg',42.64,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL),(429,87,'25 kilograms',NULL,25.0000,'25','kg',103.37,'CAD',0,'2025-06-24 16:05:18','2025-06-24 16:05:18','2025-06-24 16:05:18',NULL);
/*!40000 ALTER TABLE `seed_variations` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `seed_variations` with 429 row(s)
--

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
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
SET autocommit=0;
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES ('qCZ04gSTMsZkwjNwNmU4Ksp4QQiN2OO22iuCRQFE',2,'64.180.19.9','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','YToxMDp7czo2OiJfdG9rZW4iO3M6NDA6Ikd0QjlqS2RuVkd4Vm1ZclM5aDFCTThrNHNSaWttb0NmT3VCbFc2Z3AiO3M6MzoidXJsIjthOjA6e31zOjk6Il9wcmV2aW91cyI7YToxOntzOjM6InVybCI7czo1NDoiaHR0cHM6Ly9jYXRhcHVsdC5yb2d1ZXNweS5jby9hZG1pbi9kYXRhYmFzZS1tYW5hZ2VtZW50Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjE0OiJqdXN0X2xvZ2dlZF9pbiI7YjoxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkNi4vZUdjR3JxalRFQnlVWG9RVUxkdXJ1aWNjZWRDRHFOOTJiOXVZemIxNndRVTI1SzB4b2kiO3M6MTM6ImRhc2hib2FyZF90YWIiO3M6MTA6Im9wZXJhdGlvbnMiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fXM6NjoidGFibGVzIjthOjQ6e3M6NDA6IjExZDczODg0YTJjODc1NDU4MTZlYzljNGRiYTMzMmQzX2ZpbHRlcnMiO2E6Mzp7czo4OiJsb2dfbmFtZSI7YToxOntzOjU6InZhbHVlIjtOO31zOjU6ImV2ZW50IjthOjE6e3M6NToidmFsdWUiO047fXM6MTA6ImNyZWF0ZWRfYXQiO2E6Mjp7czoxMjoiY3JlYXRlZF9mcm9tIjtOO3M6MTM6ImNyZWF0ZWRfdW50aWwiO047fX1zOjM5OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19zZWFyY2giO3M6MDoiIjtzOjQ2OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19jb2x1bW5fc2VhcmNoIjthOjA6e31zOjM3OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19zb3J0IjthOjI6e3M6NjoiY29sdW1uIjtOO3M6OToiZGlyZWN0aW9uIjtOO319fQ==',1750951100),('SLP8HU7gm6JeYWzGBAi34cKPLBfOMe5oKOeFu8Iu',2,'64.180.19.9','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','YTo5OntzOjY6Il90b2tlbiI7czo0MDoiVEgyVmxVS0tLVzV3eGxraXFDTUNDSHJ2dHZkOEVYV2E5WEJBMTFrZSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ4OiJodHRwczovL2NhdGFwdWx0LnJvZ3Vlc3B5LmNvL2FkbWluL2FjdGl2aXRpZXMvOTIiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTQ6Imp1c3RfbG9nZ2VkX2luIjtiOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiQ2Li9lR2NHcnFqVEVCeVVYb1FVTGR1cnVpY2NlZENEcU45MmI5dVl6YjE2d1FVMjVLMHhvaSI7czoxMzoiZGFzaGJvYXJkX3RhYiI7czoxMDoib3BlcmF0aW9ucyI7czo2OiJ0YWJsZXMiO2E6MTI6e3M6NDA6IjgwYzdmOGIxYThhZjc1YTZlOTY4NWFjNjc2ZTk1MDczX2ZpbHRlcnMiO2E6Mjp7czoxMzoiY3VycmVudF9zdGFnZSI7YToxOntzOjU6InZhbHVlIjtOO31zOjEyOiJhY3RpdmVfY3JvcHMiO2E6MTp7czo1OiJ2YWx1ZSI7aToxO319czozOToiODBjN2Y4YjFhOGFmNzVhNmU5Njg1YWM2NzZlOTUwNzNfc2VhcmNoIjtzOjA6IiI7czo0NjoiODBjN2Y4YjFhOGFmNzVhNmU5Njg1YWM2NzZlOTUwNzNfY29sdW1uX3NlYXJjaCI7YTowOnt9czozNzoiODBjN2Y4YjFhOGFmNzVhNmU5Njg1YWM2NzZlOTUwNzNfc29ydCI7YToyOntzOjY6ImNvbHVtbiI7TjtzOjk6ImRpcmVjdGlvbiI7Tjt9czo0MDoiZjMzNjE3NWZkYzc5NGVhZTNiOGExY2M5ZTUzNjc3YjBfZmlsdGVycyI7YToxOntzOjEyOiJ0YXJnZXRfc3RhZ2UiO2E6MTp7czo1OiJ2YWx1ZSI7Tjt9fXM6Mzk6ImYzMzYxNzVmZGM3OTRlYWUzYjhhMWNjOWU1MzY3N2IwX3NlYXJjaCI7czowOiIiO3M6NDY6ImYzMzYxNzVmZGM3OTRlYWUzYjhhMWNjOWU1MzY3N2IwX2NvbHVtbl9zZWFyY2giO2E6MDp7fXM6Mzc6ImYzMzYxNzVmZGM3OTRlYWUzYjhhMWNjOWU1MzY3N2IwX3NvcnQiO2E6Mjp7czo2OiJjb2x1bW4iO047czo5OiJkaXJlY3Rpb24iO047fXM6NDA6IjExZDczODg0YTJjODc1NDU4MTZlYzljNGRiYTMzMmQzX2ZpbHRlcnMiO2E6Mzp7czo4OiJsb2dfbmFtZSI7YToxOntzOjU6InZhbHVlIjtOO31zOjU6ImV2ZW50IjthOjE6e3M6NToidmFsdWUiO047fXM6MTA6ImNyZWF0ZWRfYXQiO2E6Mjp7czoxMjoiY3JlYXRlZF9mcm9tIjtOO3M6MTM6ImNyZWF0ZWRfdW50aWwiO047fX1zOjM5OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19zZWFyY2giO3M6MDoiIjtzOjQ2OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19jb2x1bW5fc2VhcmNoIjthOjA6e31zOjM3OiIxMWQ3Mzg4NGEyYzg3NTQ1ODE2ZWM5YzRkYmEzMzJkM19zb3J0IjthOjI6e3M6NjoiY29sdW1uIjtOO3M6OToiZGlyZWN0aW9uIjtOO319fQ==',1750902632);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `sessions` with 2 row(s)
--

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `type` enum('text','number','boolean','json','date') NOT NULL DEFAULT 'text',
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
SET autocommit=0;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `settings` with 0 row(s)
--

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('soil','seed','consumable','other','packaging') NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `suppliers` (`id`, `name`, `type`, `contact_name`, `contact_email`, `contact_phone`, `address`, `notes`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Mumm\'s Sprouting Seeds','seed',NULL,NULL,NULL,NULL,NULL,1,'2025-06-23 03:01:59','2025-06-24 18:01:46'),(2,'Germina Seeds','seed',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 17:51:32','2025-06-24 18:01:28'),(3,'Britelands','packaging',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:01:03','2025-06-24 18:06:10'),(4,'William Dam Seeds','seed',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:01:09','2025-06-24 18:01:09'),(5,'Ecoline','soil',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:06:28','2025-06-24 18:06:28'),(6,'Buckerfields','soil',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:06:47','2025-06-24 18:07:02'),(7,'Johnny\'s Seeds','seed',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:07:22','2025-06-24 18:07:22'),(8,'True Leaf Market','seed',NULL,NULL,NULL,NULL,NULL,1,'2025-06-24 18:07:43','2025-06-24 18:07:43');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `suppliers` with 8 row(s)
--

--
-- Table structure for table `supplier_source_mappings`
--

DROP TABLE IF EXISTS `supplier_source_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_source_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source_url` varchar(500) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `supplier_id` bigint(20) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_source_mappings_domain_supplier_id_unique` (`domain`,`supplier_id`),
  KEY `supplier_source_mappings_supplier_id_foreign` (`supplier_id`),
  KEY `supplier_source_mappings_domain_index` (`domain`),
  KEY `supplier_source_mappings_source_url_index` (`source_url`),
  CONSTRAINT `supplier_source_mappings_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_source_mappings`
--

LOCK TABLES `supplier_source_mappings` WRITE;
/*!40000 ALTER TABLE `supplier_source_mappings` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `supplier_source_mappings` (`id`, `source_url`, `domain`, `supplier_id`, `is_active`, `metadata`, `created_at`, `updated_at`) VALUES (1,'https://sprouting.com','sprouting.com',1,1,'{\"created_at\": \"2025-06-24T16:05:14.423482Z\", \"import_file\": \"sprouting_com_detailed_20250603_103912.json\", \"import_method\": \"pre_selected\"}','2025-06-23 03:02:01','2025-06-24 16:05:14');
/*!40000 ALTER TABLE `supplier_source_mappings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `supplier_source_mappings` with 1 row(s)
--

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `crop_id` bigint(20) unsigned DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `priority` varchar(255) NOT NULL DEFAULT 'medium',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_crop_id_foreign` (`crop_id`),
  KEY `tasks_assigned_to_foreign` (`assigned_to`),
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_crop_id_foreign` FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
SET autocommit=0;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `tasks` with 0 row(s)
--

--
-- Table structure for table `task_schedules`
--

DROP TABLE IF EXISTS `task_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(255) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `frequency` varchar(255) NOT NULL,
  `time_of_day` time DEFAULT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `day_of_month` int(11) DEFAULT NULL,
  `conditions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conditions`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_schedules`
--

LOCK TABLES `task_schedules` WRITE;
/*!40000 ALTER TABLE `task_schedules` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `task_schedules` (`id`, `resource_type`, `task_name`, `frequency`, `time_of_day`, `day_of_week`, `day_of_month`, `conditions`, `is_active`, `last_run_at`, `next_run_at`, `created_at`, `updated_at`) VALUES (1,'crops','advance_to_blackout','once',NULL,NULL,NULL,'{\"crop_id\": 1, \"variety\": \"SUNFLOWER - BLACK OIL - SF4K - 100G\", \"tray_list\": \"11, 12\", \"tray_count\": 2, \"target_stage\": \"blackout\", \"tray_numbers\": [\"11\", \"12\"], \"batch_identifier\": \"1_2025-06-24_germination\"}',1,NULL,'2025-06-27 17:00:59','2025-06-24 17:01:12','2025-06-24 17:01:12'),(2,'crops','advance_to_harvested','once',NULL,NULL,NULL,'{\"crop_id\": 1, \"variety\": \"SUNFLOWER - BLACK OIL - SF4K - 100G\", \"tray_list\": \"11, 12\", \"tray_count\": 2, \"target_stage\": \"harvested\", \"tray_numbers\": [\"11\", \"12\"], \"batch_identifier\": \"1_2025-06-24_germination\"}',1,NULL,'2025-07-03 17:00:59','2025-06-24 17:01:12','2025-06-24 17:01:12'),(3,'crops','suspend_watering','once',NULL,NULL,NULL,'{\"crop_id\": 1}',1,NULL,'2025-07-02 17:00:59','2025-06-24 17:01:12','2025-06-24 17:01:12'),(4,'crops','advance_to_light','once',NULL,NULL,NULL,'{\"crop_id\": 3, \"variety\": \"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\", \"tray_list\": \"13, 14, 15, 16, 17, 18, 19, 20\", \"tray_count\": 8, \"target_stage\": \"light\", \"tray_numbers\": [\"13\", \"14\", \"15\", \"16\", \"17\", \"18\", \"19\", \"20\"], \"batch_identifier\": \"2_2025-06-24_germination\"}',1,NULL,'2025-06-27 17:56:42','2025-06-24 17:57:05','2025-06-24 17:57:05'),(5,'crops','advance_to_harvested','once',NULL,NULL,NULL,'{\"crop_id\": 3, \"variety\": \"SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS\", \"tray_list\": \"13, 14, 15, 16, 17, 18, 19, 20\", \"tray_count\": 8, \"target_stage\": \"harvested\", \"tray_numbers\": [\"13\", \"14\", \"15\", \"16\", \"17\", \"18\", \"19\", \"20\"], \"batch_identifier\": \"2_2025-06-24_germination\"}',1,NULL,'2025-07-03 17:56:42','2025-06-24 17:57:05','2025-06-24 17:57:05'),(6,'crops','suspend_watering','once',NULL,NULL,NULL,'{\"crop_id\": 3}',1,NULL,'2025-07-02 17:56:42','2025-06-24 17:57:05','2025-06-24 17:57:05'),(7,'crops','advance_to_light','once',NULL,NULL,NULL,'{\"crop_id\": 11, \"variety\": \"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\", \"tray_list\": \"21, 22\", \"tray_count\": 2, \"target_stage\": \"light\", \"tray_numbers\": [\"21\", \"22\"], \"batch_identifier\": \"3_2025-06-24_germination\"}',1,NULL,'2025-06-28 21:54:53','2025-06-24 21:55:14','2025-06-24 21:55:14'),(8,'crops','advance_to_harvested','once',NULL,NULL,NULL,'{\"crop_id\": 11, \"variety\": \"BASIL (GENOVESE) - BAS8Y - 5G -21 DAY\", \"tray_list\": \"21, 22\", \"tray_count\": 2, \"target_stage\": \"harvested\", \"tray_numbers\": [\"21\", \"22\"], \"batch_identifier\": \"3_2025-06-24_germination\"}',1,NULL,'2025-07-15 21:54:53','2025-06-24 21:55:15','2025-06-24 21:55:15');
/*!40000 ALTER TABLE `task_schedules` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `task_schedules` with 8 row(s)
--

--
-- Table structure for table `task_types`
--

DROP TABLE IF EXISTS `task_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_types_category_index` (`category`),
  KEY `task_types_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_types`
--

LOCK TABLES `task_types` WRITE;
/*!40000 ALTER TABLE `task_types` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `task_types` (`id`, `name`, `category`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES (1,'Planting Seeds','growing',1,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(2,'Watering','growing',2,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(3,'Harvesting','growing',3,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(4,'Washing Trays','growing',4,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(5,'Making Trays','growing',5,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(6,'Making Soil','growing',6,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(7,'Seed Soaking','growing',7,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(8,'Transplanting','growing',8,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(9,'Quality Control','growing',9,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(10,'Cleaning Growing Area','maintenance',1,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(11,'Equipment Maintenance','maintenance',2,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(12,'Organizing Supplies','maintenance',3,1,'2025-06-24 22:11:08','2025-06-24 22:11:08'),(13,'Sanitizing Equipment','maintenance',4,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(14,'Waste Management','maintenance',5,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(15,'Temperature Control','maintenance',6,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(16,'Humidity Control','maintenance',7,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(17,'Computer Work','administrative',1,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(18,'Bookkeeping','administrative',2,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(19,'Invoicing','administrative',3,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(20,'Customer Communication','administrative',4,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(21,'Inventory Management','administrative',5,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(22,'Order Processing','administrative',6,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(23,'Data Entry','administrative',7,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(24,'Planning & Scheduling','administrative',8,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(25,'Packaging Products','packaging',1,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(26,'Labeling','packaging',2,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(27,'Delivery Preparation','packaging',3,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(28,'Delivery','packaging',4,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(29,'Loading/Unloading','packaging',5,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(30,'Team Meeting','other',1,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(31,'Training','other',2,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(32,'Research & Development','other',3,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(33,'Supplier Communication','other',4,1,'2025-06-24 22:11:09','2025-06-24 22:11:09'),(34,'Break Time','other',5,1,'2025-06-24 22:11:09','2025-06-24 22:11:09');
/*!40000 ALTER TABLE `task_types` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `task_types` with 34 row(s)
--

--
-- Table structure for table `time_cards`
--

DROP TABLE IF EXISTS `time_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_cards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `clock_in` datetime NOT NULL,
  `clock_out` datetime DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `work_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `max_shift_exceeded` tinyint(1) NOT NULL DEFAULT 0,
  `max_shift_exceeded_at` datetime DEFAULT NULL,
  `requires_review` tinyint(1) NOT NULL DEFAULT 0,
  `flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`flags`)),
  `review_notes` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_cards_user_id_work_date_index` (`user_id`,`work_date`),
  KEY `time_cards_status_index` (`status`),
  KEY `time_cards_requires_review_index` (`requires_review`),
  KEY `time_cards_max_shift_exceeded_index` (`max_shift_exceeded`),
  CONSTRAINT `time_cards_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_cards`
--

LOCK TABLES `time_cards` WRITE;
/*!40000 ALTER TABLE `time_cards` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `time_cards` (`id`, `user_id`, `clock_in`, `clock_out`, `duration_minutes`, `work_date`, `status`, `max_shift_exceeded`, `max_shift_exceeded_at`, `requires_review`, `flags`, `review_notes`, `notes`, `ip_address`, `user_agent`, `created_at`, `updated_at`) VALUES (1,2,'2025-06-24 09:23:00','2025-06-24 14:33:00',310,'2025-06-24','completed',0,NULL,0,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','2025-06-24 16:23:18','2025-06-24 22:49:51'),(2,2,'2025-06-24 14:51:55','2025-06-24 15:36:44',45,'2025-06-24','completed',0,NULL,0,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','2025-06-24 21:51:55','2025-06-24 22:36:44'),(3,2,'2025-06-24 15:42:53','2025-06-24 15:45:16',2,'2025-06-24','completed',0,NULL,0,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','2025-06-24 22:42:53','2025-06-24 22:45:16'),(4,2,'2025-06-25 13:24:04','2025-06-25 17:45:56',262,'2025-06-25','completed',0,NULL,0,NULL,NULL,NULL,'64.180.6.194','Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:139.0) Gecko/20100101 Firefox/139.0','2025-06-25 13:24:04','2025-06-25 17:45:56'),(5,2,'2025-06-25 08:05:00','2025-06-25 13:00:00',295,'2025-06-25','completed',0,NULL,0,NULL,NULL,NULL,NULL,NULL,'2025-06-26 08:06:27','2025-06-26 08:06:56');
/*!40000 ALTER TABLE `time_cards` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `time_cards` with 5 row(s)
--

--
-- Table structure for table `time_card_tasks`
--

DROP TABLE IF EXISTS `time_card_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_card_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `time_card_id` bigint(20) unsigned NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `task_type_id` bigint(20) unsigned DEFAULT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_card_tasks_task_type_id_foreign` (`task_type_id`),
  KEY `time_card_tasks_time_card_id_task_name_index` (`time_card_id`,`task_name`),
  CONSTRAINT `time_card_tasks_task_type_id_foreign` FOREIGN KEY (`task_type_id`) REFERENCES `task_types` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_card_tasks_time_card_id_foreign` FOREIGN KEY (`time_card_id`) REFERENCES `time_cards` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_card_tasks`
--

LOCK TABLES `time_card_tasks` WRITE;
/*!40000 ALTER TABLE `time_card_tasks` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `time_card_tasks` (`id`, `time_card_id`, `task_name`, `task_type_id`, `is_custom`, `created_at`, `updated_at`) VALUES (1,2,'Computer Work',17,0,'2025-06-24 22:36:44','2025-06-24 22:36:44'),(2,3,'Computer Work',17,0,'2025-06-24 22:45:16','2025-06-24 22:45:16'),(4,1,'Washing Trays',4,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(5,1,'Making Trays',5,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(6,1,'Making Soil',6,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(7,1,'Planting Seeds',1,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(8,1,'Seed Soaking',7,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(9,1,'Sanitizing Equipment',13,0,'2025-06-24 23:05:09','2025-06-24 23:05:09'),(10,4,'Harvesting',3,0,'2025-06-25 17:45:56','2025-06-25 17:45:56'),(11,4,'Washing Trays',4,0,'2025-06-25 17:45:56','2025-06-25 17:45:56'),(12,4,'Sanitizing Equipment',13,0,'2025-06-25 17:45:56','2025-06-25 17:45:56'),(14,5,'Computer Work',17,0,'2025-06-26 08:06:56','2025-06-26 08:06:56');
/*!40000 ALTER TABLE `time_card_tasks` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `time_card_tasks` with 12 row(s)
--

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `customer_type` varchar(20) NOT NULL DEFAULT 'retail',
  `wholesale_discount_percentage` decimal(5,2) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
SET autocommit=0;
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `customer_type`, `wholesale_discount_percentage`, `company_name`, `address`, `city`, `state`, `zip`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (2,'Admin User','charybshawn@gmail.com','250-515-4007','retail',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-23 02:33:24','$2y$12$6./eGcGrqjTEByUXoQULduruiccedCDqN92b9uYzb16wQU25K0xoi',NULL,'2025-06-23 02:33:24','2025-06-23 02:33:24'),(3,'HANOI 36','hanoi36sa@gmail.com',NULL,'retail',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$a.99mIFSyIL/UtCDh7GdRObeP/2h/Olgg2g2LsOllq/xxCGJcue1m',NULL,'2025-06-24 17:59:04','2025-06-24 17:59:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;

-- Dumped table `users` with 2 row(s)
--

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET AUTOCOMMIT=@OLD_AUTOCOMMIT */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on: Thu, 26 Jun 2025 08:18:24 -0700
