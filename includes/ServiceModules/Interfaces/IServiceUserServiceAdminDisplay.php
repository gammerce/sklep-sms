<?php
namespace App\ServiceModules\Interfaces;

use App\View\Html\Wrapper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle user service in ACP
 */
interface IServiceUserServiceAdminDisplay
{
    /**
     * Provides page title
     *
     * @return string
     */
    public function userServiceAdminDisplayTitleGet(): string;

    /**
     * Provides list of users' services
     *
     * @param Request $request
     * @return Wrapper | string
     */
    public function userServiceAdminDisplayGet(Request $request);
}
