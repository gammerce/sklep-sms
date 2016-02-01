INSERT IGNORE INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('bizneshost', 'Biznes-Host', '{"uid":"","sms_text":"HPAY.BH"}', '', 1, 0),
  ('hostplay', 'HostPlay', '{"sms_text":"HPAY.HOSTPLAY","user_id":""}', '', 1, 0);

INSERT IGNORE INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
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
  ('92555', 25, 'hostplay'),
  ('7155', 1, 'bizneshost'),
  ('7255', 2, 'bizneshost'),
  ('7355', 3, 'bizneshost'),
  ('7555', 5, 'bizneshost'),
  ('76660', 6, 'bizneshost'),
  ('7955', 9, 'bizneshost'),
  ('91955', 19, 'bizneshost'),
  ('92520', 25, 'bizneshost');

DELETE FROM `ss_sms_numbers`
WHERE `number` = '70567' AND `tariff` = 26 AND `service` = 'pukawka';

ALTER TABLE `ss_payment_admin` CHANGE `platform`  `platform` TEXT
CHARACTER SET utf8 NOT NULL;

ALTER TABLE `ss_payment_code` CHANGE `platform`  `platform` TEXT
CHARACTER SET utf8 NOT NULL;

ALTER TABLE `ss_payment_sms` CHANGE `platform`  `platform` TEXT
CHARACTER SET utf8 NOT NULL;

ALTER TABLE `ss_payment_transfer` CHANGE `platform`  `platform` TEXT
CHARACTER SET utf8 NOT NULL;

ALTER TABLE `ss_payment_wallet` CHANGE `platform`  `platform` TEXT
CHARACTER SET utf8 NOT NULL;

ALTER TABLE `ss_users`
CHANGE `email`  `email` VARCHAR(128)
CHARACTER SET utf8 NOT NULL DEFAULT '',
CHANGE `regip`  `regip` VARCHAR(16)
CHARACTER SET utf8 NOT NULL DEFAULT '',
CHANGE `lastip`  `lastip` VARCHAR(16)
CHARACTER SET utf8 NOT NULL DEFAULT '',
CHANGE `reset_password_key`  `reset_password_key` VARCHAR(32)
CHARACTER SET utf8 NOT NULL DEFAULT '';

ALTER TABLE `ss_bought_services`
CHANGE `email`  `email` VARCHAR(128)
CHARACTER SET utf8 NOT NULL DEFAULT '',
CHANGE `auth_data`  `auth_data` VARCHAR(32)
CHARACTER SET utf8 NOT NULL DEFAULT '',
CHANGE `amount`  `amount` VARCHAR(32)
CHARACTER SET utf8 NOT NULL DEFAULT '';

ALTER TABLE `ss_servers`
CHANGE `version`  `version` VARCHAR(8)
CHARACTER SET utf8 NOT NULL DEFAULT '';