DROP TABLE IF EXISTS `ss_payment_admin`;
CREATE TABLE IF NOT EXISTS `ss_payment_admin` (
  `id`       INT(11)     NOT NULL AUTO_INCREMENT,
  `aid`      INT(11)     NOT NULL,
  `ip`       VARCHAR(16) NOT NULL DEFAULT '',
  `platform` TEXT        NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `aid` (`aid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS `ss_users`;
CREATE TABLE IF NOT EXISTS `ss_users` (
  `uid`                INT(11)            NOT NULL AUTO_INCREMENT,
  `username`           VARCHAR(64)
                       CHARACTER SET utf8 NOT NULL,
  `password`           VARCHAR(128)
                       CHARACTER SET utf8 NOT NULL,
  `salt`               VARCHAR(8)
                       CHARACTER SET utf8
                       COLLATE utf8_bin   NOT NULL,
  `email`              VARCHAR(128)
                       CHARACTER SET utf8 NOT NULL DEFAULT '',
  `forename`           VARCHAR(32)
                       CHARACTER SET utf8 NOT NULL DEFAULT '',
  `surname`            VARCHAR(64)
                       CHARACTER SET utf8 NOT NULL DEFAULT '',
  `groups`             VARCHAR(32)
                       CHARACTER SET utf8
                       COLLATE utf8_bin   NOT NULL DEFAULT '1',
  `regdate`            TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastactiv`          TIMESTAMP          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `wallet`             INT(11)            NOT NULL DEFAULT '0',
  `regip`              VARCHAR(64)        NOT NULL DEFAULT '',
  `lastip`             VARCHAR(64)        NOT NULL DEFAULT '',
  `reset_password_key` VARCHAR(32)
                       CHARACTER SET utf8
                       COLLATE utf8_bin   NOT NULL DEFAULT '',
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `emial` (`email`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_groups`;
CREATE TABLE IF NOT EXISTS `ss_groups` (
  `id`                        INT(11)          NOT NULL AUTO_INCREMENT,
  `name`                      TEXT
                              COLLATE utf8_bin NOT NULL,
  `acp`                       TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_settings`           TINYINT(1)       NOT NULL DEFAULT '0',
  `view_groups`               TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_groups`             TINYINT(1)       NOT NULL DEFAULT '0',
  `view_player_flags`         TINYINT(1)       NOT NULL DEFAULT '0',
  `view_user_services`        TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_user_services`      TINYINT(1)       NOT NULL DEFAULT '0',
  `view_income`               TINYINT(1)       NOT NULL DEFAULT '0',
  `view_users`                TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_users`              TINYINT(1)       NOT NULL DEFAULT '0',
  `view_sms_codes`            TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_sms_codes`          TINYINT(1)       NOT NULL DEFAULT '0',
  `view_service_codes`        TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_service_codes`      TINYINT(1)       NOT NULL DEFAULT '0',
  `view_antispam_questions`   TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_antispam_questions` TINYINT(1)       NOT NULL DEFAULT '0',
  `view_services`             TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_services`           TINYINT(1)       NOT NULL DEFAULT '0',
  `view_servers`              TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_servers`            TINYINT(1)       NOT NULL DEFAULT '0',
  `view_logs`                 TINYINT(1)       NOT NULL DEFAULT '0',
  `manage_logs`               TINYINT(1)       NOT NULL DEFAULT '0',
  `update`                    TINYINT(1)       NOT NULL DEFAULT '0',
  UNIQUE KEY `gid` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin
  AUTO_INCREMENT = 3;

INSERT INTO `ss_groups` (`id`, `name`, `acp`, `manage_settings`, `view_groups`, `manage_groups`, `view_player_flags`, `view_user_services`, `manage_user_services`, `view_income`, `view_users`, `manage_users`, `view_sms_codes`, `manage_sms_codes`, `view_service_codes`, `manage_service_codes`, `view_antispam_questions`, `manage_antispam_questions`, `view_services`, `manage_services`, `view_servers`, `manage_servers`, `view_logs`, `manage_logs`, `update`)
VALUES
  (1, 'Użytkownik', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
  (2, 'Właściciel', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

DROP TABLE IF EXISTS `ss_pricelist`;
CREATE TABLE IF NOT EXISTS `ss_pricelist` (
  `id`      INT(11)          NOT NULL AUTO_INCREMENT,
  `service` VARCHAR(16)
            CHARACTER SET utf8
            COLLATE utf8_bin NOT NULL,
  `tariff`  INT(11)          NOT NULL,
  `amount`  INT(11)          NOT NULL DEFAULT '0',
  `server`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `price` (`service`, `tariff`, `server`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_servers_services`;
CREATE TABLE IF NOT EXISTS `ss_servers_services` (
  `server_id`  INT(11)          NOT NULL,
  `service_id` VARCHAR(16)
               COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `ss` (`server_id`, `service_id`),
  KEY `service_id` (`service_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin;

DROP TABLE IF EXISTS `ss_user_service_extra_flags`;
CREATE TABLE IF NOT EXISTS `ss_user_service_extra_flags` (
  `us_id`     INT(11)          NOT NULL,
  `service`   VARCHAR(16)
              CHARACTER SET utf8
              COLLATE utf8_bin NOT NULL,
  `server`    INT(11)          NOT NULL,
  `type`      INT(11)          NOT NULL,
  `auth_data` VARCHAR(64)      NOT NULL,
  `password`  VARCHAR(64)
              CHARACTER SET utf8
              COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`us_id`),
  UNIQUE KEY `server` (`server`, `service`, `type`, `auth_data`),
  KEY `service` (`service`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

DROP TABLE IF EXISTS `ss_user_service_mybb_extra_groups`;
CREATE TABLE IF NOT EXISTS `ss_user_service_mybb_extra_groups` (
  `us_id`    INT(11)          NOT NULL,
  `service`  VARCHAR(16)
             COLLATE utf8_bin NOT NULL,
  `mybb_uid` INT(11)          NOT NULL,
  UNIQUE KEY `user_service` (`us_id`),
  UNIQUE KEY `service` (`service`, `mybb_uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin;

DROP TABLE IF EXISTS `ss_user_service`;
CREATE TABLE IF NOT EXISTS `ss_user_service` (
  `id`      INT(11)          NOT NULL AUTO_INCREMENT,
  `service` VARCHAR(16)
            COLLATE utf8_bin NOT NULL,
  `uid`     INT(11)          NOT NULL,
  `expire`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `service` (`service`),
  KEY `uid` (`uid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_services`;
CREATE TABLE IF NOT EXISTS `ss_services` (
  `id`                VARCHAR(16)
                      CHARACTER SET utf8
                      COLLATE utf8_bin NOT NULL,
  `name`              VARCHAR(32)      NOT NULL DEFAULT '',
  `short_description` VARCHAR(28)      NOT NULL DEFAULT '',
  `description`       TEXT             NOT NULL,
  `types`             INT(11)          NOT NULL DEFAULT '0',
  `tag`               VARCHAR(16)      NOT NULL,
  `module`            VARCHAR(32)      NOT NULL DEFAULT '',
  `groups`            TEXT             NOT NULL,
  `flags`             VARCHAR(25)      NOT NULL DEFAULT '',
  `order`             INT(4)           NOT NULL DEFAULT '1',
  `data`              TEXT             NOT NULL,
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO `ss_services` (`id`, `name`, `short_description`, `description`, `types`, `tag`, `module`, `groups`, `flags`, `order`, `data`)
VALUES
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
  ('resslot', 'Rezerwacja Slota', '',
              '<strong>Rezerwacja Slota</strong> pozwala na wejście na serwer bez czekania na wolny slot!', 7, 'dni',
              'extra_flags', '', 'b', 4, '{"web": "1"}'),
  ('vip', 'VIP', '',
          '<strong>VIP</strong> to specjalne bonusy dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP może się nieco różnić w zależności typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.',
          7, 'dni', 'extra_flags', '', 't', 1, '{"web": "1"}'),
  ('vippro', 'VIP PRO', '',
             '<strong>VIP PRO</strong> to jeszcze więcej specjalnych bonusów dla graczy, oraz sporo ułatwień podczas rozgrywki. Oferta konta VIP PRO może się nieco różnić w zależności od typu rozgrywki. Poniższa lista przedstawia bonusy, na poszczególnych serwerach.',
             7, 'dni', 'extra_flags', '', 'btx', 2, '{"web": "1"}'),
  ('zp_ap', 'Ammo Packs', '', '', 0, 'AP', 'other', '', '', 7, '');

DROP TABLE IF EXISTS `ss_servers`;
CREATE TABLE IF NOT EXISTS `ss_servers` (
  `id`          INT(11)          NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(64)      NOT NULL DEFAULT '',
  `ip`          VARCHAR(16)      NOT NULL DEFAULT '',
  `port`        VARCHAR(8)       NOT NULL DEFAULT '',
  `sms_service` VARCHAR(32)
                CHARACTER SET utf8
                COLLATE utf8_bin NOT NULL,
  `type`        VARCHAR(16)      NOT NULL DEFAULT '',
  `version`     VARCHAR(8)       NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_sms_numbers`;
CREATE TABLE IF NOT EXISTS `ss_sms_numbers` (
  `number`  VARCHAR(16)      NOT NULL DEFAULT '',
  `tariff`  INT(11)          NOT NULL,
  `service` VARCHAR(32)
            CHARACTER SET utf8
            COLLATE utf8_bin NOT NULL,
  UNIQUE KEY `numbertextservice` (`number`, `service`),
  KEY `tariff` (`tariff`),
  KEY `service` (`service`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
  ('7136', 1, 'microsms'),
  ('7136', 1, 'mintshost'),
  ('7136', 1, 'profitsms'),
  ('7136', 1, 'simpay'),
  ('71480', 1, 'cashbill'),
  ('71480', 1, 'pukawka'),
  ('71480', 1, 'zabijaka'),
  ('7155', 1, 'homepay'),
  ('7155', 1, 'cssetti'),
  ('72480', 2, 'cashbill'),
  ('72480', 2, 'pukawka'),
  ('72480', 2, 'zabijaka'),
  ('7255', 2, 'homepay'),
  ('7255', 2, 'microsms'),
  ('7255', 2, 'mintshost'),
  ('7255', 2, 'profitsms'),
  ('7255', 2, 'simpay'),
  ('72624', 2, 'cssetti'),
  ('73480', 3, 'cashbill'),
  ('73480', 3, 'pukawka'),
  ('73480', 3, 'zabijaka'),
  ('7355', 3, 'homepay'),
  ('7355', 3, 'microsms'),
  ('7355', 3, 'mintshost'),
  ('7355', 3, 'profitsms'),
  ('7355', 3, 'simpay'),
  ('73624', 3, 'cssetti'),
  ('74480', 4, 'cashbill'),
  ('74480', 4, 'pukawka'),
  ('74480', 4, 'zabijaka'),
  ('7455', 4, 'homepay'),
  ('7455', 4, 'microsms'),
  ('7455', 4, 'mintshost'),
  ('7455', 4, 'profitsms'),
  ('7455', 4, 'simpay'),
  ('74624', 4, 'cssetti'),
  ('75480', 5, 'cashbill'),
  ('75480', 5, 'pukawka'),
  ('75480', 5, 'zabijaka'),
  ('7555', 5, 'homepay'),
  ('7555', 5, 'microsms'),
  ('7555', 5, 'mintshost'),
  ('7555', 5, 'profitsms'),
  ('7555', 5, 'simpay'),
  ('75624', 5, 'cssetti'),
  ('7636', 6, 'microsms'),
  ('7636', 6, 'mintshost'),
  ('7636', 6, 'profitsms'),
  ('7636', 6, 'simpay'),
  ('76480', 6, 'cashbill'),
  ('76480', 6, 'pukawka'),
  ('76480', 6, 'zabijaka'),
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
  ('79480', 9, 'cashbill'),
  ('79480', 9, 'pukawka'),
  ('79480', 9, 'zabijaka'),
  ('7955', 9, 'homepay'),
  ('79624', 9, 'cssetti'),
  ('91055', 10, 'homepay'),
  ('91055', 10, 'microsms'),
  ('91055', 10, 'simpay'),
  ('91155', 11, 'homepay'),
  ('91155', 11, 'microsms'),
  ('91155', 11, 'simpay'),
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
  ('91900', 19, 'cashbill'),
  ('91900', 19, 'pukawka'),
  ('91900', 19, 'zabijaka'),
  ('91955', 19, 'homepay'),
  ('91955', 19, 'microsms'),
  ('91955', 19, 'mintshost'),
  ('91955', 19, 'profitsms'),
  ('91955', 19, 'simpay'),
  ('91974', 19, 'cssetti'),
  ('92022', 20, 'cashbill'),
  ('92055', 20, 'homepay'),
  ('92055', 20, 'microsms'),
  ('92055', 20, 'simpay'),
  ('92520', 25, 'homepay'),
  ('92550', 25, 'cashbill'),
  ('92550', 25, 'pukawka'),
  ('92550', 25, 'zabijaka'),
  ('92555', 25, 'microsms'),
  ('92555', 25, 'mintshost'),
  ('92555', 25, 'profitsms'),
  ('92555', 25, 'simpay'),
  ('92574', 25, 'cssetti'),
  ('7055', 26, 'cssetti'),
  ('7055', 26, 'homepay'),
  ('7055', 26, 'microsms'),
  ('7055', 26, 'profitsms'),
  ('7055', 26, 'simpay'),
  ('70567', 26, 'cashbill'),
  ('7136', 1, '1s1k'),
  ('7255', 2, '1s1k'),
  ('7355', 3, '1s1k'),
  ('7455', 4, '1s1k'),
  ('7555', 5, '1s1k'),
  ('7636', 6, '1s1k'),
  ('77464', 7, '1s1k'),
  ('78464', 8, '1s1k'),
  ('7936', 9, '1s1k'),
  ('91055', 10, '1s1k'),
  ('91155', 11, '1s1k'),
  ('91455', 14, '1s1k'),
  ('91664', 16, '1s1k'),
  ('91955', 19, '1s1k'),
  ('92055', 20, '1s1k'),
  ('92555', 25, '1s1k'),
  ('7155', 1, 'bizneshost'),
  ('7255', 2, 'bizneshost'),
  ('7355', 3, 'bizneshost'),
  ('7555', 5, 'bizneshost'),
  ('76660', 6, 'bizneshost'),
  ('7955', 9, 'bizneshost'),
  ('91955', 19, 'bizneshost'),
  ('92520', 25, 'bizneshost'),
  ('7055', 26, 'hostplay'),
  ('7155', 1, 'hostplay'),
  ('7255', 2, 'hostplay'),
  ('7355', 3, 'hostplay'),
  ('7455', 4, 'hostplay'),
  ('7555', 5, 'hostplay'),
  ('76660', 6, 'hostplay'),
  ('7955', 9, 'hostplay'),
  ('91055', 10, 'hostplay'),
  ('91155', 11, 'hostplay'),
  ('91455', 14, 'hostplay'),
  ('91955', 19, 'hostplay'),
  ('92055', 20, 'hostplay'),
  ('92520', 25, 'hostplay');

DROP TABLE IF EXISTS `ss_transaction_services`;
CREATE TABLE IF NOT EXISTS `ss_transaction_services` (
  `id`          VARCHAR(32)
                CHARACTER SET utf8
                COLLATE utf8_bin NOT NULL,
  `name`        VARCHAR(32)      NOT NULL DEFAULT '',
  `data`        VARCHAR(512)     NOT NULL DEFAULT '',
  `data_hidden` VARCHAR(256)     NOT NULL DEFAULT '',
  `sms`         TINYINT(1)       NOT NULL DEFAULT '0',
  `transfer`    TINYINT(1)       NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('1s1k', '1shot 1kill', '{"api":"","sms_text":"SHOT"}', '', 1, 0),
  ('bizneshost', 'Biznes-Host', '{"uid":"","sms_text":"HPAY.BH"}', '', 1, 0),
  ('cashbill', 'CashBill', '{"service":"","key":"","sms_text":""}', '', 1, 1),
  ('cssetti', 'CSSetti', '{"account_id":"","sms_text":"DP CSSETTI"}', '', 1, 0),
  ('homepay', 'HomePay', '{"api":"","sms_text":"","7055":"","7155":"","7255":"","7355":"","7455":"","7555":"","76660":"","7955":"","91055":"","91155":"","91455":"","91955":"","92055":"","92520":""}', '', 1, 0),
  ('hostplay', 'HostPlay', '{"sms_text":"HOSTPLAY","user_id":""}', '', 1, 0),
  ('microsms', 'MicroSMS', '{"api":"","sms_text":"","service_id":""}', '', 1, 0),
  ('mintshost', 'MintsHost', '{"email":"","sms_text":"SIM.MINTS"}', '', 1, 0),
  ('profitsms', 'Profit SMS', '{"api":"","sms_text":""}', '', 1, 0),
  ('pukawka', 'Pukawka', '{"api":"","sms_text":"PUKAWKA"}', '', 1, 0),
  ('simpay', 'SimPay', '{"sms_text":"","key":"","secret":"","service_id":""}', '', 1, 0),
  ('transferuj', 'Transferuj', '{"account_id":"","key":""}', '', 0, 1),
  ('zabijaka', 'Zabijaka', '{"api":"","sms_text":"AG.ZABIJAKA"}', '', 1, 0);

DROP TABLE IF EXISTS `ss_tariffs`;
CREATE TABLE IF NOT EXISTS `ss_tariffs` (
  `id`         INT(11)    NOT NULL,
  `provision`  INT(11)    NOT NULL DEFAULT '0',
  `predefined` TINYINT(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

INSERT INTO `ss_tariffs` (`id`, `provision`, `predefined`) VALUES
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

DROP TABLE IF EXISTS `ss_antispam_questions`;
CREATE TABLE IF NOT EXISTS `ss_antispam_questions` (
  `id`       INT(11)      NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(128) NOT NULL,
  `answers`  VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 11;

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

DROP TABLE IF EXISTS `ss_bought_services`;
CREATE TABLE IF NOT EXISTS `ss_bought_services` (
  `id`         INT(11)          NOT NULL AUTO_INCREMENT,
  `uid`        INT(11)          NOT NULL,
  `payment`    VARCHAR(16)
               CHARACTER SET utf8
               COLLATE utf8_bin NOT NULL,
  `payment_id` VARCHAR(16)      NOT NULL,
  `service`    VARCHAR(32)
               CHARACTER SET utf8
               COLLATE utf8_bin NOT NULL,
  `server`     INT(11)          NOT NULL,
  `amount`     VARCHAR(32)      NOT NULL DEFAULT '',
  `auth_data`  VARCHAR(64)      NOT NULL DEFAULT '',
  `email`      VARCHAR(128)     NOT NULL DEFAULT '',
  `extra_data` VARCHAR(256)     NOT NULL DEFAULT '',
  `timestamp`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_logs`;
CREATE TABLE IF NOT EXISTS `ss_logs` (
  `id`        INT(11)   NOT NULL AUTO_INCREMENT,
  `text`      TEXT      NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_mybb_user_group`;
CREATE TABLE IF NOT EXISTS `ss_mybb_user_group` (
  `uid`        INT(11)    NOT NULL,
  `gid`        INT(11)    NOT NULL,
  `expire`     TIMESTAMP  NULL DEFAULT NULL,
  `was_before` TINYINT(4) NOT NULL,
  PRIMARY KEY (`uid`, `gid`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin;

DROP TABLE IF EXISTS `ss_payment_code`;
CREATE TABLE IF NOT EXISTS `ss_payment_code` (
  `id`       INT(11)     NOT NULL AUTO_INCREMENT,
  `code`     VARCHAR(16) NOT NULL DEFAULT '',
  `ip`       VARCHAR(64) NOT NULL DEFAULT '',
  `platform` TEXT        NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_payment_sms`;
CREATE TABLE IF NOT EXISTS `ss_payment_sms` (
  `id`       INT(11)            NOT NULL AUTO_INCREMENT,
  `code`     VARCHAR(16)
             CHARACTER SET utf8 NOT NULL DEFAULT '',
  `income`   INT(11)            NOT NULL DEFAULT '0',
  `cost`     INT(11)            NOT NULL DEFAULT '0',
  `text`     VARCHAR(32)
             CHARACTER SET utf8 NOT NULL DEFAULT '',
  `number`   VARCHAR(16)
             CHARACTER SET utf8 NOT NULL DEFAULT '',
  `ip`       VARCHAR(64)
             CHARACTER SET utf8 NOT NULL DEFAULT '',
  `platform` TEXT               NOT NULL,
  `free`     TINYINT(1)         NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_payment_transfer`;
CREATE TABLE IF NOT EXISTS `ss_payment_transfer` (
  `id`               VARCHAR(16)
                     CHARACTER SET utf8
                     COLLATE utf8_bin NOT NULL,
  `income`           INT(11)          NOT NULL DEFAULT '0',
  `transfer_service` VARCHAR(64)
                     CHARACTER SET utf8
                     COLLATE utf8_bin NOT NULL,
  `ip`               VARCHAR(64)      NOT NULL DEFAULT '',
  `platform`         TEXT             NOT NULL,
  UNIQUE KEY `orderid` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

DROP TABLE IF EXISTS `ss_payment_wallet`;
CREATE TABLE IF NOT EXISTS `ss_payment_wallet` (
  `id`       INT(11)     NOT NULL AUTO_INCREMENT,
  `cost`     INT(11)     NOT NULL DEFAULT '0',
  `ip`       VARCHAR(64) NOT NULL DEFAULT '',
  `platform` TEXT        NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_players_flags`;
CREATE TABLE IF NOT EXISTS `ss_players_flags` (
  `id`        INT(11)          NOT NULL AUTO_INCREMENT,
  `server`    INT(11)          NOT NULL,
  `type`      INT(11)          NOT NULL DEFAULT '0',
  `auth_data` VARCHAR(32)      NOT NULL,
  `password`  VARCHAR(34)
              CHARACTER SET utf8
              COLLATE utf8_bin NOT NULL,
  `a`         INT(11)          NOT NULL DEFAULT '0',
  `b`         INT(11)          NOT NULL DEFAULT '0',
  `c`         INT(11)          NOT NULL DEFAULT '0',
  `d`         INT(11)          NOT NULL DEFAULT '0',
  `e`         INT(11)          NOT NULL DEFAULT '0',
  `f`         INT(11)          NOT NULL DEFAULT '0',
  `g`         INT(11)          NOT NULL DEFAULT '0',
  `h`         INT(11)          NOT NULL DEFAULT '0',
  `i`         INT(11)          NOT NULL DEFAULT '0',
  `j`         INT(11)          NOT NULL DEFAULT '0',
  `k`         INT(11)          NOT NULL DEFAULT '0',
  `l`         INT(11)          NOT NULL DEFAULT '0',
  `m`         INT(11)          NOT NULL DEFAULT '0',
  `n`         INT(11)          NOT NULL DEFAULT '0',
  `o`         INT(11)          NOT NULL DEFAULT '0',
  `p`         INT(11)          NOT NULL DEFAULT '0',
  `q`         INT(11)          NOT NULL DEFAULT '0',
  `r`         INT(11)          NOT NULL DEFAULT '0',
  `s`         INT(11)          NOT NULL DEFAULT '0',
  `t`         INT(11)          NOT NULL DEFAULT '0',
  `u`         INT(11)          NOT NULL DEFAULT '0',
  `y`         INT(11)          NOT NULL DEFAULT '0',
  `v`         INT(11)          NOT NULL DEFAULT '0',
  `w`         INT(11)          NOT NULL DEFAULT '0',
  `x`         INT(11)          NOT NULL DEFAULT '0',
  `z`         INT(11)          NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `server+type+player` (`server`, `type`, `auth_data`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_service_codes`;
CREATE TABLE IF NOT EXISTS `ss_service_codes` (
  `id`        INT(11)     NOT NULL AUTO_INCREMENT,
  `code`      VARCHAR(16) NOT NULL DEFAULT '',
  `service`   VARCHAR(16) NOT NULL,
  `server`    INT(11)     NOT NULL DEFAULT '0',
  `tariff`    INT(11)     NOT NULL DEFAULT '0',
  `uid`       INT(11)     NOT NULL DEFAULT '0',
  `amount`    DOUBLE      NOT NULL DEFAULT '0',
  `data`      TEXT        NOT NULL,
  `timestamp` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS `ss_settings`;
CREATE TABLE IF NOT EXISTS `ss_settings` (
  `key`   VARCHAR(128)
          CHARACTER SET utf8
          COLLATE utf8_bin NOT NULL DEFAULT '',
  `value` VARCHAR(256)     NOT NULL DEFAULT '',
  UNIQUE KEY `key` (`key`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

INSERT INTO `ss_settings` (`key`, `value`) VALUES
  ('contact', ''),
  ('cron_each_visit', '0'),
  ('currency', 'PLN'),
  ('date_format', 'Y-m-d H:i'),
  ('delete_logs', '0'),
  ('google_analytics', ''),
  ('language', 'polish'),
  ('license_login', 'license'),
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
  ('vat', '1.23'),
  ('gadugadu', '');

DROP TABLE IF EXISTS `ss_sms_codes`;
CREATE TABLE IF NOT EXISTS `ss_sms_codes` (
  `id`     INT(11)            NOT NULL AUTO_INCREMENT,
  `code`   VARCHAR(16)
           CHARACTER SET utf8 NOT NULL DEFAULT '',
  `tariff` INT(11)            NOT NULL,
  `free`   TINYINT(1)         NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1
  AUTO_INCREMENT = 1;


ALTER TABLE `ss_payment_admin`
  ADD CONSTRAINT `ss_payment_admin_ibfk_1` FOREIGN KEY (`aid`) REFERENCES `ss_users` (`uid`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ss_pricelist`
  ADD CONSTRAINT `ss_pricelist_ibfk_1` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_servers_services`
  ADD CONSTRAINT `ss_servers_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_servers_services_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_sms_numbers`
  ADD CONSTRAINT `ss_sms_numbers_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_transaction_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_sms_numbers_ibfk_1` FOREIGN KEY (`tariff`) REFERENCES `ss_tariffs` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service`
  ADD CONSTRAINT `ss_user_service_ibfk_1` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_extra_flags`
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_3` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_mybb_extra_groups`
  ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE;
