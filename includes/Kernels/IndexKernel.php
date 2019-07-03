<?php
namespace App\Kernels;

use App\Middlewares\IsUpToDate;
use App\Middlewares\ValidateLicense;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use App\Routes\RoutesManager;
use Symfony\Component\HttpFoundation\Request;

class IndexKernel extends Kernel
{
    protected $middlewares = [
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        ValidateLicense::class,
    ];

    public function run(Request $request)
    {
        /** @var RoutesManager $routesManager */
        $routesManager = $this->app->make(RoutesManager::class);

        return $routesManager->dispatch($request);
    }
}
