<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;

/**
 * Obsluga edycji usług użytkownika przez użytkownika
 */
interface IServiceUserOwnServicesEdit
{
    /**
     * Metoda powinna zwrócić formularz do edycji danych usługi przez użytkownika.
     *
     * @param UserService $userService Dane edytowanej usługi
     *
     * @return string
     */
    public function userOwnServiceEditFormGet(UserService $userService);

    /**
     * Metoda sprawdza dane formularza, podczas edycji usługi użytkownika przez użytkownika
     * i gdy wszystko jest okej, to ją edytuje.
     *
     * @param array $body
     * @param UserService $userService
     * @return bool|array
     */
    public function userOwnServiceEdit(array $body, UserService $userService);
}
