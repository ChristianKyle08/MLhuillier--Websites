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
-- Table structure for table `lessor_profile`
--

DROP TABLE IF EXISTS `lessor_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lessor_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(150) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(150) DEFAULT NULL,
  `gender` varchar(45) DEFAULT NULL,
  `address` varchar(250) DEFAULT NULL,
  `mobile_number` varchar(45) DEFAULT NULL,
  `status` varchar(25) DEFAULT NULL,
  `corporate_name` varchar(250) DEFAULT NULL,
  `lessor_type` varchar(45) DEFAULT NULL,
  `main_zone` varchar(45) DEFAULT NULL,
  `region` varchar(150) DEFAULT NULL,
  `area` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lessor_profile`
--

LOCK TABLES `lessor_profile` WRITE;
/*!40000 ALTER TABLE `lessor_profile` DISABLE KEYS */;
INSERT INTO `lessor_profile` VALUES (1,'Christian Kyle','Paredes','Autida','Male','Banawa, Cebu City, Cebu','09464364745','Active',NULL,'Individual','VISMIN','Leyte B','C'),(3,'Mary Sheena','A','Cantalejo','Female','Cantabaco, Toledo, Cebu City','09464752356','Active',NULL,'Individual','VISMIN','Leyte B','B'),(4,'Elgin','Paredes','Autida','Female','Tuboran, Bien Unido, Bohol','09435647635','Active',NULL,'Individual','LNCR','South Eastern Luzon Region','A'),(5,'CHRIS','NM','AUTIDA','Male','TUBORAN, BIEN UNIDO, BOHOL','09643326535','Active',NULL,'Individual','LNCR','South Eastern Luzon Region','A'),(6,'CHRISTIAN','PAREDES','ALVAREZ','Male','TUBORAN, BIEN UNIDO, BOHOL','09345346345','Active',NULL,'Individual','VISMIN','Cebu Central Region A','A');
/*!40000 ALTER TABLE `lessor_profile` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-03 14:41:02
