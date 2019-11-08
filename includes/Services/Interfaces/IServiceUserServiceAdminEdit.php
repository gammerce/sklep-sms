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
     * @param array $body
     * @param array $userService Obecne dane edytowanej usługi
     *
     * @return array
     *  'status' => id wiadomości,
     *  'text' => treść wiadomości
     *  'positive' => czy udało się wyedytować usługę
     */
    public function userServiceAdminEdit($body, $userService);

    /**
     * Metoda powinna zwrócić dodatkowe pola usługi
     * podczas jej edycji w PA
     *
     * @param array $userService - dane edytowanej usługi
     *
     * @return string
     */
    public function userServiceAdminEditFormGet($userService);
}
