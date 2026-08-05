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
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `sale_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) DEFAULT NULL,
  `block_number` varchar(20) DEFAULT NULL,
  `lot_number` varchar(20) DEFAULT NULL,
  `niche_type` varchar(100) DEFAULT NULL,
  `tcp` decimal(15,2) DEFAULT NULL,
  `cash_price` decimal(15,2) DEFAULT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `customer_fullname` varchar(150) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `agent_id` varchar(50) DEFAULT NULL,
  `agent_fullname` varchar(150) DEFAULT NULL,
  `um_id` varchar(50) DEFAULT NULL,
  `um_fullname` varchar(150) DEFAULT NULL,
  `broker_id` varchar(50) DEFAULT NULL,
  `broker_fullname` varchar(150) DEFAULT NULL,
  `payment_method` varchar(45) DEFAULT NULL,
  `lot_assume_type` varchar(45) DEFAULT NULL,
  `installment_terms` int DEFAULT NULL,
  `installment_start_date` date DEFAULT NULL,
  `installment_end_date` date DEFAULT NULL,
  `installment_monthly_payment` decimal(15,2) DEFAULT NULL,
  `sales_status` varchar(20) DEFAULT NULL,
  `cancel_by` varchar(150) DEFAULT NULL,
  `cancel_remarks` text,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,'Lawn Lots','BLK-001','LT-001','Regular',25000.00,23000.00,'CUST-2026-000001','Genobaten, Jon Anthony T.','09684958673','AGT-000001','Autida, Christian Kyle','UM-000001','Autida, Maryjoy Maedawnna','BRK-000001','Autida, John Jeff Vearl','Installment','No',24,'2026-07-15','2028-06-15',1041.67,'sold',NULL,NULL,NULL,'2026-06-15 03:00:10'),(2,'Wall Niche','BLW-01','LTW-01','Regular',250000.00,240000.00,'CUST-2026-000001','Genobaten, Jon Anthony T.','09684958673','AGT-000001','Autida, Christian Kyle','UM-000001','Autida, Maryjoy Maedawnna','BRK-000001','Autida, John Jeff Vearl','Installment','No',24,'2026-03-15','2028-02-15',10416.67,'sold',NULL,NULL,NULL,'2026-06-15 05:33:29');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-17 13:43:16
