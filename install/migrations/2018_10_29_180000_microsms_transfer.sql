UPDATE `ss_transaction_services`
SET `transfer` = 1, `data` = CONCAT(SUBSTRING(`data`, 1, LENGTH(`data`) - 1), ',"shop_id": "","hash": ""}')
WHERE `id` = 'microsms';
