<?php
namespace App\Requesting;

use App\Loggers\FileLogger;

class Requester
{
    private FileLogger $logger;

    public function __construct(FileLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $url
     * @param array  $query
     * @param array  $headers
     * @param int    $timeout
     * @return Response|null
     */
    public function get($url, array $query = [], array $headers = [], $timeout = 10)
    {
        return $this->curl("GET", $url, $query, [], $headers, $timeout);
    }

    /**
     * @param string $url
     * @param mixed $body
     * @param array $headers
     * @return Response|null
     */
    public function post($url, $body = [], array $headers = [])
    {
        return $this->curl("POST", $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param mixed $body
     * @param array $headers
     * @return Response|null
     */
    public function patch($url, $body = [], array $headers = [])
    {
        return $this->curl("PATCH", $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param mixed $body
     * @param array $headers
     * @return Response|null
     */
    public function put($url, $body = [], array $headers = [])
    {
        return $this->curl("PUT", $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return Response|null
     */
    public function delete($url, array $query = [], array $headers = [])
    {
        return $this->curl("DELETE", $url, $query, [], $headers);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $query
     * @param mixed $body
     * @param array $headers
     * @param int $timeout
     * @return Response|null
     */
    private function curl(
        $method,
        $url,
        array $query = [],
        $body = [],
        array $headers = [],
        $timeout = 10
    ) {
        if (!empty($query)) {
            $url .= "?" . http_build_query($query);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => "gammerce/sklep-sms",
        ]);

        $formattedHeaders = $this->formatHeaders($headers);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $formattedHeaders);

        if (!empty($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            $this->logger->error("CURL request failed", [
                "method" => $method,
                "url" => $url,
                "error" => curl_error($curl),
                "error_no" => curl_errno($curl),
            ]);
            return null;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return new Response($httpCode, $response);
    }

    /**
     * @param array $headers
     * @return array
     */
    private function formatHeaders(array $headers)
    {
        $output = [];

        foreach ($headers as $key => $value) {
            $output[] = "${key}: ${value}";
        }

        return $output;
    }
}
