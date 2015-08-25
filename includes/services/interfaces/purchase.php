<?php

/**
 * Możliwość zakupu usługi
 *
 * Interface IService_Purchase
 */
interface IService_Purchase
{
	/**
	 * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
	 *
	 * @param Entity_Purchase $purchase_data
	 * @return integer        value returned by function add_bought_service_info
	 */
	public function purchase($purchase_data);
}