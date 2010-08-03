--
-- Table structure for table `#__mxcalendar_categories`
--
CREATE TABLE IF NOT EXISTS `#__mxcalendar_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `isdefault` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(256) NOT NULL,
  `foregroundcss` varchar(256) DEFAULT NULL,
  `backgroundcss` varchar(256) DEFAULT NULL,
  `inlinecss` tinytext,
  `disable` INT NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `#__mxcalendar_categories`
--

INSERT INTO `#__mxcalendar_categories` (`id`, `isdefault`, `name`, `foregroundcss`, `backgroundcss`, `inlinecss`, `disable`, `active`) VALUES
(1, 1, 'General', '', '', '', 0, 1);


--
-- Table structure for table `#__mxcalendar_config`
--

CREATE TABLE IF NOT EXISTS `#__mxcalendar_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `param` varchar(256) NOT NULL,
  `value` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=35 ;


--
-- Dumping data for table `#__mxcalendar_config`
--

INSERT INTO `#__mxcalendar_config` (`id`, `param`, `value`) VALUES
(1, 'disptooltip', '0'),
(2, 'liststyle_limit', '5'),
(3, 'dispduration', '0'),
(4, 'dispeventtime', '0'),
(5, 'enableprevnext', '1'),
(6, 'useajax', '1'),
(7, 'calSMwidth', '125px'),
(8, 'calFULLwidth', '100%'),
(9, 'calstartday', '0'),
(10, 'calweekends', '0'),
(11, 'caltdbordercss', '666666'),
(12, 'caldatestampbgcss', 'CCCCCC'),
(13, 'caldatestamptxtcss', '000000'),
(14, 'GOOGLE_MAPS_HOST', 'maps.google.com'),
(15, 'GOOGLE_MAPS_KEY', ''),
(16, 'eventlist_multiday', '1'),
(17, 'mxcEventDetailId', ''),
(18, 'mxcEventDetailClass', ''),
(19, 'mxCalendarTheme', 'default'),
(20, 'mxcEventDetailBackBtnClass', 'bsCalBack'),
(21, 'mxcCalendearActiveDayClass', 'today'),
(22, 'mxcCalendearActiveDayDisplay', '0'),
(23, 'mxcEventDetailId', ''),
(24, 'mxcEventDetailClass', 'event'),
(25, 'mxcEventDetailLabelDateTime', ''),
(26, 'mxcEventDetailLabelLocation', ''),
(27, 'mgrAddClockTwentryFourHour', '0'),
(28, 'mxcEventDetailLabelHeading', ''),
(29, 'mxcLabelEventListMoreLink', ''),
(30, 'mxcEventDetailBackBtnClass', ''),
(31, 'mxcEventDetailBackBtnTitle', ''),
(32, 'mxcEventListItemId', ''),
(33, 'mxcEventListEventClass', 'event'),
(34, 'mxcEventListLabelLocation', ''),
(35, 'mxcGoogleMapDisplayCanvasID', 'map_canvas'),
(36, 'mxcGoogleMapDisplayWidth', 'auto'),
(37, 'mxcGoogleMapDisplayHeigh', 'auto'),
(38, 'mxcGoogleMapDisplayLngLat', '0'),
(39, 'mxcLocalization', ''),
(40, 'mxcGetCategoryListUIFilterLabel', 'Categories'),
(41, 'mxcGetCategoryListUIFilterLabelTag', 'H3'),
(42, 'mxcGetCategoryListUIFilterLabelTagClass', 'mxcCategoryHeading'),
(43, 'mxcGetCategoryListUIFilterType', 'list'),
(44,'mxcEventListItemMultiDayStyle','font-size:70%'),
(45,'mxcGetCategoryListUIFilterActive','1'),
(46,'mxcEventListItemStateDateStamp','%I:%M %p'),
(47,'mxcEventListItemEndDateStamp','%I:%M %p'),
(48,'mxcAdvancedDateEntry','0');


--
-- Table structure for table `#__mxcalendar_events`
--

CREATE TABLE IF NOT EXISTS `#__mxcalendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `description` text NOT NULL,
  `category` tinyint(3) NOT NULL DEFAULT '1',
  `link` text,
  `linkrel` text,
  `linktarget` text,
  `location` text,
  `displayGoogleMap` tinyint(1) NOT NULL DEFAULT '0',
  `start` datetime NOT NULL,
  `startdate` date NOT NULL,
  `starttime` time NOT NULL,
  `end` datetime NOT NULL,
  `enddate` date NOT NULL,
  `endtime` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `repeat` text,
  `lastrepeat` datetime DEFAULT NULL,
  `event_occurance` varchar(1) DEFAULT NULL,
  `_occurance_wkly` varchar(10) DEFAULT NULL,
  `event_occurance_rep` tinyint(2) DEFAULT NULL,
  `_occurance_properties` varchar(68) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=99 ;

--
-- Table structure for table `#__mxcalendar_pastevents`
--

CREATE TABLE IF NOT EXISTS `#__mxcalendar_pastevents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `description` text NOT NULL,
  `category` tinyint(3) NOT NULL DEFAULT '1',
  `link` text,
  `linkrel` text,
  `linktarget` text,
  `location` text,
  `displayGoogleMap` tinyint(1) NOT NULL DEFAULT '0',
  `start` datetime NOT NULL,
  `startdate` date NOT NULL,
  `starttime` time NOT NULL,
  `end` datetime NOT NULL,
  `enddate` date NOT NULL,
  `endtime` time NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `repeat` text,
  `lastrepeat` datetime DEFAULT NULL,
  `event_occurance` varchar(1) DEFAULT NULL,
  `_occurance_wkly` varchar(10) DEFAULT NULL,
  `event_occurance_rep` tinyint(2) DEFAULT NULL,
  `_occurance_properties` varchar(68) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;