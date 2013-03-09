-- phpMyAdmin SQL Dump
-- version 3.4.5
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2013 年 03 月 06 日 02:34
-- 服务器版本: 5.5.16
-- PHP 版本: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `cdn_info`
--

-- --------------------------------------------------------

--
-- 表的结构 `conf`
--

CREATE TABLE IF NOT EXISTS `conf` (
  `conf_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '模板名字',
  `rule` varchar(255) NOT NULL COMMENT '缓存资源规则',
  `cache` smallint(6) NOT NULL DEFAULT '0' COMMENT '如果为0则不缓存，如果为数字则为缓存天数',
  `weight` int(10) unsigned NOT NULL DEFAULT '0',
  `rule_type` enum('dir','file','reg') NOT NULL DEFAULT 'dir',
  `domain_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`conf_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `config_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- 转存表中的数据 `config`
--

INSERT INTO `config` (`config_id`, `name`, `value`) VALUES
(1, 'reg', 'true'),
(2, 'restart', 'true');

-- --------------------------------------------------------

--
-- 表的结构 `domain`
--

CREATE TABLE IF NOT EXISTS `domain` (
  `domain_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `domain_name` varchar(255) NOT NULL COMMENT '域名',
  `source_ip` varchar(255) NOT NULL COMMENT '源站IP',
  `source_port` int(10) unsigned NOT NULL DEFAULT '80',
  `last_update_time` int(10) unsigned NOT NULL,
  `dnspod` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '是否可以通过dnspod操作,是的话存域名ID，不能的话NULL',
  `cname_domain` varchar(255) NOT NULL,
  `cname_ip` varchar(255) NOT NULL,
  `token` char(32) NOT NULL,
  `status` enum('cname','invalid','a') NOT NULL DEFAULT 'invalid',
  `a_ip` varchar(255) NOT NULL,
  PRIMARY KEY (`domain_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `node_info`
--

CREATE TABLE IF NOT EXISTS `node_info` (
  `node_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node_access` enum('allow','deny') NOT NULL DEFAULT 'allow',
  `node_name` varchar(255) NOT NULL DEFAULT '未命名节点',
  `NodeIP` varchar(20) DEFAULT NULL,
  `Status` varchar(10) DEFAULT NULL,
  `IP0` varchar(20) DEFAULT NULL,
  `Netmask0` varchar(20) DEFAULT NULL,
  `IP1` varchar(20) DEFAULT NULL,
  `Netmask1` varchar(20) DEFAULT NULL,
  `System_release` varchar(100) DEFAULT NULL,
  `Kernel_release` varchar(100) DEFAULT NULL,
  `Frequency` varchar(100) DEFAULT NULL,
  `CPU_cores` varchar(100) DEFAULT NULL,
  `Mem_total` varchar(10) DEFAULT NULL,
  `Swap_total` varchar(10) DEFAULT NULL,
  `Disk_total` varchar(10) DEFAULT NULL,
  `cpu` varchar(20) DEFAULT NULL,
  `cpu_free` varchar(20) DEFAULT NULL,
  `eth0` varchar(10) DEFAULT NULL,
  `send_rate0` varchar(20) DEFAULT NULL,
  `recv_rate0` varchar(20) DEFAULT NULL,
  `send_all_rate0` varchar(20) DEFAULT NULL,
  `recv_all_rate0` varchar(20) DEFAULT NULL,
  `eth1` varchar(10) DEFAULT NULL,
  `send_rate1` varchar(20) DEFAULT NULL,
  `recv_rate1` varchar(20) DEFAULT NULL,
  `send_all_rate1` varchar(20) DEFAULT NULL,
  `recv_all_rate1` varchar(20) DEFAULT NULL,
  `Mem_used` varchar(10) DEFAULT NULL,
  `Mem_free` varchar(10) DEFAULT NULL,
  `Mem_per` varchar(10) DEFAULT NULL,
  `Swap_used` varchar(10) DEFAULT NULL,
  `Swap_free` varchar(10) DEFAULT NULL,
  `Swap_per` varchar(10) DEFAULT NULL,
  `Disk_used` varchar(10) DEFAULT NULL,
  `Disk_free` varchar(10) DEFAULT NULL,
  `Disk_per` varchar(10) DEFAULT NULL,
  `cache` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`node_id`),
  UNIQUE KEY `NodeIP` (`NodeIP`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mail` varchar(255) NOT NULL,
  `mail_verify` enum('true','false') NOT NULL DEFAULT 'false',
  `passwd` char(32) NOT NULL,
  `salt` char(25) NOT NULL,
  `dnspod_user` varchar(255) DEFAULT NULL,
  `dnspod_pass` varchar(255) DEFAULT NULL,
  `dnspod` enum('valid','invalid') NOT NULL DEFAULT 'invalid',
  `change_mail` varchar(255) NOT NULL,
  `change_token` char(32) NOT NULL,
  `auth` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`user_id`, `mail`, `mail_verify`, `passwd`, `salt`, `dnspod_user`, `dnspod_pass`, `dnspod`, `change_mail`, `change_token`, `auth`) VALUES
(1, 'admin@ocdn.me', 'false', '08f8702e03b2ad3c3616999378253aad', '8PI0dbWDvB88YsQHctIhjx3px', NULL, NULL, 'invalid', '', '', 1);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
