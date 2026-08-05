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
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `block_number` varchar(10) DEFAULT NULL,
  `lot_number` varchar(20) DEFAULT NULL,
  `niche_type` varchar(100) DEFAULT NULL,
  `block_description` text,
  `tcp` decimal(15,2) DEFAULT '0.00',
  `cash_price` decimal(15,2) DEFAULT NULL,
  `marketing_budget` decimal(15,2) DEFAULT '0.00',
  `care_fund` decimal(15,2) DEFAULT '0.00',
  `vat` decimal(10,2) DEFAULT '0.00',
  `lot_price` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) DEFAULT 'available',
  `product_image` longblob,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `unique_block_lot` (`block_number`,`lot_number`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,'Lawn Lots','BLK-001','LT-001','Regular','NONE',25000.00,23000.00,0.00,0.00,0.00,0.00,'sold',NULL,'2026-04-22 00:59:06','Kyle Paredes'),(2,'Lawn Lots','BLK-001B','LT-001B','Regular','BASAK',514298.00,500000.00,0.00,0.00,0.00,0.00,'available',NULL,'2026-04-22 01:05:37','Kyle Paredes'),(3,'Lawn Lots','BLK-001','LT-002','Regular','NONE',399300.00,319440.00,0.00,0.00,0.00,0.00,'available',NULL,'2026-04-22 01:20:59','Kyle Paredes'),(4,'Lawn Lots','BLK-001B','LT-002B','Regular','NONE',421534.00,337227.00,0.00,0.00,0.00,0.00,'available',NULL,'2026-04-22 01:22:38','Kyle Paredes'),(5,'Lawn Lots','BLK-001','LT-003','Regular','NONE',428050.00,400000.00,0.00,0.00,0.00,0.00,'available',NULL,'2026-04-22 01:24:08','Kyle Paredes'),(6,'Lawn Lots','BLK-001B','LT-003B','Regular','NONE',451885.00,450000.00,0.00,0.00,0.00,0.00,'available',NULL,'2026-04-22 01:50:50','Kyle Paredes'),(7,'Lawn Lots','BLK-001','LT-004','Premium','NONE',428050.00,450000.00,0.00,0.00,0.00,0.00,'inactive',NULL,'2026-04-24 00:07:46','Kyle Paredes');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-03 14:43:14
