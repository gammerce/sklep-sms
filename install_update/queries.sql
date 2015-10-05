DELETE FROM `ss_sms_numbers`
WHERE `tariff` = '10' AND `service` = 'mintshost';

ALTER TABLE `ss_tariffs` CHANGE `tariff` `id` INT(11) NOT NULL;

DELETE FROM `ss_sms_numbers`
WHERE `service` = '1s1k';

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
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
  ('92555', 25, '1s1k');

INSERT INTO `ss_settings`(`key`, `value`) VALUES ('gadugadu', '');