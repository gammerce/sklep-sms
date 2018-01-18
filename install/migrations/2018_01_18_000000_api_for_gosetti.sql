INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('gosetti', 'GOSetti.pl', '{"account_id":"","sms_text":"CSGO"}', '', 1, 0);

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
  ('71480', 1, 'gosetti'),
  ('72480', 2, 'gosetti'),
  ('73480', 3, 'gosetti'),
  ('74480', 4, 'gosetti'),
  ('75480', 5, 'gosetti'),
  ('76480', 6, 'gosetti'),
  ('79480', 9, 'gosetti'),
  ('91400', 14, 'gosetti'),
  ('91900', 19, 'gosetti'),
  ('92022', 20, 'gosetti'),
  ('92521', 25, 'gosetti');
