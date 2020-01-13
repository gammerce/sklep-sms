<?php
namespace App\ServiceModules\Interfaces;

/**
 * Obsługa dodawania nowych kodów na usługę w PA
 */
interface IServiceServiceCodeAdminManage
{
    /**
     * Metoda sprawdza dane formularza podczas dodawania kodu na usługę w PA
     *
     * @param array $body
     *
     * @return array 'key' (DOM element name) => 'value'
     */
    public function serviceCodeAdminAddValidate($body);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania kodu na usługę
     *
     * @return string
     */
    public function serviceCodeAdminAddFormGet();

    /**
     * Metoda powinna zwrócić tablicę z danymi które zostaną dodane do bazy wraz z kodem na usługę
     * można założyć że dane są już prawidłowo zweryfikowane przez metodę service_code_admin_add_validate
     *
     * @param $data
     *
     * @return array (
     *        'server'    - int,
     *        'amount'    - double,
     *        'tariff'    - int,
     *        'data'        - string
     * )
     */
    public function serviceCodeAdminAddInsert($data);
}
