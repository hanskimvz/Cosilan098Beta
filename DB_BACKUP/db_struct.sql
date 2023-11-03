-- MariaDB dump 10.19  Distrib 10.4.24-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: 
-- ------------------------------------------------------
-- Server version	10.4.24-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `cnt_demo`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `cnt_demo` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `cnt_demo`;

--
-- Table structure for table `age_gender`
--

DROP TABLE IF EXISTS `age_gender`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `age_gender` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT NULL,
  `month` int(2) unsigned DEFAULT NULL,
  `day` int(2) unsigned DEFAULT NULL,
  `hour` int(2) unsigned DEFAULT NULL,
  `min` int(2) unsigned DEFAULT NULL,
  `wday` int(1) unsigned DEFAULT NULL,
  `age_1st` int(10) unsigned DEFAULT 0,
  `age_2nd` int(10) unsigned DEFAULT 0,
  `age_3rd` int(10) unsigned DEFAULT 0,
  `age_4th` int(10) unsigned DEFAULT 0,
  `age_5th` int(10) unsigned DEFAULT 0,
  `age_6th` int(10) unsigned DEFAULT 0,
  `age_7th` int(10) unsigned DEFAULT 0,
  `male` int(10) unsigned DEFAULT 0,
  `female` int(10) unsigned DEFAULT 0,
  `age` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gender` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `week` int(2) DEFAULT NULL,
  `square_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `camera`
--

DROP TABLE IF EXISTS `camera`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `camera` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `square_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usn` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable_countingline` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `enable_heatmap` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `enable_snapshot` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `enable_face_det` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `enable_macsniff` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `count_tenmin`
--

DROP TABLE IF EXISTS `count_tenmin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `count_tenmin` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT NULL,
  `month` int(2) unsigned DEFAULT NULL,
  `day` int(2) unsigned DEFAULT NULL,
  `hour` int(2) unsigned DEFAULT NULL,
  `min` int(2) unsigned DEFAULT NULL,
  `wday` int(1) unsigned DEFAULT NULL,
  `week` int(2) unsigned DEFAULT NULL,
  `counter_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_val` int(11) unsigned DEFAULT NULL,
  `counter_label` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `camera_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `square_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counter_label`
--

DROP TABLE IF EXISTS `counter_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counter_label` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `camera_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_name` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `face_analysis`
--

DROP TABLE IF EXISTS `face_analysis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_analysis` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `thumbnail` blob DEFAULT NULL,
  `ref_face_token` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_token` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confidence` float unsigned DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `date` datetime DEFAULT NULL,
  `face_thumbnail_pk` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `face_set`
--

DROP TABLE IF EXISTS `face_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_set` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `thumbnail` blob DEFAULT NULL,
  `face_token` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(31) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `heatmap`
--

DROP TABLE IF EXISTS `heatmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heatmap` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `year` int(4) unsigned DEFAULT NULL,
  `month` int(2) unsigned DEFAULT NULL,
  `day` int(2) unsigned DEFAULT NULL,
  `hour` int(2) unsigned DEFAULT NULL,
  `wday` int(1) unsigned DEFAULT NULL,
  `body_csv` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `week` int(2) unsigned DEFAULT NULL,
  `camera_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `store_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `square_code` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `varstr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eng` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `realtime_counting`
--

DROP TABLE IF EXISTS `realtime_counting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realtime_counting` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_before` int(2) unsigned DEFAULT 0,
  `ct_label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `ct_value` int(11) DEFAULT 0,
  `latest` int(10) unsigned DEFAULT NULL,
  `ref_date` datetime DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `realtime_screen`
--

DROP TABLE IF EXISTS `realtime_screen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `realtime_screen` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enable` enum('yes','no') COLLATE utf8mb4_unicode_ci DEFAULT 'yes',
  `text` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `font` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `color` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `size` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `position` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `padding` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '[]',
  `ct_labels` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '["entrance", "exit"]',
  `rule` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `square`
--

DROP TABLE IF EXISTS `square`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `square` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_state` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_city` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_b` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `store`
--

DROP TABLE IF EXISTS `store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `square_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_state` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_city` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addr_b` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_tel` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `open_hour` smallint(6) DEFAULT NULL,
  `close_hour` smallint(6) DEFAULT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `sniffing_mac` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` int(10) unsigned DEFAULT NULL,
  `apply_open_hour` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_eng` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `language` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_b` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `theme` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `date_in` date DEFAULT NULL,
  `date_out` date DEFAULT NULL,
  `img` mediumblob DEFAULT NULL,
  `comment` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weather`
--

DROP TABLE IF EXISTS `weather`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weather` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `cityid` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cityCn` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weather` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `temperature` int(3) DEFAULT NULL,
  `temp_low` int(3) DEFAULT NULL,
  `temp_high` int(3) DEFAULT NULL,
  `wind` varchar(31) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `humidity` int(3) DEFAULT NULL,
  `visibility` int(5) DEFAULT NULL,
  `pressure` int(5) DEFAULT NULL,
  `air` int(5) DEFAULT NULL,
  `air_pm25` int(5) DEFAULT NULL,
  `air_level` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webpage_config`
--

DROP TABLE IF EXISTS `webpage_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `webpage_config` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `frame` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depth` int(10) DEFAULT 0,
  `pos_x` int(10) DEFAULT 0,
  `pos_y` int(10) DEFAULT 0,
  `body` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Current Database: `common`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `common` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `common`;

--
-- Table structure for table `access_log`
--

DROP TABLE IF EXISTS `access_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `access_log` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `act` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_addr` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ID` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PHPSESSID` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_session_time` datetime DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counting_event`
--

DROP TABLE IF EXISTS `counting_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counting_event` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_ip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `counter_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_val` int(11) unsigned DEFAULT 0,
  `counter_diff` int(11) unsigned DEFAULT 0,
  `message` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `status` int(2) unsigned DEFAULT 0,
  `db_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`),
  KEY `device_info` (`device_info`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `counting_report_10min`
--

DROP TABLE IF EXISTS `counting_report_10min`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counting_report_10min` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `counter_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counter_val` int(11) unsigned DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `datetime` datetime DEFAULT NULL,
  `tag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `status` int(2) unsigned DEFAULT 0,
  PRIMARY KEY (`pk`),
  KEY `device_info` (`device_info`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `face_thumbnail`
--

DROP TABLE IF EXISTS `face_thumbnail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `face_thumbnail` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `thumbnail` blob DEFAULT NULL,
  `age` int(3) unsigned DEFAULT NULL,
  `gender` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emotion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `get_str` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_r` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `face_token` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag_fd` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `flag_ud` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `flag_fs` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `status` int(2) unsigned DEFAULT 0,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `facesets`
--

DROP TABLE IF EXISTS `facesets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facesets` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `api_host` varchar(63) DEFAULT NULL,
  `api_key` varchar(63) DEFAULT NULL,
  `api_secret` varchar(63) DEFAULT NULL,
  `faceset` varchar(63) DEFAULT NULL,
  `db_name` varchar(63) DEFAULT 'none',
  `flag` enum('y','n') DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `heatmap`
--

DROP TABLE IF EXISTS `heatmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `heatmap` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `body_csv` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `datetime` datetime DEFAULT NULL,
  `status` int(2) unsigned DEFAULT 0,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `language` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `varstr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eng` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kor` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `page` varchar(63) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `macsniff`
--

DROP TABLE IF EXISTS `macsniff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `macsniff` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `timestamp` int(10) unsigned DEFAULT NULL,
  `ip_addr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `mac_src` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_dst` varchar(18) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `frame` int(2) unsigned DEFAULT NULL,
  `subframe` int(2) unsigned DEFAULT NULL,
  `channel` int(2) unsigned DEFAULT NULL,
  `rssi` int(11) DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `message`
--

DROP TABLE IF EXISTS `message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `category` varchar(13) DEFAULT NULL,
  `from_p` varchar(60) DEFAULT NULL,
  `to_p` varchar(60) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `flag` enum('y','n') DEFAULT 'n',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `params`
--

DROP TABLE IF EXISTS `params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `params` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usn` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_id` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lic_pro` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lic_surv` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lic_count` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `face_det` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `heatmap` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countrpt` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `macsniff` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `write_cgi_cmd` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `initial_access` datetime DEFAULT NULL,
  `last_access` datetime DEFAULT NULL,
  `db_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `param` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method` enum('auto','manual') COLLATE utf8mb4_unicode_ci DEFAULT 'auto',
  `user_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'root',
  `user_pw` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'pass',
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snapshot`
--

DROP TABLE IF EXISTS `snapshot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snapshot` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `device_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` mediumblob DEFAULT NULL,
  `regdate` datetime DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `pk` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `regdate` datetime DEFAULT NULL,
  `code` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ID` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passwd` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `db_name` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'none',
  `flag` enum('y','n') COLLATE utf8mb4_unicode_ci DEFAULT 'n',
  `role` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`pk`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- Dump completed on 2023-01-04  7:34:59
