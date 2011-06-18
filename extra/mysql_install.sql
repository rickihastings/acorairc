-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 18, 2011 at 06:56 PM
-- Server version: 5.5.8
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `services`
--

-- --------------------------------------------------------

--
-- Table structure for table `system_chans`
--

CREATE TABLE IF NOT EXISTS `system_chans` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `timestamp` int(20) NOT NULL,
  `last_timestamp` int(20) NOT NULL,
  `topic` text,
  `topic_setter` varchar(31) NOT NULL,
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `suspend_reason` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_chans`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_chans_flags`
--

CREATE TABLE IF NOT EXISTS `system_chans_flags` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `flags` varchar(50) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `welcome` varchar(255) NOT NULL,
  `modelock` text NOT NULL,
  `topicmask` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_chans_flags`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_chans_levels`
--

CREATE TABLE IF NOT EXISTS `system_chans_levels` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `setby` varchar(255) NOT NULL,
  `target` varchar(255) NOT NULL,
  `flags` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `expire` int(10) NOT NULL,
  `timestamp` int(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_chans_levels`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_core`
--

CREATE TABLE IF NOT EXISTS `system_core` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `max_users` int(5) NOT NULL,
  `max_userstime` int(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_core`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_failed_attempts`
--

CREATE TABLE IF NOT EXISTS `system_failed_attempts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `nick` varchar(31) NOT NULL,
  `mask` varchar(255) NOT NULL,
  `time` int(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_failed_attempts`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_ignored_users`
--

CREATE TABLE IF NOT EXISTS `system_ignored_users` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `who` varchar(255) NOT NULL,
  `time` int(20) NOT NULL,
  `temp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_ignored_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_logon_news`
--

CREATE TABLE IF NOT EXISTS `system_logon_news` (
  `id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `message` varchar(255) NOT NULL,
  `title` varchar(50) NOT NULL,
  `nick` varchar(31) NOT NULL,
  `time` int(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_logon_news`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_sessions`
--

CREATE TABLE IF NOT EXISTS `system_sessions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `nick` varchar(255) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `limit` int(3) NOT NULL,
  `description` varchar(255) NOT NULL,
  `time` int(20) NOT NULL,
  `expire` int(20) NOT NULL,
  `hostmask` varchar(255) NOT NULL,
  `akill` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_sessions`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_users`
--

CREATE TABLE IF NOT EXISTS `system_users` (
  `id` tinyint(11) NOT NULL AUTO_INCREMENT,
  `display` varchar(255) NOT NULL,
  `pass` varchar(41) NOT NULL,
  `salt` varchar(10) NOT NULL,
  `timestamp` int(20) NOT NULL,
  `last_timestamp` int(20) NOT NULL,
  `last_hostmask` varchar(255) NOT NULL,
  `vhost` varchar(255) NOT NULL,
  `identified` tinyint(1) NOT NULL DEFAULT '0',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `real_user` tinyint(1) NOT NULL DEFAULT '1',
  `suspended` tinyint(1) NOT NULL DEFAULT '0',
  `suspend_reason` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_users_flags`
--

CREATE TABLE IF NOT EXISTS `system_users_flags` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(255) NOT NULL,
  `flags` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_users_flags`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_validation_codes`
--

CREATE TABLE IF NOT EXISTS `system_validation_codes` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `nick` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_validation_codes`
--

