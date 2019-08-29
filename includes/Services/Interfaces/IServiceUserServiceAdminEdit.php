<?php
namespace App\Services\Interfaces;

/**
 * Obsługa edycji usług użytkownika w PA
 */
interface IServiceUserServiceAdminEdit
{
    /**
     * Metoda sprawdza dane formularza podczas edycji usługi użytkownika w PA
     * i gdy wszystko jest okej, to ją edytuje.
     *
     * @param array $post Dane $_POST
     * @param array $user_service Obecne dane edytowanej usługi
     *
     * @return array
     *  'status' => id wiadomości,
     *  'text' => treść wiadomości
     *  'positive' => czy udało się wyedytować usługę
     */
    public function userServiceAdminEdit($post, $user_service);

    /**
     * Metoda powinna zwrócić dodatkowe pola usługi
     * podczas jej edycji w PA
     *
     * @param array $user_service - dane edytowanej usługi
     *
     * @return string
     */
    public function userServiceAdminEditFormGet($user_service);
}
