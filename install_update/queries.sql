UPDATE `ss_sms_numbers`
SET `number` = '76660'
WHERE `service` = 'homepay' AND `number` = '7655';

UPDATE `ss_sms_numbers`
SET `number` = '92520'
WHERE `service` = 'homepay' AND `number` = '92555';

UPDATE `ss_transaction_services`
SET
  `data` = '{"api":"","sms_text":"","7055":"","7155":"","7255":"","7355":"","7455":"","7555":"","76660":"","7955":"","91055":"","91155":"","91455":"","91955":"","92055":"","92520":""}'
WHERE `id` = 'homepay';

DELETE FROM `ss_sms_numbers`
WHERE `service` = 'cssetti';

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
  ('7055', '26', 'cssetti'), ('71624', '1', 'cssetti'), ('72624', '2', 'cssetti'),
  ('73624', '3', 'cssetti'), ('74624', '4', 'cssetti'), ('75624', '5', 'cssetti'),
  ('76624', '6', 'cssetti'), ('77464', '7', 'cssetti'), ('78464', '8', 'cssetti'),
  ('79624', '9', 'cssetti'), ('91455', '14', 'cssetti'), ('91974', '19', 'cssetti'),
  ('92574', '25', 'cssetti');

UPDATE `ss_transaction_services`
SET `data` = '{"account_id":"","sms_text":"DP CSSETTI"}'
WHERE `id` = 'cssetti';

INSERT IGNORE INTO `ss_transaction_services` (`id`, `name`, `data`, `sms`, `transfer`)
VALUES ('transferuj', 'Transferuj', '{"account_id":"","key":""}', '0', '1');

UPDATE `ss_transaction_services`
SET `data_hidden` = ''
WHERE `id` = 'cashbill';

INSERT IGNORE INTO `ss_transaction_services` (`id`, `name`, `data`, `sms`, `transfer`)
VALUES ('simpay', 'SimPay', '{"sms_text":"","key":"","secret":"","service_id":""}', '1', '0');

INSERT IGNORE INTO `ss_sms_numbers` (`service`, `number`, `tariff`)
VALUES ('simpay', '7055', '26'), ('simpay', '7136', '1'), ('simpay', '7255', '2'), ('simpay', '7355', '3'),
  ('simpay', '7455', '4'), ('simpay', '7555', '5'), ('simpay', '7636', '6'), ('simpay', '77464', '7'),
  ('simpay', '78464', '8'), ('simpay', '7936', '9'), ('simpay', '91055', '10'), ('simpay', '91155', '11'),
  ('simpay', '91455', '14'), ('simpay', '91664', '16'), ('simpay', '91955', '19'), ('simpay', '92055', '20'),
  ('simpay', '92555', '25');

INSERT IGNORE INTO `ss_settings` (`key`, `value`) VALUES ('google_analytics', '');

UPDATE `ss_transaction_services`
SET `data` = '{"api":"","sms_text":"","service_id":""}'
WHERE `id` = 'microsms';

CREATE TABLE IF NOT EXISTS `ss_user_service` (
  `id`      INT(11)          NOT NULL AUTO_INCREMENT,
  `service` VARCHAR(16)
            COLLATE utf8_bin NOT NULL,
  `uid`     INT(11)          NOT NULL,
  `expire`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `service` (`service`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_bin
  AUTO_INCREMENT = 1;

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

ALTER TABLE `ss_payment_sms` CHANGE `income` `income` INT NOT NULL, CHANGE `cost` `cost` INT NOT NULL;

ALTER TABLE `ss_payment_transfer` CHANGE `income` `income` INT NOT NULL;

ALTER TABLE `ss_payment_wallet` CHANGE `cost` `cost` INT NOT NULL;

ALTER TABLE `ss_users` CHANGE `wallet` `wallet` INT NOT NULL DEFAULT '0';

ALTER TABLE `ss_tariffs` CHANGE `provision` `provision` INT NOT NULL DEFAULT '0';

ALTER TABLE `ss_payment_admin` ADD INDEX (`aid`);

ALTER TABLE `ss_sms_numbers` ADD INDEX (`tariff`);

ALTER TABLE `ss_user_service` ADD INDEX (`uid`);

CREATE TABLE IF NOT EXISTS `ss_tmp` (
  `id` INT(11) NOT NULL
);

INSERT INTO `ss_tmp` (`id`)
  SELECT a.id AS `id`
  FROM `ss_pricelist` AS a
    LEFT JOIN `ss_services` AS b ON a.service = b.id
  WHERE b.id IS NULL;

DELETE FROM `ss_pricelist`
WHERE `id` IN (
  SELECT `id`
  FROM `ss_tmp`
);

TRUNCATE TABLE `ss_tmp`;

ALTER TABLE `ss_pricelist` ADD FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_servers_services` ADD FOREIGN KEY (`server_id`) REFERENCES `ss_servers` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_servers_services` ADD FOREIGN KEY (`service_id`) REFERENCES `ss_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

INSERT INTO `ss_tmp` (`id`)
  SELECT pa.id AS `id`
  FROM `ss_payment_admin` AS pa
    LEFT JOIN `ss_users` AS u ON pa.aid = u.uid
  WHERE u.uid IS NULL;

DELETE FROM `ss_payment_admin`
WHERE `id` IN (
  SELECT `id`
  FROM `ss_tmp`
);

TRUNCATE TABLE `ss_tmp`;

ALTER TABLE `ss_payment_admin` ADD FOREIGN KEY (`aid`) REFERENCES `ss_users` (`uid`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ss_sms_numbers` ADD FOREIGN KEY (`tariff`) REFERENCES `ss_tariffs` (`tariff`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_sms_numbers` ADD FOREIGN KEY (`service`) REFERENCES `ss_transaction_services` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service` ADD CONSTRAINT `ss_user_service_ibfk_1` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_extra_flags` ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE, ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE, ADD CONSTRAINT `ss_user_service_extra_flags_ibfk_3` FOREIGN KEY (`server`) REFERENCES `ss_servers` (`id`)
  ON DELETE NO ACTION
  ON UPDATE CASCADE;

ALTER TABLE `ss_user_service_mybb_extra_groups` ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_1` FOREIGN KEY (`us_id`) REFERENCES `ss_user_service` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE, ADD CONSTRAINT `ss_user_service_mybb_extra_groups_ibfk_2` FOREIGN KEY (`service`) REFERENCES `ss_services` (`id`)
  ON UPDATE CASCADE;

INSERT INTO `ss_user_service` (`id`, `service`, `uid`, `expire`)
  SELECT
    `id`,
    `service`,
    `uid`,
    `expire`
  FROM `ss_players_services`;

INSERT INTO `ss_user_service_extra_flags` (`us_id`, `service`, `server`, `type`, `auth_data`, `password`)
  SELECT
    ps.id,
    `service`,
    `server`,
    `type`,
    `auth_data`,
    `password`
  FROM `ss_players_services` AS ps
    LEFT JOIN `ss_services` AS s ON ps.service = s.id
  WHERE s.module = 'extra_flags';

UPDATE `ss_payment_sms`
SET `income` = `income` * 100, `cost` = `cost` * 100;

UPDATE `ss_payment_transfer`
SET `income` = `income` * 100;

UPDATE `ss_payment_wallet`
SET `cost` = `cost` * 100;

UPDATE `ss_users`
SET `wallet` = `wallet` * 100;

UPDATE `ss_tariffs`
SET `provision` = `provision` * 100;

DROP TABLE `ss_players_services`;

DROP TABLE `ss_tmp`;
