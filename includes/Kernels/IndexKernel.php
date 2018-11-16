<?php
namespace App\Kernels;

use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\RunCron;
use App\Middlewares\SetLanguage;
use App\Middlewares\UpdateUserActivity;
use App\Routes\RoutesManager;
use Symfony\Component\HttpFoundation\Request;

class IndexKernel extends Kernel
{
    protected $middlewares = [
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
        UpdateUserActivity::class,
        RunCron::class,
    ];

    public function run(Request $request)
    {
        /** @var RoutesManager $routesManager */
        $routesManager = $this->app->make(RoutesManager::class);

        return $routesManager->dispatch($request);
    }
}
