<?php
namespace App\Kernels;

use App\Routes\RoutesManager;
use Symfony\Component\HttpFoundation\Request;

class IndexKernel extends Kernel
{
    protected $middlewares = [
        //
    ];

    public function run(Request $request)
    {
        /** @var RoutesManager $routesManager */
        $routesManager = $this->app->make(RoutesManager::class);
        return $routesManager->dispatch($request);
    }
}
