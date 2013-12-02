-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 28, 2013 at 11:38 AM
-- Server version: 5.5.32
-- PHP Version: 5.3.10-1ubuntu3.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `voices`
--

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `con_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `con_com_id` int(10) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `tel_number` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `village` varchar(255) DEFAULT NULL,
  `zone` varchar(255) DEFAULT NULL,
  UNIQUE KEY `con_id` (`con_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=63 ;

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_message` text NOT NULL,
  `log_level` varchar(30) NOT NULL,
  `log_time` datetime NOT NULL,
  `stack` text,
  `request` text,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=208 ;

-- --------------------------------------------------------

--
-- Table structure for table `market_store`
--

CREATE TABLE IF NOT EXISTS `market_store` (
  `store_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `com_id` varchar(30) NOT NULL,
  `con_com_id` int(10) NOT NULL,
  `prod_com_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `com_number` int(4) NOT NULL,
  `prod_name` varchar(255) NOT NULL,
  `unit_measure` varchar(10) DEFAULT NULL,
  `quantity` float(10,2) DEFAULT NULL,
  `quality` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `ts_date_entered` datetime NOT NULL,
  `ts_date_delivered` datetime NOT NULL,
  `contact_fname` varchar(255) NOT NULL,
  `contact_lname` varchar(255) NOT NULL,
  `contact_tel` varchar(30) DEFAULT NULL,
  `contact_address` varchar(255) DEFAULT NULL,
  `village` varchar(255) DEFAULT NULL,
  `zone` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=66 ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `prod_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `con_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `prod_com_id` int(10) NOT NULL,
  `prod_name` varchar(255) NOT NULL,
  `unit_measure` varchar(10) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `quality` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `valid` enum('yes','no') DEFAULT NULL,
  `ts_date_entered` datetime NOT NULL,
  `ts_date_delivered` datetime DEFAULT NULL,
  PRIMARY KEY (`prod_id`),
  KEY `con_id` (`con_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=43 ;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE IF NOT EXISTS `uploads` (
  `upload_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `com_number` int(100) NOT NULL,
  `com_id` varchar(30) DEFAULT NULL,
  `content` varchar(255) NOT NULL,
  `lang_code` varchar(10) DEFAULT NULL,
  `prod_com_ids` varchar(255) NOT NULL,
  `rad_sta_code` int(10) DEFAULT NULL,
  `delivered` varchar(10) NOT NULL DEFAULT 'no',
  `ts_date_submitted` datetime NOT NULL,
  `ts_date_delivered` datetime DEFAULT NULL,
  PRIMARY KEY (`upload_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=165 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(32) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `ts_created` datetime NOT NULL,
  `ts_last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `users_profile`
--

CREATE TABLE IF NOT EXISTS `users_profile` (
  `user_id` bigint(20) unsigned NOT NULL,
  `profile_key` varchar(255) NOT NULL,
  `profile_value` mediumtext NOT NULL,
  PRIMARY KEY (`user_id`,`profile_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`con_id`) REFERENCES `contacts` (`con_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `users_profile`
--
ALTER TABLE `users_profile`
  ADD CONSTRAINT `users_profile_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
