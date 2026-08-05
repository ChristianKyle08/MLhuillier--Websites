-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: cattleya_db
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `commission_profiles`
--

DROP TABLE IF EXISTS `commission_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commission_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `agent_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `um_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `broker_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `payment_division` int NOT NULL DEFAULT '1',
  `release_day` varchar(50) NOT NULL COMMENT 'Stores Terms of Payment: OTS, At Need, Years',
  `start_date` datetime DEFAULT NULL,
  `duration` varchar(50) NOT NULL DEFAULT 'FULLCOMM' COMMENT 'Stores Division of Commission',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at` DESC)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commission_profiles`
--

LOCK TABLES `commission_profiles` WRITE;
/*!40000 ALTER TABLE `commission_profiles` DISABLE KEYS */;
INSERT INTO `commission_profiles` VALUES (1,3.00,2.00,1.00,2,'OTS','2026-05-07 00:00:00','FULLCOMM',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(2,3.00,2.00,1.00,2,'AT NEED BUYER','2026-05-07 00:00:00','FULLCOMM',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(3,3.00,2.00,1.00,2,'1 Year','2026-05-07 00:00:00','3rd',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(4,3.00,2.00,1.00,2,'2 Years','2026-05-07 00:00:00','6th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(5,3.00,2.00,1.00,2,'3 Years','2026-05-07 00:00:00','9th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(6,3.00,2.00,1.00,2,'5 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(7,3.00,2.00,1.00,2,'6 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(8,3.00,2.00,1.00,2,'7 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(9,3.00,2.00,1.00,2,'8 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(10,3.00,2.00,1.00,2,'9 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30'),(11,3.00,2.00,1.00,2,'10 Years','2026-05-07 00:00:00','15th',1,'2026-04-21 05:48:43','2026-05-07 07:55:30');
/*!40000 ALTER TABLE `commission_profiles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-17 13:43:17
