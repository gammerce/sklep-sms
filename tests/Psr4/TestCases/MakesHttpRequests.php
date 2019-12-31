<?php
namespace Tests\Psr4\TestCases;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Request;

trait MakesHttpRequests
{
    protected function call($method, $uri, array $query = [], array $body = [], array $headers = [])
    {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $request = Request::create($this->prepareUrlForRequest($uri), $method);
        $request->query->replace($query);
        $request->request->replace($body);
        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    protected function get($uri, array $query = [], array $headers = [])
    {
        return $this->call('GET', $uri, $query, [], $headers);
    }

    protected function post($uri, array $body = [], array $query = [], array $headers = [])
    {
        return $this->call('POST', $uri, $query, $body, $headers);
    }

    protected function put($uri, array $body = [], array $query = [], array $headers = [])
    {
        return $this->call('PUT', $uri, $query, $body, $headers);
    }

    protected function delete($uri, array $query = [], array $headers = [])
    {
        return $this->call('DELETE', $uri, $query, [], $headers);
    }

    abstract protected function prepareUrlForRequest($uri);
}
