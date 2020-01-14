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
    public function serviceCodeAdminAddValidate(array $body);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania kodu na usługę
     *
     * @return string
     */
    public function serviceCodeAdminAddFormGet();
}
