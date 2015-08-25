<?php

$heart->register_payment_api("cashbill", "PaymentModuleCashbill");

class PaymentModuleCashbill extends PaymentModule implements IPayment_Sms, IPayment_Transfer
{

	const SERVICE_ID = "cashbill";

	public function verify_sms($sms_code, $sms_number)
	{
		$handle = fopen("http://sms.cashbill.pl/backcode_check_singleuse_noip.php?id=&code=" . urlencode($this->data['sms_text']) .
			"&check=" . urlencode($sms_code), 'r');

		if ($handle) {
			$status = fgets($handle, 8);
			/*$czas_zycia = */
			fgets($handle, 24);
			/*$foo = */
			fgets($handle, 96);
			$bramka = fgets($handle, 96);
			fclose($handle);

			if ($status == '0')
				$output['status'] = "BAD_CODE";
			else if ($sms_number !== $bramka) {
				$output['status'] = "BAD_NUMBER";
				$output['tariff'] = $this->smses[$bramka]['tariff'];
			} else
				$output['status'] = "OK";
		} else
			$output['status'] = "NO_CONNECTION";

		return $output;
	}

	public function prepare_transfer($purchase_data, $data_filename)
	{
		// Zamieniamy grosze na złotówki
		$cost = number_format($purchase_data->getPayment('cost') / 100, 2);

		return array(
			'url' => $this->data['transfer_url'],
			'service' => $this->data['service'],
			'desc' => $purchase_data->getDesc(),
			'forname' => $purchase_data->user->getForename(false),
			'surname' => $purchase_data->user->getSurname(false),
			'email' => $purchase_data->getEmail(),
			'amount' => $cost,
			'userdata' => $data_filename,
			'sign' => md5($this->data['service'] . $cost . $purchase_data->getDesc() . $data_filename . $purchase_data->user->getForename(false) .
				$purchase_data->user->getSurname(false) . $purchase_data->getEmail() . $this->data['key'])
		);
	}

	public function finalizeTransfer($get, $post)
	{
		$transfer_finalize = new Entity_TransferFinalize();

		if ($this->check_sign($post, $this->data['key'], $post['sign']) && strtoupper($post['status']) == 'OK' && $post['service'] == $this->data['service']) {
			$transfer_finalize->setStatus(true);
		}

		$transfer_finalize->setOrderid($post['orderid']);
		$transfer_finalize->setAmount($post['amount']);
		$transfer_finalize->setDataFilename($post['userdata']);
		$transfer_finalize->setTransferService($post['service']);
		$transfer_finalize->setOutput('OK');

		return $transfer_finalize;
	}

	/**
	 * Funkcja sprawdzajaca poprawnosc sygnatury
	 * przy płatnościach za pomocą przelewu
	 *
	 * @param $data - dane
	 * @param $key - klucz do hashowania
	 * @param $sign - hash danych
	 * @return bool
	 */
	public function check_sign($data, $key, $sign)
	{
		return md5($data['service'] . $data['orderid'] . $data['amount'] . $data['userdata'] . $data['status'] . $key) == $sign;
	}
}