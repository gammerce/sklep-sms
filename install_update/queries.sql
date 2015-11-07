INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('bizneshost', 'Biznes-Host', '{"uid":"","sms_text":"HPAY.BH"}', '', 1, 0);

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
  ('7155', 1, 'bizneshost'),
  ('7255', 2, 'bizneshost'),
  ('7355', 3, 'bizneshost'),
  ('7555', 5, 'bizneshost'),
  ('76660', 6, 'bizneshost'),
  ('7955', 9, 'bizneshost'),
  ('91955', 19, 'bizneshost'),
  ('92520', 25, 'bizneshost');