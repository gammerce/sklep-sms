UPDATE `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`)
SET `data` = '{"api":"","sms_text":"","service_id":""}', `transfer` = 1
WHERE `id` = 'microsms';
