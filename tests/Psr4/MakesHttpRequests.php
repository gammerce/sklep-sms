<?php
namespace Tests\Psr4;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Request;

trait MakesHttpRequests
{
    protected function call($method, $uri, $parameters = [])
    {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $request = Request::create($this->prepareUrlForRequest($uri), $method, $parameters);
        $this->app->instance(Request::class, $request);

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    abstract protected function prepareUrlForRequest($uri);
}
