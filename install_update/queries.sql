INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('intersms', 'InterSMS', '{"sms_text":"","user_id":"","client_key":""}', '', 1, 0);

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
  ('7155', 1, 'intersms'),
  ('7255', 2, 'intersms'),
  ('7355', 3, 'intersms'),
  ('7455', 4, 'intersms'),
  ('7555', 5, 'intersms'),
  ('76660', 6, 'intersms'),
  ('7955', 9, 'intersms'),
  ('91955', 19, 'intersms'),
  ('92520', 25, 'intersms');

INSERT INTO `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`) VALUES
  ('hostplay', 'HostPlay', '{"sms_text":"HPAY.HOSTPLAY","user_id":""}', '', 1, 0);

INSERT INTO `ss_sms_numbers` (`number`, `tariff`, `service`) VALUES
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