<?php

interface IPayment_Transfer
{

	/**
	 * Przygotowanie zapytania POST w celu płatności przelewem
	 *
	 * @param Entity_Purchase $purchase_data
	 * @param string $data_filename
	 * @return array
	 *  string url => adres url strony do ktorej wysylamy dane POST
	 *  ... - wysyłane pola POST
	 */
	public function prepare_transfer($purchase_data, $data_filename);

	/**
	 * Finalizuje zakup usługi podczas zakupu poprzez przelew
	 *
	 * @param $get
	 * @param $post
	 * @return Entity_TransferFinalize
	 */
	public function finalizeTransfer($get, $post);

}