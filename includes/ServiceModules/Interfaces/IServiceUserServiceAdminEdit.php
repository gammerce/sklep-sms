<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;

/**
 * Support for editing user services in ACP
 */
interface IServiceUserServiceAdminEdit
{
    /**
     * The method checks the form data when editing the user service in ACP
     * and when everything is fine, it edits it.
     *
     * @param array $body
     * @param UserService $userService Current data of the edited service
     * @return bool
     */
    public function userServiceAdminEdit(array $body, UserService $userService): bool;

    /**
     * The method should return additional service fields during its edition in ACP
     *
     * @param UserService $userService
     * @return string
     */
    public function userServiceAdminEditFormGet(UserService $userService): string;
}
