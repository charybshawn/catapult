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
-- Dumping data for table `seed_scrape_uploads`
--

LOCK TABLES `seed_scrape_uploads` WRITE;
/*!40000 ALTER TABLE `seed_scrape_uploads` DISABLE KEYS */;
INSERT INTO `seed_scrape_uploads` (`id`, `original_filename`, `status`, `uploaded_at`, `processed_at`, `notes`, `failed_entries`, `total_entries`, `successful_entries`, `failed_entries_count`, `created_at`, `updated_at`) VALUES (1,'sprouting_com_detailed_20250609_103826.json','processing','2025-06-23 10:02:01',NULL,NULL,NULL,0,0,0,'2025-06-23 10:02:01','2025-06-23 10:02:01');
INSERT INTO `seed_scrape_uploads` (`id`, `original_filename`, `status`, `uploaded_at`, `processed_at`, `notes`, `failed_entries`, `total_entries`, `successful_entries`, `failed_entries_count`, `created_at`, `updated_at`) VALUES (2,'sprouting_com_detailed_20250603_103912.json','completed','2025-06-24 23:05:14','2025-06-24 23:05:18','Processed 87/87 products successfully with supplier: Sprouting Seeds.','[]',87,87,0,'2025-06-24 23:05:14','2025-06-24 23:05:18');
/*!40000 ALTER TABLE `seed_scrape_uploads` ENABLE KEYS */;
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
