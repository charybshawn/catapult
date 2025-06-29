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
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `customer_type`, `wholesale_discount_percentage`, `company_name`, `address`, `city`, `state`, `zip`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (2,'Admin User','charybshawn@gmail.com','250-515-4007','retail',NULL,NULL,NULL,NULL,NULL,NULL,'2025-06-23 09:33:24','$2y$12$6./eGcGrqjTEByUXoQULduruiccedCDqN92b9uYzb16wQU25K0xoi',NULL,'2025-06-23 09:33:24','2025-06-23 09:33:24');
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `customer_type`, `wholesale_discount_percentage`, `company_name`, `address`, `city`, `state`, `zip`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES (3,'HANOI 36','hanoi36sa@gmail.com',NULL,'retail',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'$2y$12$a.99mIFSyIL/UtCDh7GdRObeP/2h/Olgg2g2LsOllq/xxCGJcue1m',NULL,'2025-06-25 00:59:04','2025-06-25 00:59:37');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
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
