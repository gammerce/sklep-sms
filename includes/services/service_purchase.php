<?php

interface IServicePurchase
{
    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param array $data user:
     *                            uid - id uzytkownika wykonującego zakupy
     *                            ip - ip użytkownika wykonującego zakupy
     *                            email - email -||-
     *                            name - nazwa -||-
     *                        transaction:
     *                            method - sposób płatności
     *                            payment_id - id płatności
     *                        order:
     *                            server - serwer na który ma być wykupiona usługa
     *                            auth_data - dane rozpoznawcze gracza
     *                            type - TYPE_NICK / TYPE_IP / TYPE_SID
     *                            password - hasło do usługi
     *                            amount - ilość kupionej usługi
     *
     * @return integer        value returned by function add_bought_service_info
     */
    public function purchase($data);
}