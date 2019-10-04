<?php
namespace App\Services\Interfaces;

/**
 * Obsluga edycji usług użytkownika przez użytkownika
 */
interface IServiceUserOwnServicesEdit
{
    /**
     * Metoda powinna zwrócić formularz do edycji danych usługi przez użytkownika.
     *
     * @param array $userService Dane edytowanej usługi
     *
     * @return string
     */
    public function userOwnServiceEditFormGet($userService);

    /**
     * Metoda sprawdza dane formularza, podczas edycji usługi użytkownika przez użytkownika
     * i gdy wszystko jest okej, to ją edytuje.
     *
     * @param array $body
     * @param array $userService Obecne dane edytowanej usługi
     *
     * @return array        'status'    => id wiadomości,
     *                        'text'        => treść wiadomości
     *                        'positive'    => czy udało się wyedytować usługę
     */
    public function userOwnServiceEdit(array $body, $userService);
}
