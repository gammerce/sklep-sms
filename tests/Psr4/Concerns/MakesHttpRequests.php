<?php
namespace Tests\Psr4\Concerns;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait MakesHttpRequests
{
    protected function call($method, $uri, array $query = [], array $body = [], array $headers = [])
    {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $request = Request::create($this->prepareUrlForRequest($uri), $method);
        $request->query->replace($this->castValuesToString($query));
        $request->request->replace($this->castValuesToString($body));
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

    protected function decodeJsonResponse(Response $response)
    {
        $decoded = json_decode($response->getContent(), true);
        if (array_get($decoded, "return_id") === "stack_trace") {
            var_dump($decoded['stack_trace']);
        }
        return $decoded;
    }

    protected function prepareUrlForRequest($uri)
    {
        return 'http://localhost/' . ltrim($uri, "/");
    }

    private function castValuesToString(array $data)
    {
        $output = [];

        foreach ($data as $key => $value) {
            $output[$key] = $value;
        }

        return $output;
    }
}
