<?php
namespace Tests\Psr4;

use App\Exceptions\RequireInstallationException;
use App\Kernels\KernelContract;
use App\ShopState;
use Symfony\Component\HttpFoundation\Request;

trait MakesHttpRequests
{
    protected function call($method, $uri, $parameters = [])
    {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $request = Request::create($this->prepareUrlForRequest($uri), $method, $parameters);
        $this->app->instance(Request::class, $request);

        $app = $this->app;
        require __DIR__ . '/../../bootstrap/app_global.php';

        if (!ShopState::isInstalled() || !$this->app->make(ShopState::class)->isUpToDate()) {
            throw new RequireInstallationException();
        }

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    abstract protected function prepareUrlForRequest($uri);
}
