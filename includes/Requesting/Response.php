<?php
namespace App\Requesting;

class Response
{
    /** @var int */
    protected $responseCode;

    /** @var string */
    protected $body;

    public function __construct($responseCode, $body)
    {
        $this->responseCode = $responseCode;
        $this->body = $body;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return $this->is2xx();
    }

    /**
     * @return bool
     */
    public function isBadResponse()
    {
        return $this->is4xx() || $this->is5xx();
    }

    /**
     * @return array
     */
    public function json()
    {
        return json_decode($this->body, true);
    }

    /**
     * @return bool
     */
    public function is2xx()
    {
        return $this->responseCode >= 200 && $this->responseCode < 300;
    }

    /**
     * @return bool
     */
    public function is3xx()
    {
        return $this->responseCode >= 300 && $this->responseCode < 400;
    }

    /**
     * @return bool
     */
    public function is4xx()
    {
        return $this->responseCode >= 400 && $this->responseCode < 500;
    }

    /**
     * @return bool
     */
    public function is5xx()
    {
        return $this->responseCode >= 500 && $this->responseCode < 600;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}