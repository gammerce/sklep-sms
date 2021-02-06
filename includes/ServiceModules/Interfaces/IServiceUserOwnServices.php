<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;

/**
 * Support for displaying its services to the user
 */
interface IServiceUserOwnServices
{
    /**
     * The method should return information about the user service.
     * These are then displayed on the user_own_services page
     *
     * @param UserService $userService
     * @param string $buttonEdit Button to edit the service
     * (if the module is to be able to edit services by the user,
     * you must include this button in the service information)
     * @return string
     */
    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit): string;
}
