<?php

/**
 * Created by MilyGosc.
 * URL: http://forum.sklep-sms.pl/showthread.php?tid=88
 */

$heart->register_payment_api("transferuj", "PaymentModuleTransferuj");

class PaymentModuleTransferuj extends PaymentModule implements IPayment_Transfer
{

	const SERVICE_ID = "transferuj";

	public function prepare_transfer($purchase_data, $data_filename)
	{
		global $settings;

		// Zamieniamy grosze na złotówki
		$cost = number_format($purchase_data->getPayment('cost') / 100, 2);

		return array(
			'url' => $this->data['transfer_url'],
			'id' => $this->data['account_id'],
			'kwota' => $cost,
			'opis' => $purchase_data->getDesc(),
			'crc' => $data_filename,
			'md5sum' => md5($this->data['account_id'] . $cost . $data_filename . $this->data['key']),
			'imie' => $purchase_data->user->getForename(false),
			'nazwisko' => $purchase_data->user->getSurname(false),
			'email' => $purchase_data->getEmail(),
			'pow_url' => $settings['shop_url_slash'] . "index.php?pid=transferuj_ok",
			'pow_url_blad' => $settings['shop_url_slash'] . "index.php?pid=transferuj_bad"
		);
	}

	public function finalizeTransfer($get, $post)
	{
		$transfer_finalize = new Entity_TransferFinalize();

		if ($_SERVER['REMOTE_ADDR'] == '195.149.229.109' && !empty($post) && $_POST['tr_status'] == 'TRUE' && $_POST['tr_error'] == 'none') {
			$transfer_finalize->setStatus(true);
		}

		$transfer_finalize->setOrderid($post['tr_id']);
		$transfer_finalize->setAmount($post['tr_amount']);
		$transfer_finalize->setDataFilename($post['tr_crc']);
		$transfer_finalize->setTransferService($post['id']);
		$transfer_finalize->setOutput('TRUE');

		return $transfer_finalize;
	}
}