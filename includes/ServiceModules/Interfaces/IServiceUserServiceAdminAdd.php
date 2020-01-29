<?php
namespace App\ServiceModules\Interfaces;

/**
 * Obsługa dodawania usług użytkownika w PA
 */
interface IServiceUserServiceAdminAdd
{
    /**
     * Metoda sprawdza dane formularza podczas dodawania użytkownikowi usługi w PA
     * i gdy wszystko jest okej, to ją dodaje.
     *
     * @param array $body
     */
    public function userServiceAdminAdd(array $body);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function userServiceAdminAddFormGet();
}
