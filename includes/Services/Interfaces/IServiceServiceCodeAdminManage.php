<?php
namespace App\Services\Interfaces;

/**
 * Obsługa dodawania nowych kodów na usługę w PA
 */
interface IServiceServiceCodeAdminManage
{
    /**
     * Metoda sprawdza dane formularza podczas dodawania kodu na usługę w PA
     *
     * @param array $post Dane $_POST
     *
     * @return array 'key' (DOM element name) => 'value'
     */
    public function service_code_admin_add_validate($post);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania kodu na usługę
     *
     * @return string
     */
    public function service_code_admin_add_form_get();

    /**
     * Metoda powinna zwrócić tablicę z danymi które zostaną dodane do bazy wraz z kodem na usługę
     * można założyć że dane są już prawidłowo zweryfikowane przez metodę service_code_admin_add_validate
     *
     * @param $data
     *
     * @return array (
     *        'server'    - integer,
     *        'amount'    - double,
     *        'tariff'    - integer,
     *        'data'        - string
     * )
     */
    public function service_code_admin_add_insert($data);
}
