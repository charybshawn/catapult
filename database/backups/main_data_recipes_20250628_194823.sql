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
-- Dumping data for table `recipes`
--

LOCK TABLES `recipes` WRITE;
/*!40000 ALTER TABLE `recipes` DISABLE KEYS */;
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (1,'SUNFLOWER - BLACK OIL - SF4K - 100G',NULL,2,1,NULL,9,3,1,9,5,7,450.00,15.00,100.00,NULL,24,1,'2025-06-25 00:00:50','2025-06-25 00:00:50',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (2,'SUNFLOWER  - BLACK OIL - SFK16 - 100 GRAMS',NULL,2,3,NULL,4,3,0,9,6,7,NULL,15.00,100.00,NULL,24,1,'2025-06-25 00:56:15','2025-06-25 00:56:15',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (3,'BASIL (GENOVESE) - BAS8Y - 5G -21 DAY',NULL,2,9,NULL,0,4,0,21,17,7,80.00,10.00,5.00,NULL,0,1,'2025-06-25 04:16:57','2025-06-25 04:16:57',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (4,'CORIANDER - TRUE LEAF - 45G ',NULL,2,10,NULL,4,6,0,14,8,7,135.00,5.00,45.00,NULL,24,1,'2025-06-26 21:47:56','2025-06-27 15:28:45',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (5,'CABBAGE RED - 18G',NULL,2,11,NULL,0,3,0,11,8,7,180.00,10.00,18.00,NULL,28,1,'2025-06-27 16:25:58','2025-06-27 16:25:58',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (6,'KALE (RED) - 20G',NULL,2,14,NULL,0,3,1,11,7,7,180.00,15.00,20.00,NULL,24,1,'2025-06-28 22:29:29','2025-06-28 22:29:29',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (7,'BROCCOLI - 20g',NULL,2,13,NULL,0,3,1,11,7,7,200.00,15.00,20.00,NULL,24,1,'2025-06-28 22:32:13','2025-06-28 22:32:13',NULL,NULL,NULL);
INSERT INTO `recipes` (`id`, `name`, `supplier_soil_id`, `soil_consumable_id`, `seed_consumable_id`, `seed_density`, `seed_soak_hours`, `germination_days`, `blackout_days`, `days_to_maturity`, `light_days`, `harvest_days`, `expected_yield_grams`, `buffer_percentage`, `seed_density_grams_per_tray`, `notes`, `suspend_water_hours`, `is_active`, `created_at`, `updated_at`, `seed_entry_id`, `common_name`, `cultivar_name`) VALUES (8,'PEA - SPECKLED - 300G',NULL,2,12,NULL,8,4,0,11,7,7,350.00,15.00,300.00,NULL,24,1,'2025-06-28 22:52:23','2025-06-28 22:52:23',NULL,NULL,NULL);
/*!40000 ALTER TABLE `recipes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-06-28 19:48:24
