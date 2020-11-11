<?php
namespace App\ServiceModules\Interfaces;

use App\Models\UserService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle editing user service by an owner
 */
interface IServiceUserOwnServicesEdit
{
    /**
     * Provide user service edit form
     *
     * @param UserService $userService
     * @return string
     */
    public function userOwnServiceEditFormGet(UserService $userService);

    /**
     * Validate form and edit user service
     *
     * @param Request $request
     * @param UserService $userService
     * @return bool|array
     */
    public function userOwnServiceEdit(Request $request, UserService $userService);
}
