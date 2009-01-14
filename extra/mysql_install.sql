-- phpMyAdmin SQL Dump
-- version 2.11.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 30, 2008 at 09:38 PM
-- Server version: 5.0.51
-- PHP Version: 5.2.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `services`
--

-- --------------------------------------------------------

--
-- Table structure for table `system_chans`
--

CREATE TABLE IF NOT EXISTS `system_chans` (
  `id` int(10) NOT NULL auto_increment,
  `channel` varchar(255) NOT NULL,
  `founder` tinyint(11) NOT NULL,
  `timestamp` int(20) NOT NULL,
  `last_timestamp` int(20) NOT NULL,
  `topic` text,
  `topic_setter` varchar(31) NOT NULL,
  `suspended` tinyint(1) NOT NULL default '0',
  `suspend_reason` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_chans`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_chans_flags`
--

CREATE TABLE IF NOT EXISTS `system_chans_flags` (
  `id` int(10) NOT NULL,
  `channel` varchar(255) NOT NULL,
  `flags` varchar(50) NOT NULL,
  `desc` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `welcome` varchar(255) NOT NULL,
  `modelock` text NOT NULL,
  `topicmask` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `system_chans_flags`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_chans_levels`
--

CREATE TABLE IF NOT EXISTS `system_chans_levels` (
  `id` int(10) NOT NULL auto_increment,
  `channel` varchar(255) NOT NULL,
  `target` varchar(255) NOT NULL,
  `flags` varchar(255) NOT NULL,
  `reason` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_chans_levels`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_core`
--

CREATE TABLE IF NOT EXISTS `system_core` (
  `id` int(1) NOT NULL auto_increment,
  `max_users` int(5) NOT NULL,
  `max_userstime` int(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `system_core`
--

INSERT INTO `system_core` (`id`, `max_users`, `max_userstime`) VALUES
(1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `system_failed_attempts`
--

CREATE TABLE IF NOT EXISTS `system_failed_attempts` (
  `id` int(10) NOT NULL auto_increment,
  `nick` varchar(31) NOT NULL,
  `mask` varchar(255) NOT NULL,
  `time` int(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_failed_attempts`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_ignored_users`
--

CREATE TABLE IF NOT EXISTS `system_ignored_users` (
  `id` int(5) NOT NULL auto_increment,
  `who` varchar(255) NOT NULL,
  `time` int(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_ignored_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_logon_news`
--

CREATE TABLE IF NOT EXISTS `system_logon_news` (
  `id` tinyint(2) NOT NULL auto_increment,
  `message` varchar(255) NOT NULL,
  `title` varchar(50) NOT NULL,
  `nick` varchar(31) NOT NULL,
  `time` int(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_logon_news`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_users`
--

CREATE TABLE IF NOT EXISTS `system_users` (
  `id` tinyint(11) NOT NULL auto_increment,
  `display` varchar(255) NOT NULL,
  `pass` varchar(41) NOT NULL,
  `salt` varchar(10) NOT NULL,
  `timestamp` int(20) NOT NULL,
  `last_timestamp` int(20) NOT NULL,
  `last_hostmask` varchar(255) NOT NULL,
  `vhost` varchar(255) NOT NULL,
  `identified` tinyint(1) NOT NULL default '0',
  `validated` tinyint(1) NOT NULL default '0',
  `real_user` tinyint(1) NOT NULL default '1',
  `suspended` tinyint(1) NOT NULL default '0',
  `suspend_reason` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_users_flags`
--

CREATE TABLE IF NOT EXISTS `system_users_flags` (
  `id` int(10) NOT NULL auto_increment,
  `nickname` varchar(255) NOT NULL,
  `flags` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_users_flags`
--


-- --------------------------------------------------------

--
-- Table structure for table `system_validation_codes`
--

CREATE TABLE IF NOT EXISTS `system_validation_codes` (
  `id` int(4) NOT NULL auto_increment,
  `nick` varchar(255) NOT NULL,
  `code` varchar(20) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `system_validation_codes`
--

