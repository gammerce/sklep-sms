DELETE FROM `ss_sms_numbers` WHERE `tariff` = '10' AND `service` = 'mintshost';

ALTER TABLE `ss_tariffs` CHANGE `tariff` `id` INT(11) NOT NULL;