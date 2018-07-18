ALTER TABLE `ss_users` MODIFY `lastip` VARCHAR(64);
ALTER TABLE `ss_users` MODIFY `regip` VARCHAR(64);
ALTER TABLE `ss_payment_code` MODIFY `ip` VARCHAR(64);
ALTER TABLE `ss_payment_sms` MODIFY `ip` VARCHAR(64);
ALTER TABLE `ss_payment_transfer` MODIFY `ip` VARCHAR(64);
ALTER TABLE `ss_payment_wallet` MODIFY `ip` VARCHAR(64);