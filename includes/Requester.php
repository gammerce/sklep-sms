<?php
namespace App;

class Requester
{
    public function get($url, array $query = [])
    {
        return $this->curl($url . '?' . http_build_query($query));
    }

    public function post($url, array $body = [])
    {
        return $this->curl($url, 10, true, $body);
    }

    /**
     * @param string $url
     * @param int $timeout
     * @param bool $post
     * @param array $data
     *
     * @return string
     */
    protected function curl($url, $timeout = 10, $post = false, $data = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'gammerce/sklep-sms',
        ]);

        if ($post) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['params' => $data]));
        }

        $resp = curl_exec($curl);
        if ($resp === false) {
            return false;
        }

        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $http_code == 200 ? $resp : '';
    }
}
