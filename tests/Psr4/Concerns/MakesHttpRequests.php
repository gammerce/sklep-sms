<?php
namespace Tests\Psr4\Concerns;

use App\Kernels\KernelContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait MakesHttpRequests
{
    protected function call(
        $method,
        $uri,
        array $query = [],
        array $body = [],
        array $headers = []
    ): Response {
        /** @var KernelContract $kernel */
        $kernel = $this->app->make(KernelContract::class);

        $server = collect($headers)
            ->flatMap(function ($value, $key) {
                $snakeKey = strtoupper(str_replace("-", "_", $key));
                return ["HTTP_{$snakeKey}" => $value];
            })
            ->all();

        $request = Request::create($this->prepareUrlForRequest($uri), $method, [], [], [], $server);
        $request->query->replace($this->castValuesToString($query));
        $request->request->replace($this->castValuesToString($body));

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    protected function get($uri, array $query = [], array $headers = []): Response
    {
        return $this->call("GET", $uri, $query, [], $headers);
    }

    protected function getJson($uri, array $query = [], array $headers = []): Response
    {
        return $this->call(
            "GET",
            $uri,
            $query,
            [],
            array_merge(["Accept" => "application/json"], $headers)
        );
    }

    protected function post(
        $uri,
        array $body = [],
        array $query = [],
        array $headers = []
    ): Response {
        return $this->call("POST", $uri, $query, $body, $headers);
    }

    protected function put($uri, array $body = [], array $query = [], array $headers = []): Response
    {
        return $this->call("PUT", $uri, $query, $body, $headers);
    }

    protected function putJson(
        $uri,
        array $body = [],
        array $query = [],
        array $headers = []
    ): Response {
        return $this->call(
            "PUT",
            $uri,
            $query,
            $body,
            array_merge(["Accept" => "application/json"], $headers)
        );
    }

    protected function delete($uri, array $query = [], array $headers = []): Response
    {
        return $this->call("DELETE", $uri, $query, [], $headers);
    }

    protected function decodeJsonResponse(Response $response)
    {
        $decoded = json_decode($response->getContent(), true);
        if (array_get($decoded, "return_id") === "stack_trace") {
            var_dump($decoded["stack_trace"]);
        }
        return $decoded;
    }

    protected function prepareUrlForRequest($uri): string
    {
        return "http://localhost/" . ltrim($uri, "/");
    }

    private function castValuesToString(array $data): array
    {
        return $data;
        //        return collect($data)
        //            ->mapWithKeys(function ($value) {
        //                return $value;
        //            })
        //            ->all();
    }
}
