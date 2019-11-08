<?php
namespace App\Services\Interfaces;

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
     *
     * @return array
     *  status => id wiadomości
     *  text => treść wiadomości
     *  positive => czy udało się dodać usługę
     */
    public function userServiceAdminAdd($body);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function userServiceAdminAddFormGet();
}
