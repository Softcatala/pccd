/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: pccd
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB-ubu2404

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `00_EDITORIA`
--

DROP TABLE IF EXISTS `00_EDITORIA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_EDITORIA` (
  `CODI` varchar(3) DEFAULT NULL,
  `NOM` varchar(255) DEFAULT NULL,
  `DATA_ENTR` datetime DEFAULT NULL,
  `ADREÇA` varchar(255) DEFAULT NULL,
  `MUNICIPI` varchar(255) DEFAULT NULL,
  `CODI_POST` varchar(5) DEFAULT NULL,
  `TELEFON` varchar(10) DEFAULT NULL,
  `FAX` varchar(10) DEFAULT NULL,
  `EMAIL` varchar(255) DEFAULT NULL,
  `INTERNET` varchar(255) DEFAULT NULL,
  `CONTACTE` varchar(255) DEFAULT NULL,
  `DARRER_CAT` datetime DEFAULT NULL,
  `OBSERVACIO` varchar(255) DEFAULT NULL,
  KEY `CODI` (`CODI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_EQUIVALENTS`
--

DROP TABLE IF EXISTS `00_EQUIVALENTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_EQUIVALENTS` (
  `CODI` varchar(255) NOT NULL,
  `IDIOMA` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_FONTS`
--

DROP TABLE IF EXISTS `00_FONTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_FONTS` (
  `Comptador` int(11) NOT NULL AUTO_INCREMENT,
  `Identificador` varchar(255) DEFAULT NULL,
  `CODI_RML` varchar(255) DEFAULT NULL,
  `Autor` varchar(255) DEFAULT NULL,
  `Any` varchar(10) DEFAULT NULL,
  `Títol` varchar(255) DEFAULT NULL,
  `ISBN` varchar(50) DEFAULT NULL,
  `Codi_edit` varchar(3) DEFAULT NULL,
  `Editorial` varchar(255) DEFAULT NULL,
  `Municipi` varchar(255) DEFAULT NULL,
  `Edició` varchar(25) DEFAULT NULL,
  `Any_edició` int(11) DEFAULT NULL,
  `Collecció` varchar(255) DEFAULT NULL,
  `Núm_collecció` varchar(255) DEFAULT NULL,
  `Pàgines` int(11) DEFAULT NULL,
  `Idioma` varchar(255) DEFAULT NULL,
  `Varietat_dialectal` varchar(255) DEFAULT NULL,
  `Registres` int(11) DEFAULT NULL,
  `Preu` float DEFAULT NULL,
  `Data_compra` date DEFAULT NULL,
  `Lloc_compra` varchar(255) DEFAULT NULL,
  `Imatge` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `Observacions` text DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `Comptador` (`Comptador`),
  KEY `Identificador` (`Identificador`)
) ENGINE=InnoDB AUTO_INCREMENT=797 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_IMATGES`
--

DROP TABLE IF EXISTS `00_IMATGES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_IMATGES` (
  `Comptador` int(11) NOT NULL AUTO_INCREMENT,
  `Identificador` varchar(255) DEFAULT NULL,
  `TIPUS` varchar(1) DEFAULT NULL,
  `MODISME` varchar(255) DEFAULT NULL,
  `PAREMIOTIPUS` varchar(255) DEFAULT NULL,
  `IDIOMA` varchar(255) DEFAULT NULL,
  `EQUIVALENT` varchar(255) DEFAULT NULL,
  `LLOC` varchar(255) DEFAULT NULL,
  `DESCRIPCIO` varchar(255) DEFAULT NULL,
  `AUTOR` varchar(255) DEFAULT NULL,
  `ANY` double DEFAULT NULL,
  `EDITORIAL` varchar(3) DEFAULT NULL,
  `DIARI` varchar(255) DEFAULT NULL,
  `ARTICLE` varchar(200) DEFAULT NULL,
  `PAGINA` varchar(10) DEFAULT NULL,
  `URL_ENLLAÇ` varchar(255) DEFAULT NULL,
  `TIPUS_IMATGE` varchar(255) DEFAULT NULL,
  `URL_IMATGE` varchar(255) DEFAULT NULL,
  `OBSERVACIONS` varchar(255) DEFAULT NULL,
  `DATA` date DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `Comptador` (`Comptador`),
  KEY `PAREMIOTIPUS` (`PAREMIOTIPUS`)
) ENGINE=InnoDB AUTO_INCREMENT=6552 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_OBRESVPR`
--

DROP TABLE IF EXISTS `00_OBRESVPR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_OBRESVPR` (
  `Comptador` int(11) NOT NULL,
  `Identificador` varchar(255) DEFAULT NULL,
  `Autor` varchar(255) DEFAULT NULL,
  `Any` varchar(10) DEFAULT NULL,
  `Títol` varchar(255) DEFAULT NULL,
  `ISBN` varchar(50) DEFAULT NULL,
  `Codi_edit` varchar(3) DEFAULT NULL,
  `Editorial` varchar(255) DEFAULT NULL,
  `Municipi` varchar(255) DEFAULT NULL,
  `Edició` varchar(25) DEFAULT NULL,
  `Any_edició` int(11) DEFAULT NULL,
  `Collecció` varchar(255) DEFAULT NULL,
  `Núm_collecció` varchar(255) DEFAULT NULL,
  `Pàgines` int(11) DEFAULT NULL,
  `Idioma` varchar(255) DEFAULT NULL,
  `Preu` float DEFAULT NULL,
  `Imatge` varchar(255) DEFAULT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_PAREMIOTIPUS`
--

DROP TABLE IF EXISTS `00_PAREMIOTIPUS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `00_PAREMIOTIPUS` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `TIPUS` varchar(1) DEFAULT NULL,
  `MODISME` varchar(255) DEFAULT NULL,
  `PAREMIOTIPUS` varchar(255) DEFAULT NULL,
  `SINONIM` varchar(255) DEFAULT NULL,
  `IDIOMA` varchar(255) DEFAULT NULL,
  `EQUIVALENT` varchar(255) DEFAULT NULL,
  `LLOC` varchar(255) DEFAULT NULL,
  `AUTORIA` varchar(255) DEFAULT NULL,
  `FONT` varchar(255) DEFAULT NULL,
  `EXPLICACIO` varchar(255) DEFAULT NULL,
  `EXPLICACIO2` varchar(255) DEFAULT NULL,
  `EXEMPLES` varchar(255) DEFAULT NULL,
  `AUTOR` varchar(255) DEFAULT NULL,
  `ANY` double DEFAULT NULL,
  `EDITORIAL` varchar(3) DEFAULT NULL,
  `ID_FONT` varchar(255) DEFAULT NULL,
  `DIARI` varchar(255) DEFAULT NULL,
  `ARTICLE` varchar(200) DEFAULT NULL,
  `PAGINA` varchar(10) DEFAULT NULL,
  `NUM_ORDRE` varchar(255) DEFAULT NULL,
  `DATA` date DEFAULT NULL,
  `ACCEPCIO` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id` (`Id`),
  KEY `PAREMIOTIPUS` (`PAREMIOTIPUS`),
  KEY `MODISME` (`MODISME`),
  KEY `AUTOR` (`AUTOR`),
  KEY `ID_FONT` (`ID_FONT`),
  FULLTEXT KEY `PAREMIOTIPUS_2` (`PAREMIOTIPUS`),
  FULLTEXT KEY `PAREMIOTIPUS_3` (`PAREMIOTIPUS`,`MODISME`),
  FULLTEXT KEY `PAREMIOTIPUS_4` (`PAREMIOTIPUS`,`SINONIM`),
  FULLTEXT KEY `PAREMIOTIPUS_5` (`PAREMIOTIPUS`,`EQUIVALENT`),
  FULLTEXT KEY `PAREMIOTIPUS_6` (`PAREMIOTIPUS`,`MODISME`,`SINONIM`),
  FULLTEXT KEY `PAREMIOTIPUS_7` (`PAREMIOTIPUS`,`MODISME`,`EQUIVALENT`),
  FULLTEXT KEY `PAREMIOTIPUS_8` (`PAREMIOTIPUS`,`SINONIM`,`EQUIVALENT`),
  FULLTEXT KEY `PAREMIOTIPUS_9` (`PAREMIOTIPUS`,`MODISME`,`SINONIM`,`EQUIVALENT`)
) ENGINE=InnoDB AUTO_INCREMENT=1065884 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RML`
--

DROP TABLE IF EXISTS `RML`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `RML` (
  `NUM_ORDRE` int(11) NOT NULL AUTO_INCREMENT,
  `COMPLET` varchar(255) DEFAULT NULL,
  `TIPUS` varchar(255) DEFAULT NULL,
  `MODISME` varchar(255) DEFAULT NULL,
  `F_MODISME` varchar(255) DEFAULT NULL,
  `NUM_ACCEPCIO` varchar(2) DEFAULT NULL,
  `PAREMIOTIPUS` varchar(255) DEFAULT NULL,
  `DEFINICIÓ` varchar(255) DEFAULT NULL,
  `F_DEFINICIÓ` varchar(255) DEFAULT NULL,
  `SINÒNIMS` varchar(255) DEFAULT NULL,
  `CODI_CASTELLÀ` varchar(255) DEFAULT NULL,
  `EQ_CASTELLÀ` varchar(255) DEFAULT NULL,
  `F_CASTELLÀ` varchar(255) DEFAULT NULL,
  `CODI_ANGLÈS` varchar(255) DEFAULT NULL,
  `EQ_ANGLÈS` varchar(255) DEFAULT NULL,
  `F_ANGLÈS` varchar(255) DEFAULT NULL,
  `CODI_LLATÍ` varchar(255) DEFAULT NULL,
  `EQ_LLATÍ` varchar(255) DEFAULT NULL,
  `F_LLATÍ` varchar(255) DEFAULT NULL,
  `CODI_FRANCÈS` varchar(255) DEFAULT NULL,
  `EQ_FRANCÈS` varchar(255) DEFAULT NULL,
  `F_FRANCÈS` varchar(255) DEFAULT NULL,
  `CODI_ITALIÀ` varchar(255) DEFAULT NULL,
  `EQ_ITALIÀ` varchar(255) DEFAULT NULL,
  `F_ITALIÀ` varchar(255) DEFAULT NULL,
  `CODI_PORTUGUÈS` varchar(255) DEFAULT NULL,
  `EQ_PORTUGUÈS` varchar(255) DEFAULT NULL,
  `F_PORTUGUÈS` varchar(255) DEFAULT NULL,
  `CODI_GALLEC` varchar(255) DEFAULT NULL,
  `EQ_GALLEC` varchar(255) DEFAULT NULL,
  `F_GALLEC` varchar(255) DEFAULT NULL,
  `CODI_OCCITÀ` varchar(255) DEFAULT NULL,
  `EQ_OCCITÀ` varchar(255) DEFAULT NULL,
  `F_OCCITÀ` varchar(255) DEFAULT NULL,
  `CODI_ASTURIÀ` varchar(255) DEFAULT NULL,
  `EQ_ASTURIÀ` varchar(255) DEFAULT NULL,
  `F_ASTURIÀ` varchar(255) DEFAULT NULL,
  `CODI_ROMANÈS` varchar(255) DEFAULT NULL,
  `EQ_ROMANÈS` varchar(255) DEFAULT NULL,
  `F_ROMANÈS` varchar(255) DEFAULT NULL,
  `CODI_ARAGONÈS` varchar(255) DEFAULT NULL,
  `EQ_ARAGONÈS` varchar(255) DEFAULT NULL,
  `F_ARAGONÈS` varchar(255) DEFAULT NULL,
  `CODI_ESPERANTO` varchar(255) DEFAULT NULL,
  `EQ_ESPERANTO` varchar(255) DEFAULT NULL,
  `F_ESPERANTO` varchar(255) DEFAULT NULL,
  `CODI_EUSKARA` varchar(255) DEFAULT NULL,
  `EQ_EUSKARA` varchar(255) DEFAULT NULL,
  `F_EUSKARA` varchar(255) DEFAULT NULL,
  `OBSERVACIONS` varchar(255) DEFAULT NULL,
  `REVISAT` varchar(2) DEFAULT NULL,
  UNIQUE KEY `NUM_ORDRE` (`NUM_ORDRE`),
  KEY `PAREMIOTIPUS` (`PAREMIOTIPUS`)
) ENGINE=InnoDB AUTO_INCREMENT=2679 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `common_paremiotipus`
--

DROP TABLE IF EXISTS `common_paremiotipus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `common_paremiotipus` (
  `Paremiotipus` varchar(255) DEFAULT NULL,
  `Compt` int(11) DEFAULT NULL,
  KEY `Compt` (`Compt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commonvoice`
--

DROP TABLE IF EXISTS `commonvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `commonvoice` (
  `paremiotipus` varchar(300) NOT NULL,
  `file` varchar(200) NOT NULL,
  PRIMARY KEY (`paremiotipus`,`file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paremiotipus_display`
--

DROP TABLE IF EXISTS `paremiotipus_display`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `paremiotipus_display` (
  `Paremiotipus` varchar(255) NOT NULL,
  `Display` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Paremiotipus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pccd_is_installed`
--

DROP TABLE IF EXISTS `pccd_is_installed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `pccd_is_installed` (
  `id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed
