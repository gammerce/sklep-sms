<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;

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
     * @param UserService $userService Obecne dane edytowanej usługi
     * @return bool
     */
    public function userServiceAdminEdit(array $body, UserService $userService);

    /**
     * Metoda powinna zwrócić dodatkowe pola usługi
     * podczas jej edycji w PA
     *
     * @param UserService $userService
     *
     * @return string
     */
    public function userServiceAdminEditFormGet(UserService $userService);
}
