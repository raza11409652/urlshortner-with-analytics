-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 28, 2019 at 03:44 PM
-- Server version: 10.1.25-MariaDB
-- PHP Version: 7.1.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `url`
--

-- --------------------------------------------------------

--
-- Table structure for table `url_bundle`
--

CREATE TABLE `url_bundle` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `userid` mediumint(9) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `access` varchar(10) NOT NULL DEFAULT 'private',
  `view` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `url_page`
--

CREATE TABLE `url_page` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `seo` varchar(255) DEFAULT NULL,
  `content` text,
  `menu` int(11) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `url_page`
--

INSERT INTO `url_page` (`id`, `name`, `seo`, `content`, `menu`) VALUES
(1, 'Terms and Conditions', 'terms', 'Please edit me when you can. I am very important.', 1);

-- --------------------------------------------------------

--
-- Table structure for table `url_payment`
--

CREATE TABLE `url_payment` (
  `id` int(11) NOT NULL,
  `tid` varchar(255) DEFAULT NULL,
  `userid` bigint(20) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiry` datetime DEFAULT NULL,
  `data` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `url_settings`
--

CREATE TABLE `url_settings` (
  `config` varchar(255) NOT NULL,
  `var` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `url_settings`
--

INSERT INTO `url_settings` (`config`, `var`) VALUES
('url', 'http://localhost/url'),
('title', ''),
('description', ''),
('api', '1'),
('user', '1'),
('sharing', '1'),
('geotarget', '1'),
('adult', '1'),
('maintenance', '0'),
('keywords', ''),
('theme', 'cleanex'),
('apikey', ''),
('ads', '1'),
('captcha', '0'),
('ad728', ''),
('ad468', ''),
('ad300', ''),
('frame', '0'),
('facebook', ''),
('twitter', ''),
('email', 'ittripathy@gmail.com'),
('fb_connect', '0'),
('analytic', ''),
('private', '0'),
('facebook_app_id', ''),
('facebook_secret', ''),
('twitter_key', ''),
('twitter_secret', ''),
('safe_browsing', ''),
('captcha_public', ''),
('captcha_private', ''),
('tw_connect', '0'),
('multiple_domains', '0'),
('domain_names', ''),
('tracking', '1'),
('update_notification', '0'),
('default_lang', ''),
('user_activate', '0'),
('domain_blacklist', ''),
('keyword_blacklist', ''),
('user_history', '0'),
('pro_yearly', ''),
('show_media', '0'),
('pro_monthly', ''),
('paypal_email', ''),
('logo', ''),
('timer', ''),
('smtp', ''),
('style', ''),
('font', ''),
('currency', 'USD'),
('news', '<strong>Installation successful</strong> Please go to the admin panel to configure important settings including this message!'),
('gl_connect', '0'),
('require_registration', '0'),
('phish_api', ''),
('aliases', ''),
('pro', '1'),
('google_cid', ''),
('google_cs', ''),
('public_dir', '0'),
('devicetarget', '1'),
('homepage_stats', '1'),
('home_redir', ''),
('detectadblock', '0'),
('timezone', ''),
('freeurls', '10');

-- --------------------------------------------------------

--
-- Table structure for table `url_splash`
--

CREATE TABLE `url_splash` (
  `id` int(11) NOT NULL,
  `userid` bigint(12) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `data` text,
  `date` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `url_stats`
--

CREATE TABLE `url_stats` (
  `id` int(11) NOT NULL,
  `short` varchar(255) DEFAULT NULL,
  `urlid` bigint(20) DEFAULT NULL,
  `urluserid` bigint(20) NOT NULL DEFAULT '0',
  `date` datetime DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `domain` varchar(50) DEFAULT NULL,
  `referer` text,
  `browser` text,
  `os` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `url_stats`
--

INSERT INTO `url_stats` (`id`, `short`, `urlid`, `urluserid`, `date`, `ip`, `country`, `domain`, `referer`, `browser`, `os`) VALUES
(1, 'aonFV', 2, 2, '2019-02-28 17:24:42', '::1', '', '', 'direct', 'Chrome', 'Windows 8.1');

-- --------------------------------------------------------

--
-- Table structure for table `url_url`
--

CREATE TABLE `url_url` (
  `id` int(20) NOT NULL,
  `userid` int(16) NOT NULL DEFAULT '0',
  `alias` varchar(10) DEFAULT NULL,
  `custom` varchar(160) DEFAULT NULL,
  `url` text,
  `location` text,
  `devices` text,
  `domain` text,
  `description` text,
  `date` datetime DEFAULT NULL,
  `pass` varchar(255) DEFAULT NULL,
  `click` bigint(20) NOT NULL DEFAULT '0',
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(255) DEFAULT NULL,
  `ads` int(1) NOT NULL DEFAULT '1',
  `bundle` mediumint(9) DEFAULT NULL,
  `public` int(1) NOT NULL DEFAULT '0',
  `archived` int(1) NOT NULL DEFAULT '0',
  `type` varchar(64) DEFAULT NULL,
  `pixels` varchar(255) DEFAULT NULL,
  `expiry` date DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `url_url`
--

INSERT INTO `url_url` (`id`, `userid`, `alias`, `custom`, `url`, `location`, `devices`, `domain`, `description`, `date`, `pass`, `click`, `meta_title`, `meta_description`, `ads`, `bundle`, `public`, `archived`, `type`, `pixels`, `expiry`) VALUES
(1, 0, 'Zce8t', '', 'https://www.youtube.com/watch?v=srPHbcwK0V0', '', '', '', '', '2019-02-28 17:21:41', '', 0, 'Ek Dil | T-Series Acoustics | NEETI MOHAN | Padmaavat | Birthday Special - YouTube', 'We present to you the T-Series Acoustic version of the love songs Ek Dil from the movie &amp;quot;Padmaavat&amp;quot;.Wishing the beautiful and melodi...', 1, NULL, 1, 0, NULL, '', NULL),
(2, 2, 'aonFV', '', 'https://www.grammarly.com/?breadcrumbs=true&page=install', '', '', '', '', '2019-02-28 17:23:24', '', 1, '', '', 1, NULL, 0, 0, NULL, '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `url_user`
--

CREATE TABLE `url_user` (
  `id` int(11) NOT NULL,
  `auth` text,
  `auth_id` varchar(255) DEFAULT NULL,
  `admin` int(1) NOT NULL DEFAULT '0',
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text,
  `date` datetime DEFAULT NULL,
  `api` varchar(255) DEFAULT NULL,
  `ads` int(1) NOT NULL DEFAULT '1',
  `active` int(1) NOT NULL DEFAULT '1',
  `banned` int(1) NOT NULL DEFAULT '0',
  `public` int(1) NOT NULL DEFAULT '0',
  `domain` varchar(255) DEFAULT NULL,
  `media` int(1) NOT NULL DEFAULT '0',
  `splash_opt` int(1) NOT NULL DEFAULT '0',
  `splash` text,
  `auth_key` varchar(255) DEFAULT NULL,
  `last_payment` datetime DEFAULT NULL,
  `expiration` datetime DEFAULT NULL,
  `pro` int(1) NOT NULL DEFAULT '0',
  `overlay` text,
  `fbpixel` varchar(255) DEFAULT NULL,
  `linkedinpixel` varchar(255) DEFAULT NULL,
  `adwordspixel` varchar(255) DEFAULT NULL,
  `defaulttype` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `url_user`
--

INSERT INTO `url_user` (`id`, `auth`, `auth_id`, `admin`, `email`, `name`, `username`, `password`, `address`, `date`, `api`, `ads`, `active`, `banned`, `public`, `domain`, `media`, `splash_opt`, `splash`, `auth_key`, `last_payment`, `expiration`, `pro`, `overlay`, `fbpixel`, `linkedinpixel`, `adwordspixel`, `defaulttype`) VALUES
(1, NULL, NULL, 1, 'ittripathy@gmail.com', NULL, 'indrajeet', '$2a$08$EyIbW5f4dKMRME1inJLXuulQCENGHID8TUlzAEkET.z9pB1gh1vF2', NULL, '2019-02-28 17:21:15', 'ZU6duMevRSPb', 1, 1, 0, 0, NULL, 0, 0, NULL, '$2a$08$xISO3.9ZZti9AqpNAyToMeC8QUhzUDCHP5SdRC.ipCXffIF7EZpJq', '2019-02-28 12:51:15', '2029-02-25 12:51:15', 1, NULL, NULL, NULL, NULL, NULL),
(2, NULL, NULL, 0, 'hackroidbykhan@gmail.com', NULL, '11410085', '$2a$08$HpgPnn747ORwqH2AiftkL.gSbcVXGl6Kiw7zzUsbLtLO/IyCxC5le', NULL, '2019-02-28 17:22:44', 'm7aNs4fnHDSu', 1, 1, 0, 0, NULL, 0, 0, NULL, '$2a$08$b4JkVmhv7G6YmNWj1NZt7.Vod2Q3JcU/jImpKnpisRM6tvvEVYQyO', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `url_bundle`
--
ALTER TABLE `url_bundle`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_page`
--
ALTER TABLE `url_page`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_payment`
--
ALTER TABLE `url_payment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_settings`
--
ALTER TABLE `url_settings`
  ADD PRIMARY KEY (`config`);

--
-- Indexes for table `url_splash`
--
ALTER TABLE `url_splash`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_stats`
--
ALTER TABLE `url_stats`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_url`
--
ALTER TABLE `url_url`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `url_user`
--
ALTER TABLE `url_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api` (`api`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `url_bundle`
--
ALTER TABLE `url_bundle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `url_page`
--
ALTER TABLE `url_page`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `url_payment`
--
ALTER TABLE `url_payment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `url_splash`
--
ALTER TABLE `url_splash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `url_stats`
--
ALTER TABLE `url_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `url_url`
--
ALTER TABLE `url_url`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `url_user`
--
ALTER TABLE `url_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
