<?php

interface IPayment_Transfer
{

	/**
	 * Przygotowanie zapytania POST w celu płatności przelewem
	 *
	 * @param Entity_Purchase $purchase_data
	 * @return array    ['url'] - adres url strony do ktorej wysylamy dane POST
	 *                  ...     - pola POST
	 */
	public function prepare_transfer($purchase_data);

}