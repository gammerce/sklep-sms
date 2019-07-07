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
     * @param array $post Dane $_POST
     *
     * @return array
     *  status => id wiadomości
     *  text => treść wiadomości
     *  positive => czy udało się dodać usługę
     */
    public function user_service_admin_add($post);

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function user_service_admin_add_form_get();
}
