UPDATE `ss_transaction_services` (`id`, `name`, `data`, `data_hidden`, `sms`, `transfer`)
SET `transfer` = 1, `data` = CONCAT(SUBSTRING(`data`, 1, LENGTH(`data`) - 1), ',"test": ""}')
WHERE `id` = 'microsms';
