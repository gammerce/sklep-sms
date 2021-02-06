<?php
namespace App\ServiceModules\Interfaces;

use App\Exceptions\ValidationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle adding user service in ACP
 */
interface IServiceUserServiceAdminAdd
{
    /**
     * Validate form and add user service
     *
     * @param Request $request
     * @return int
     * @throws ValidationException
     */
    public function userServiceAdminAdd(Request $request): int;

    /**
     * Provide additional user service add form fields
     *
     * @return string
     */
    public function userServiceAdminAddFormGet(): string;
}
