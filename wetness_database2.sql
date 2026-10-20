-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2026-10-20 14:52:48
-- 伺服器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `wetness_database2`
--

-- --------------------------------------------------------

--
-- 資料表結構 `a1111122_event`
--

CREATE TABLE `a1111122_event` (
  `id` int(11) NOT NULL,
  `event_time` datetime NOT NULL DEFAULT current_timestamp() COMMENT '事件發生時間',
  `event_type` varchar(50) NOT NULL COMMENT '事件類型',
  `description` text DEFAULT NULL COMMENT '事件詳細說明'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `a1111122_valuehistory`
--

CREATE TABLE `a1111122_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `a987654321_valuehistory`
--

CREATE TABLE `a987654321_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `b148523000_valuehistory`
--

CREATE TABLE `b148523000_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `correspondhistory`
--

CREATE TABLE `correspondhistory` (
  `num` int(100) NOT NULL,
  `id` varchar(10) DEFAULT NULL,
  `mac_address` varchar(20) NOT NULL,
  `IDP` varchar(20) NOT NULL,
  `begin` datetime NOT NULL,
  `end` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `diaper inventory`
--

CREATE TABLE `diaper inventory` (
  `id` int(11) NOT NULL,
  `IDP` varchar(10) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `discharged_patient`
--

CREATE TABLE `discharged_patient` (
  `discharge_date` date NOT NULL,
  `IDP` varchar(10) NOT NULL,
  `Password` varchar(16) NOT NULL,
  `Admission_date` datetime NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Blood` varchar(2) NOT NULL,
  `Birthday` date NOT NULL,
  `QRcode` text NOT NULL,
  `sex` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `export_tasks`
--

CREATE TABLE `export_tasks` (
  `task_id` int(11) NOT NULL,
  `patient_id` varchar(50) NOT NULL COMMENT '病患 IDP',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending' COMMENT '任務狀態',
  `progress` int(11) DEFAULT 0 COMMENT '目前進度 0-100',
  `file_path` varchar(255) DEFAULT NULL COMMENT '產出的 SQL 檔案下載路徑',
  `error_message` text DEFAULT NULL COMMENT '失敗時記錄錯誤原因',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `g360002854_valuehistory`
--

CREATE TABLE `g360002854_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `h250014369_valuehistory`
--

CREATE TABLE `h250014369_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `h631592714_valuehistory`
--

CREATE TABLE `h631592714_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `hub`
--

CREATE TABLE `hub` (
  `id` int(11) NOT NULL,
  `hub_name` varchar(50) NOT NULL COMMENT 'HUB名稱',
  `ip_address` varchar(50) DEFAULT NULL COMMENT 'IP位址',
  `last_update_time` datetime NOT NULL COMMENT '最後連線時間',
  `alert_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '警示開關(1=開, 0=關)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `m252631234_valuehistory`
--

CREATE TABLE `m252631234_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `memberlogin`
--

CREATE TABLE `memberlogin` (
  `IDP` varchar(10) NOT NULL,
  `Password` varchar(16) NOT NULL,
  `Admission_date` datetime NOT NULL,
  `Name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `Blood` varchar(2) NOT NULL,
  `Birthday` date NOT NULL,
  `QRcode` text DEFAULT NULL,
  `sex` int(1) NOT NULL COMMENT '0是女 1是男'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `nurses_account`
--

CREATE TABLE `nurses_account` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL COMMENT '編號',
  `name` varchar(50) NOT NULL COMMENT '姓名',
  `username` varchar(50) NOT NULL COMMENT '帳號',
  `password` varchar(255) NOT NULL COMMENT '密碼',
  `role` varchar(10) NOT NULL COMMENT '權限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `q123456789_valuehistory`
--

CREATE TABLE `q123456789_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `server_ip`
--

CREATE TABLE `server_ip` (
  `id` int(11) NOT NULL,
  `local_ip` varchar(255) NOT NULL,
  `trans_ip` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `tag`
--

CREATE TABLE `tag` (
  `tagid` int(11) NOT NULL,
  `tagmac` text NOT NULL,
  `tagvalue` text DEFAULT NULL,
  `rssi` text NOT NULL,
  `owner` text DEFAULT NULL COMMENT 'IDP',
  `lastPID` text DEFAULT NULL,
  `multple` tinyint(1) DEFAULT 0,
  `hubnum` varchar(99) DEFAULT NULL,
  `time` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `valuehistory`
--

CREATE TABLE `valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `valuehistory_import`
--

CREATE TABLE `valuehistory_import` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `valueimmediate`
--

CREATE TABLE `valueimmediate` (
  `id` varchar(20) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL,
  `bodytemp` float NOT NULL,
  `roomtemp` float NOT NULL,
  `breath` int(10) NOT NULL,
  `SpitMilk` tinyint(1) NOT NULL,
  `LeaveBed` tinyint(1) NOT NULL,
  `GetUp` tinyint(1) NOT NULL,
  `time` datetime NOT NULL,
  `hubnum` varchar(99) NOT NULL,
  `power` int(3) DEFAULT NULL,
  `x_data` float DEFAULT NULL,
  `y_data` float DEFAULT NULL,
  `z_data` float DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `warddata`
--

CREATE TABLE `warddata` (
  `ward` varchar(5) NOT NULL,
  `bed` varchar(2) NOT NULL,
  `IDP` varchar(10) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `warning_standard`
--

CREATE TABLE `warning_standard` (
  `id` int(1) NOT NULL,
  `wet_top` varchar(3) NOT NULL,
  `temp_top` varchar(5) NOT NULL,
  `temp_bot` varchar(5) NOT NULL,
  `high_pressure_top` varchar(3) NOT NULL,
  `high_pressure_bot` varchar(3) NOT NULL,
  `low_pressure_top` varchar(3) NOT NULL,
  `low_pressure_bot` varchar(3) NOT NULL,
  `breath_top` varchar(3) NOT NULL,
  `breath_bot` varchar(3) NOT NULL,
  `heartbeat_top` int(11) NOT NULL,
  `heartbeat_bot` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `z123456789_event`
--

CREATE TABLE `z123456789_event` (
  `id` int(11) NOT NULL,
  `event_time` datetime NOT NULL DEFAULT current_timestamp() COMMENT '事件發生時間',
  `event_type` varchar(50) NOT NULL COMMENT '事件類型',
  `description` text DEFAULT NULL COMMENT '事件詳細說明'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `z123456789_valuehistory`
--

CREATE TABLE `z123456789_valuehistory` (
  `id` varchar(10) NOT NULL,
  `mac_address` varchar(20) NOT NULL,
  `data` varchar(200) NOT NULL,
  `wet` varchar(10) NOT NULL COMMENT '濕度',
  `bodytemp` float NOT NULL COMMENT '病人體溫',
  `roomtemp` float NOT NULL COMMENT '室溫',
  `breath` int(10) NOT NULL COMMENT '呼吸頻率',
  `SpitMilk` tinyint(1) NOT NULL COMMENT '吐奶',
  `LeaveBed` tinyint(1) NOT NULL COMMENT '離床',
  `GetUp` tinyint(1) NOT NULL COMMENT '睡醒',
  `time` datetime NOT NULL COMMENT '手機端收到藍芽封包時間',
  `hubnum` varchar(99) NOT NULL COMMENT '上傳的hubName',
  `power` int(3) DEFAULT NULL COMMENT '電量',
  `rssi` int(10) NOT NULL,
  `hubtime` datetime DEFAULT NULL COMMENT 'hub上傳時間',
  `x_data` varchar(50) DEFAULT NULL,
  `y_data` varchar(50) DEFAULT NULL,
  `z_data` varchar(50) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `a1111122_event`
--
ALTER TABLE `a1111122_event`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `a1111122_valuehistory`
--
ALTER TABLE `a1111122_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `a987654321_valuehistory`
--
ALTER TABLE `a987654321_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `b148523000_valuehistory`
--
ALTER TABLE `b148523000_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `correspondhistory`
--
ALTER TABLE `correspondhistory`
  ADD PRIMARY KEY (`num`);

--
-- 資料表索引 `diaper inventory`
--
ALTER TABLE `diaper inventory`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `discharged_patient`
--
ALTER TABLE `discharged_patient`
  ADD PRIMARY KEY (`IDP`,`discharge_date`) USING BTREE;

--
-- 資料表索引 `export_tasks`
--
ALTER TABLE `export_tasks`
  ADD PRIMARY KEY (`task_id`);

--
-- 資料表索引 `g360002854_valuehistory`
--
ALTER TABLE `g360002854_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `h250014369_valuehistory`
--
ALTER TABLE `h250014369_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `h631592714_valuehistory`
--
ALTER TABLE `h631592714_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `hub`
--
ALTER TABLE `hub`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hub_name` (`hub_name`);

--
-- 資料表索引 `m252631234_valuehistory`
--
ALTER TABLE `m252631234_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `memberlogin`
--
ALTER TABLE `memberlogin`
  ADD PRIMARY KEY (`IDP`);

--
-- 資料表索引 `nurses_account`
--
ALTER TABLE `nurses_account`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `emp_id` (`emp_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 資料表索引 `q123456789_valuehistory`
--
ALTER TABLE `q123456789_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `server_ip`
--
ALTER TABLE `server_ip`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`tagid`);

--
-- 資料表索引 `valuehistory`
--
ALTER TABLE `valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `valuehistory_import`
--
ALTER TABLE `valuehistory_import`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 資料表索引 `valueimmediate`
--
ALTER TABLE `valueimmediate`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `warddata`
--
ALTER TABLE `warddata`
  ADD PRIMARY KEY (`ward`,`bed`);

--
-- 資料表索引 `warning_standard`
--
ALTER TABLE `warning_standard`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `z123456789_event`
--
ALTER TABLE `z123456789_event`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `z123456789_valuehistory`
--
ALTER TABLE `z123456789_valuehistory`
  ADD KEY `time` (`time`),
  ADD KEY `id` (`id`,`mac_address`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `a1111122_event`
--
ALTER TABLE `a1111122_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `correspondhistory`
--
ALTER TABLE `correspondhistory`
  MODIFY `num` int(100) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `diaper inventory`
--
ALTER TABLE `diaper inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `export_tasks`
--
ALTER TABLE `export_tasks`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `hub`
--
ALTER TABLE `hub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `nurses_account`
--
ALTER TABLE `nurses_account`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `server_ip`
--
ALTER TABLE `server_ip`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `tag`
--
ALTER TABLE `tag`
  MODIFY `tagid` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `warning_standard`
--
ALTER TABLE `warning_standard`
  MODIFY `id` int(1) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `z123456789_event`
--
ALTER TABLE `z123456789_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
