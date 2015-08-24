<?php

/**
 * Created by MilyGosc.
 * URL: http://forum.sklep-sms.pl/showthread.php?tid=88
 */

$heart->register_payment_api("transferuj", "PaymentModuleTransferuj");

class PaymentModuleTransferuj extends PaymentModule implements IPayment_Transfer
{

	const SERVICE_ID = "transferuj";

	public function prepare_transfer($purchase_data)
	{
		global $settings;

		$serialized = serialize($purchase_data);
		$data_hash = time() . "-" . md5($serialized);
		file_put_contents(SCRIPT_ROOT . "data/transfers/" . $data_hash, $serialized);

		// Zamieniamy grosze na złotówki
		$cost = number_format($purchase_data->getPayment('cost') / 100, 2);

		$md5sum = md5($this->data['account_id'] . $cost . $data_hash . $this->data['key']);

		return array(
			'url' => $this->data['transfer_url'],
			'id' => $this->data['account_id'],
			'kwota' => $cost,
			'opis' => $purchase_data->getDesc(),
			'crc' => $data_hash,
			'md5sum' => $md5sum,
			'imie' => $purchase_data->user->getForename(false),
			'nazwisko' => $purchase_data->user->getSurname(false),
			'email' => $purchase_data->getEmail(),
			'pow_url' => $settings['shop_url_slash'] . "index.php?pid=transferuj_ok",
			'pow_url_blad' => $settings['shop_url_slash'] . "index.php?pid=transferuj_bad"
		);
	}
}