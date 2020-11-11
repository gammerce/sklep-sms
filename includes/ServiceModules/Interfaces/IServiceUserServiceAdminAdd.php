<?php
namespace App\ServiceModules\Interfaces;

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
     */
    public function userServiceAdminAdd(Request $request);

    /**
     * Provide additional user service add form fields
     *
     * @return string
     */
    public function userServiceAdminAddFormGet();
}
