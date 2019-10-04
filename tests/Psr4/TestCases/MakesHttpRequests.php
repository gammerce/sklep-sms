<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Request;

trait MakesHttpRequests
{
    protected function call($method, $uri, $parameters = [])
    {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $request = Request::create($this->prepareUrlForRequest($uri), $method, $parameters);

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    protected function get($uri, array $query = [])
    {
        return $this->call('GET', $uri, $query);
    }

    protected function post($uri, array $body = [])
    {
        return $this->call('POST', $uri, $body);
    }

    protected function put($uri, array $body = [])
    {
        return $this->call('PUT', $uri, $body);
    }

    abstract protected function prepareUrlForRequest($uri);
}
