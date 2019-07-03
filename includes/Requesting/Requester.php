<?php
namespace App\Requesting;

class Requester
{
    /**
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return Response|bool
     */
    public function get($url, array $query = [], array $headers = [])
    {
        return $this->curl('GET', $url, $query, [], $headers);
    }

    /**
     * @param string $url
     * @param array $body
     * @param array $headers
     * @return Response|bool
     */
    public function post($url, array $body = [], array $headers = [])
    {
        return $this->curl('POST', $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param array $body
     * @param array $headers
     * @return Response|bool
     */
    public function patch($url, array $body = [], array $headers = [])
    {
        return $this->curl('PATCH', $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param array $body
     * @param array $headers
     * @return Response|bool
     */
    public function put($url, array $body = [], array $headers = [])
    {
        return $this->curl('PUT', $url, [], $body, $headers);
    }

    /**
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return Response
     * @return Response|bool
     */
    public function delete($url, array $query = [], array $headers = [])
    {
        return $this->curl('DELETE', $url, $query, [], $headers);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $query
     * @param array $body
     * @param array $headers
     * @param int $timeout
     * @return Response|bool
     */
    protected function curl(
        $method,
        $url,
        array $query = [],
        array $body = [],
        array $headers = [],
        $timeout = 10
    ) {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'gammerce/sklep-sms'
        ]);

        $headers['Content-Type'] = 'application/json';
        $formattedHeaders = $this->formatHeaders($headers);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $formattedHeaders);

        if (!empty($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($curl);

        if ($response === false) {
            return false;
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return new Response($httpCode, $response);
    }

    protected function formatHeaders(array $headers)
    {
        $output = [];

        foreach ($headers as $key => $value) {
            $output[] = "${key}: ${value}";
        }

        return $output;
    }
}
