CREATE TABLE IF NOT EXISTS `ss_antispam_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(128) NOT NULL,
  `answers` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;

INSERT INTO `ss_antispam_questions` (`id`, `question`, `answers`) VALUES
(1, 'Podaj <b><u>piątą</u></b> literę alfabetu.', 'e'),
(2, 'Jak nazywa się pierwsza stolica Polski?', 'gniezno'),
(3, 'Na której pozycji znajduje się litera <b><u>n</u></b> w wyrazie narcyz?', '1;pierwsza;pierwszej'),
(4, 'Jakiego koloru jest dolna część polskiej flagi?', 'czerwona;czerwony;czerwonego'),
(5, 'Na której pozycji znajduje się litera <b><u>r</u></b> w wyrazie marchew?', '3;trzeciej;trzecia'),
(6, 'Ile wynosi wynik dodawania (słownie) <b><u>2 + 5</u></b> ?', 'siedem'),
(7, 'Jeden + trzy minus 3 = ? (słownie)', 'jeden'),
(8, 'Jakiego koloru jest pomarańcza?', 'pomarańczowego;pomarańczowy'),
(9, 'Jakiego koloru jest cytryna?', 'żółty;żółtego'),
(10, 'Jakiego koloru jest czarny but?', 'czarnego;czarny');

CREATE TABLE IF NOT EXISTS `ss_bought_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `payment` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `payment_id` varchar(16) NOT NULL,
  `service` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `server` int(11) NOT NULL,
  `amount` varchar(32) NOT NULL,
  `auth_data` varchar(32) NOT NULL,
  `email` varchar(128) NOT NULL,
  `extra_data` varchar(256) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text COLLATE utf8_bin NOT NULL,
  `acp` tinyint(1) NOT NULL DEFAULT '0',
  `manage_settings` tinyint(1) NOT NULL DEFAULT '0',
  `view_groups` tinyint(1) NOT NULL DEFAULT '0',
  `manage_groups` tinyint(1) NOT NULL DEFAULT '0',
  `view_player_flags` tinyint(1) NOT NULL DEFAULT '0',
  `view_user_services` tinyint(1) NOT NULL DEFAULT '0',
  `manage_user_services` tinyint(1) NOT NULL DEFAULT '0',
  `view_income` tinyint(1) NOT NULL DEFAULT '0',
  `view_users` tinyint(1) NOT NULL DEFAULT '0',
  `manage_users` tinyint(1) NOT NULL DEFAULT '0',
  `view_sms_codes` tinyint(1) NOT NULL DEFAULT '0',
  `manage_sms_codes` tinyint(1) NOT NULL DEFAULT '0',
  `view_service_codes` tinyint(1) NOT NULL DEFAULT '0',
  `manage_service_codes` tinyint(1) NOT NULL DEFAULT '0',
  `view_antispam_questions` tinyint(1) NOT NULL DEFAULT '0',
  `manage_antispam_questions` tinyint(1) NOT NULL DEFAULT '0',
  `view_services` tinyint(1) NOT NULL DEFAULT '0',
  `manage_services` tinyint(1) NOT NULL DEFAULT '0',
  `view_servers` tinyint(1) NOT NULL DEFAULT '0',
  `manage_servers` tinyint(1) NOT NULL DEFAULT '0',
  `view_logs` tinyint(1) NOT NULL DEFAULT '0',
  `manage_logs` tinyint(1) NOT NULL DEFAULT '0',
  `update` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `gid` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;

INSERT INTO `ss_groups` (`id`, `name`, `acp`, `manage_settings`, `view_groups`, `manage_groups`, `view_player_flags`, `view_user_services`, `manage_user_services`, `view_income`, `view_users`, `manage_users`, `view_sms_codes`, `manage_sms_codes`, `view_service_codes`, `manage_service_codes`, `view_antispam_questions`, `manage_antispam_questions`, `view_services`, `manage_services`, `view_servers`, `manage_servers`, `view_logs`, `manage_logs`, `update`) VALUES
(1, 'Użytkownik', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(2, 'Właściciel', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

CREATE TABLE IF NOT EXISTS `ss_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_mybb_user_group` (
  `uid` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `expire` timestamp NULL DEFAULT NULL,
  `was_before` tinyint(4) NOT NULL,
  PRIMARY KEY (`uid`,`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `ss_payment_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `platform` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `aid` (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_payment_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `platform` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_payment_sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) CHARACTER SET utf8 NOT NULL,
  `income` int(11) NOT NULL,
  `cost` int(11) NOT NULL,
  `text` varchar(32) CHARACTER SET utf8 NOT NULL,
  `number` varchar(16) CHARACTER SET utf8 NOT NULL,
  `ip` varchar(16) CHARACTER SET utf8 NOT NULL,
  `platform` varchar(128) CHARACTER SET utf8 NOT NULL,
  `free` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_payment_transfer` (
  `id` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `income` int(11) NOT NULL,
  `transfer_service` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `ip` varchar(16) NOT NULL,
  `platform` varchar(128) NOT NULL,
  UNIQUE KEY `orderid` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ss_payment_wallet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cost` int(11) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `platform` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_players_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` int(11) NOT NULL,
  `type` int(11) NOT NULL DEFAULT '0',
  `auth_data` varchar(32) NOT NULL,
  `password` varchar(34) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `a` int(11) NOT NULL DEFAULT '0',
  `b` int(11) NOT NULL DEFAULT '0',
  `c` int(11) NOT NULL DEFAULT '0',
  `d` int(11) NOT NULL DEFAULT '0',
  `e` int(11) NOT NULL DEFAULT '0',
  `f` int(11) NOT NULL DEFAULT '0',
  `g` int(11) NOT NULL DEFAULT '0',
  `h` int(11) NOT NULL DEFAULT '0',
  `i` int(11) NOT NULL DEFAULT '0',
  `j` int(11) NOT NULL DEFAULT '0',
  `k` int(11) NOT NULL DEFAULT '0',
  `l` int(11) NOT NULL DEFAULT '0',
  `m` int(11) NOT NULL DEFAULT '0',
  `n` int(11) NOT NULL DEFAULT '0',
  `o` int(11) NOT NULL DEFAULT '0',
  `p` int(11) NOT NULL DEFAULT '0',
  `q` int(11) NOT NULL DEFAULT '0',
  `r` int(11) NOT NULL DEFAULT '0',
  `s` int(11) NOT NULL DEFAULT '0',
  `t` int(11) NOT NULL DEFAULT '0',
  `u` int(11) NOT NULL DEFAULT '0',
  `y` int(11) NOT NULL DEFAULT '0',
  `v` int(11) NOT NULL DEFAULT '0',
  `w` int(11) NOT NULL DEFAULT '0',
  `x` int(11) NOT NULL DEFAULT '0',
  `z` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `server+type+player` (`server`,`type`,`auth_data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_pricelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `tariff` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `server` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `price` (`service`,`tariff`,`server`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `port` varchar(8) NOT NULL,
  `sms_service` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `type` varchar(16) NOT NULL DEFAULT '',
  `version` varchar(8) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_servers_services` (
  `server_id` int(11) NOT NULL,
  `service_id` varchar(16) COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `ss` (`server_id`,`service_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `ss_services` (
  `id` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` varchar(32) NOT NULL,
  `short_description` varchar(28) NOT NULL,
  `description` text NOT NULL,
  `types` int(11) NOT NULL DEFAULT '0',
  `tag` varchar(16) NOT NULL,
  `module` varchar(32) NOT NULL DEFAULT '',
  `groups` text NOT NULL,
  `flags` varchar(25) NOT NULL DEFAULT '',
  `order` int(4) NOT NULL DEFAULT '1',
  `data` text NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ss_services` (`id`, `name`, `short_description`, `description`, `types`, `tag`, `module`, `groups`, `flags`, `order`, `data`) VALUES
('bf2_badge', 'Odznaki', '', '', 0, 'Poziom', 'other', '', '', 8, ''),
('charge_wallet', 'Doładowanie Portfela', '', '<strong>Doładowanie Portfela</strong> pozwala zwiększyć stan wirtualnej gotówki w celu dokonywania przyszłych zakupów.', 0, '', 'charge_wallet', '', '', 0, ''),
('cod_exp', 'Doswiadczenie', '', '', 0, 'EXP', 'other', '', '', 5, ''),
('cod_exp_transfer', 'Przeniesienie EXPa', '', '', 0, 'Przeniesienie', 'other', '', '', 6, ''),
('goresnick', 'GO Rezerwacja Nicku', '', '<strong>Rezerwacja Nicku</strong> zabezpiecza Twój nick, aby nikt inny nie mógł na nim grać!', 1, 'dni', 'extra_flags', '', 'o', 7, '{"web":"1"}'),
('goresslot', 'GO Rezerwacja Slota', '', '<strong>Rezerwacja Slota</strong> pozwala na wejście na serwer bez czekania na wolny slot!', 4, 'dni', 'extra_flags', '', 'a', 8, '{"web":"1"}'),
('govip', 'GO VIP', '', '<strong>VIP</strong> to specjalne bonusy dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP może się nieco różnić w zależności typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.', 4, 'dni', 'extra_flags', '', 'at', 5, '{"web":"1"}'),
('govippro', 'GO VIP PRO', '', '<strong>VIP PRO</strong> to jeszcze więcej specjalnych bonusów dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP PRO może się nieco różnić w zależności od typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.', 4, 'dni', 'extra_flags', '', 'ats', 6, '{"web":"1"}'),
('gxm_bm', 'Bezlitosne Monety', '', '', 0, 'BM', 'other', '', '', 6, ''),
('gxm_exp', 'Doswiadczenie', '', '', 0, 'EXP', 'other', '', '', 5, ''),
('resnick', 'Rezerwacja Nicku', '', '<strong>Rezerwacja Nicku</strong> zabezpiecza Twój nick, aby nikt inny nie mógł na nim grać!', 1, 'dni', 'extra_flags', '', 'z', 3, '{"web": "1"}'),
('resslot', 'Rezerwacja Slota', '', '<strong>Rezerwacja Slota</strong> pozwala na wejście na serwer bez czekania na wolny slot!', 7, 'dni', 'extra_flags', '', 'b', 4, '{"web": "1"}'),
('vip', 'VIP', '', '<strong>VIP</strong> to specjalne bonusy dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP może się nieco różnić w zależności typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.', 7, 'dni', 'extra_flags', '', 't', 1, '{"web": "1"}'),
('vippro', 'VIP PRO', '', '<strong>VIP PRO</strong> to jeszcze więcej specjalnych bonusów dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP PRO może się nieco różnić w zależności od typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.', 7, 'dni', 'extra_flags', '', 'btx', 2, '{"web": "1"}'),
('zp_ap', 'Ammo Packs', '', '', 0, 'AP', 'other', '', '', 7, '');

CREATE TABLE IF NOT EXISTS `ss_service_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) NOT NULL,
  `service` varchar(16) NOT NULL,
  `server` int(11) NOT NULL DEFAULT '0',
  `tariff` int(11) NOT NULL DEFAULT '0',
  `uid` int(11) NOT NULL DEFAULT '0',
  `amount` double NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_settings` (
  `key` varchar(128) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `value` varchar(256) NOT NULL,
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ss_settings` (`key`, `value`) VALUES
('contact', ''),
('cron_each_visit', '0'),
('currency', 'PLN'),
('date_format', 'Y-m-d H:i'),
('delete_logs', '0'),
('google_analytics', ''),
('language', 'polish'),
('license_login', ''),
('license_password', ''),
('random_key', ''),
('row_limit', '30'),
('sender_email', ''),
('sender_email_name', ''),
('shop_url', ''),
('signature', ''),
('sms_service', ''),
('theme', 'default'),
('timezone', 'Europe/Warsaw'),
('transfer_service', ''),
('user_edit_service', '1'),
('vat', '1.23');

CREATE TABLE IF NOT EXISTS `ss_sms_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(16) CHARACTER SET utf8 NOT NULL,
  `tariff` int(11) NOT NULL,
  `free` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_sms_numbers` (
  `number` varchar(16) NOT NULL,
  `tariff` int(11) NOT NULL,
  `service` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `numbertextservice` (`number`,`service`),
  KEY `tariff` (`tariff`),
  KEY `service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
('7136', 1, 'microsms'),
('7136', 1, 'mintshost'),
('7136', 1, 'profitsms'),
('7136', 1, 'simpay'),
('71480', 1, 'amxx'),
('71480', 1, 'cashbill'),
('71480', 1, 'pukawka'),
('71480', 1, 'zabijaka'),
('7155', 1, 'homepay'),
('71624', 1, 'cssetti'),
('7169', 1, '1s1k'),
('72480', 2, 'amxx'),
('72480', 2, 'cashbill'),
('72480', 2, 'pukawka'),
('72480', 2, 'zabijaka'),
('7255', 2, 'homepay'),
('7255', 2, 'microsms'),
('7255', 2, 'mintshost'),
('7255', 2, 'profitsms'),
('7255', 2, 'simpay'),
('72550', 2, '1s1k'),
('72624', 2, 'cssetti'),
('73480', 3, 'amxx'),
('73480', 3, 'cashbill'),
('73480', 3, 'pukawka'),
('73480', 3, 'zabijaka'),
('7355', 3, 'homepay'),
('7355', 3, 'microsms'),
('7355', 3, 'mintshost'),
('7355', 3, 'profitsms'),
('7355', 3, 'simpay'),
('73550', 3, '1s1k'),
('73624', 3, 'cssetti'),
('74480', 4, 'amxx'),
('74480', 4, 'cashbill'),
('74480', 4, 'pukawka'),
('74480', 4, 'zabijaka'),
('7455', 4, 'homepay'),
('7455', 4, 'microsms'),
('7455', 4, 'mintshost'),
('7455', 4, 'profitsms'),
('7455', 4, 'simpay'),
('74550', 4, '1s1k'),
('74624', 4, 'cssetti'),
('75480', 5, 'amxx'),
('75480', 5, 'cashbill'),
('75480', 5, 'pukawka'),
('75480', 5, 'zabijaka'),
('7555', 5, 'homepay'),
('7555', 5, 'microsms'),
('7555', 5, 'mintshost'),
('7555', 5, 'profitsms'),
('7555', 5, 'simpay'),
('75550', 5, '1s1k'),
('75624', 5, 'cssetti'),
('7636', 6, 'microsms'),
('7636', 6, 'mintshost'),
('7636', 6, 'profitsms'),
('7636', 6, 'simpay'),
('76480', 6, 'amxx'),
('76480', 6, 'cashbill'),
('76480', 6, 'pukawka'),
('76480', 6, 'zabijaka'),
('76550', 6, '1s1k'),
('76624', 6, 'cssetti'),
('76660', 6, 'homepay'),
('77464', 7, 'cssetti'),
('77464', 7, 'microsms'),
('77464', 7, 'simpay'),
('78464', 8, 'cssetti'),
('78464', 8, 'microsms'),
('78464', 8, 'simpay'),
('7936', 9, 'microsms'),
('7936', 9, 'mintshost'),
('7936', 9, 'profitsms'),
('7936', 9, 'simpay'),
('79480', 9, 'amxx'),
('79480', 9, 'cashbill'),
('79480', 9, 'pukawka'),
('79480', 9, 'zabijaka'),
('7955', 9, 'homepay'),
('79550', 9, '1s1k'),
('79624', 9, 'cssetti'),
('91055', 10, 'homepay'),
('91055', 10, 'microsms'),
('91055', 10, 'mintshost'),
('91055', 10, 'simpay'),
('91155', 11, 'homepay'),
('91155', 11, 'microsms'),
('91155', 11, 'simpay'),
('91400', 14, 'amxx'),
('91400', 14, 'cashbill'),
('91400', 14, 'pukawka'),
('91400', 14, 'zabijaka'),
('91455', 14, 'cssetti'),
('91455', 14, 'homepay'),
('91455', 14, 'microsms'),
('91455', 14, 'mintshost'),
('91455', 14, 'profitsms'),
('91455', 14, 'simpay'),
('91664', 16, 'microsms'),
('91664', 16, 'simpay'),
('91900', 19, 'amxx'),
('91900', 19, 'cashbill'),
('91900', 19, 'pukawka'),
('91900', 19, 'zabijaka'),
('91955', 19, 'homepay'),
('91955', 19, 'microsms'),
('91955', 19, 'mintshost'),
('91955', 19, 'profitsms'),
('91955', 19, 'simpay'),
('91974', 19, 'cssetti'),
('91986', 19, '1s1k'),
('92022', 20, 'cashbill'),
('92055', 20, 'homepay'),
('92055', 20, 'microsms'),
('92055', 20, 'simpay'),
('92520', 25, 'homepay'),
('92550', 25, 'amxx'),
('92550', 25, 'cashbill'),
('92550', 25, 'pukawka'),
('92550', 25, 'zabijaka'),
('92555', 25, 'microsms'),
('92555', 25, 'mintshost'),
('92555', 25, 'profitsms'),
('92555', 25, 'simpay'),
('92574', 25, 'cssetti'),
('92596', 25, '1s1k'),
('7055', 26, 'cssetti'),
('7055', 26, 'homepay'),
('7055', 26, 'microsms'),
('7055', 26, 'profitsms'),
('7055', 26, 'simpay'),
('70567', 26, 'cashbill'),
('70567', 26, 'pukawka');

CREATE TABLE IF NOT EXISTS `ss_tariffs` (
  `tariff` int(11) NOT NULL,
  `provision` int(11) NOT NULL DEFAULT '0',
  `predefined` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `tariff` (`tariff`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `ss_tariffs` (`tariff`, `provision`, `predefined`) VALUES
(1, 70, 1),
(2, 140, 1),
(3, 210, 1),
(4, 280, 1),
(5, 350, 1),
(6, 420, 1),
(7, 490, 1),
(8, 560, 1),
(9, 630, 1),
(10, 700, 1),
(11, 770, 1),
(14, 980, 1),
(16, 1120, 1),
(19, 1330, 1),
(20, 1400, 1),
(25, 1750, 1),
(26, 35, 1);

CREATE TABLE IF NOT EXISTS `ss_transaction_services` (
  `id` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `name` varchar(32) NOT NULL,
  `data` varchar(512) NOT NULL,
  `data_hidden` varchar(256) NOT NULL,
  `sms` tinyint(1) NOT NULL DEFAULT '0',
  `transfer` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
('1s1k', '1shot 1kill', '{"api":"","sms_text":"SHOT"}', '', 1, 0),
('amxx', 'AMXX', '{"account_id":"","sms_text":"AG MSAMXX"}', '', 1, 0),
('cashbill', 'CashBill', '{"service":"","key":"","sms_text":""}', '', 1, 1),
('cssetti', 'CSSetti', '{"account_id":"","sms_text":"DP CSSETTI"}', '', 1, 0),
('homepay', 'HomePay', '{"api":"","sms_text":"","7055":"","7155":"","7255":"","7355":"","7455":"","7555":"","76660":"","7955":"","91055":"","91155":"","91455":"","91955":"","92055":"","92520":""}', '', 1, 0),
('microsms', 'MicroSMS', '{"api":"","sms_text":"","service_id":""}', '', 1, 0),
('mintshost', 'MintsHost', '{"email":"","sms_text":"KDW.MINTS"}', '', 1, 0),
('profitsms', 'Profit SMS', '{"api":"","sms_text":""}', '', 1, 0),
('pukawka', 'Pukawka', '{"api":"","sms_text":"PUKAWKA"}', '', 1, 0),
('simpay', 'SimPay', '{"sms_text":"","key":"","secret":"","service_id":""}', '', 1, 0),
('transferuj', 'Transferuj', '{"account_id":"","key":""}', '', 0, 1),
('zabijaka', 'Zabijaka', '{"api":"","sms_text":"AG.ZABIJAKA"}', '', 1, 0);

CREATE TABLE IF NOT EXISTS `ss_users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) CHARACTER SET utf8 NOT NULL,
  `password` varchar(128) CHARACTER SET utf8 NOT NULL,
  `salt` varchar(8) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `email` varchar(128) CHARACTER SET utf8 NOT NULL,
  `forename` varchar(32) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `surname` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `groups` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1',
  `regdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastactiv` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `wallet` int(11) NOT NULL DEFAULT '0',
  `regip` varchar(16) NOT NULL,
  `lastip` varchar(16) NOT NULL,
  `reset_password_key` varchar(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `emial` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ss_user_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(16) COLLATE utf8_bin NOT NULL,
  `uid` int(11) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `service` (`service`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2777 ;

CREATE TABLE IF NOT EXISTS `ss_user_service_extra_flags` (
  `us_id` int(11) NOT NULL,
  `service` varchar(16) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `server` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `auth_data` varchar(64) NOT NULL,
  `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `server` (`server`,`service`,`type`,`auth_data`),
  KEY `service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ss_user_service_mybb_extra_groups` (
  `us_id` int(11) NOT NULL,
  `service` varchar(16) COLLATE utf8_bin NOT NULL,
  `mybb_uid` int(11) NOT NULL,
  UNIQUE KEY `user_service` (`us_id`),
  UNIQUE KEY `service` (`service`,`mybb_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


ALTER TABLE `ss_payment_admin`
  ADD CONSTRAINT `ss_payment_admin_ibfk_1` FOREIGN KEY (`aid`) REFERENCES `ss_users` (`uid`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `ss_pricelist`
  ADD CONSTRAINT `ss_pricelist_ibfk_1` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ss_servers_services`
  ADD CONSTRAINT `ss_servers_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_servers_services_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ss_sms_numbers`
  ADD CONSTRAINT `ss_sms_numbers_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_transaction_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_sms_numbers_ibfk_1` FOREIGN KEY (`tariff`) REFERENCES `ss_tariffs` (`tariff`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `ss_user_service`
  ADD CONSTRAINT `ss_user_service_ibfk_1` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`) ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_extra_flags`
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_3` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_mybb_extra_groups`
  ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`) ON UPDATE CASCADE;
