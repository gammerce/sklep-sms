<?php

define('IN_SCRIPT', "1");
define("SCRIPT_NAME", "transfer_finalize");

require_once 'global.php';

$payment = new Payment($_GET['service']);
$transfer_finalize = $payment->getPaymentModule()->finalizeTransfer($_GET, $_POST);

if ($transfer_finalize->getStatus() === false) {
	log_info($lang_shop->sprintf($lang_shop->translate('payment_not_accepted'), $transfer_finalize->getOrderid(), $transfer_finalize->getAmount(), $transfer_finalize->getTransferService(),
		$purchase_data->user->getUsername(), $purchase_data->user->getUid(), $purchase_data->user->getLastIp()));
}

$payment->transferFinalize($transfer_finalize);

output_page($transfer_finalize->getOutput());