-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: qs
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
-- Table structure for table `sendout`
--

DROP TABLE IF EXISTS `sendout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sendout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contract_number` varchar(50) DEFAULT NULL,
  `control_number` varchar(50) DEFAULT NULL,
  `sender_customerID` int DEFAULT NULL,
  `sender_name` varchar(150) DEFAULT NULL,
  `receiver_name` varchar(150) DEFAULT NULL,
  `charge_to` varchar(45) DEFAULT NULL,
  `kptn` varchar(50) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `sendout_datetime` varchar(50) DEFAULT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `principal` decimal(10,2) DEFAULT NULL,
  `charge` decimal(10,2) DEFAULT NULL,
  `commission` decimal(10,2) DEFAULT NULL,
  `so_operator` varchar(150) DEFAULT NULL,
  `region` varchar(150) DEFAULT NULL,
  `sendout_branch` varchar(150) DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `remote_operator` varchar(50) DEFAULT NULL,
  `remote_branch` varchar(150) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `imported_by` varchar(150) DEFAULT NULL,
  `imported_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sendout`
--

LOCK TABLES `sendout` WRITE;
/*!40000 ALTER TABLE `sendout` DISABLE KEYS */;
/*!40000 ALTER TABLE `sendout` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-17  9:12:13
