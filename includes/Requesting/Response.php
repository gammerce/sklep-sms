<?php
namespace App\Requesting;

final class Response
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $body;

    public function __construct($statusCode, $body)
    {
        $this->statusCode = $statusCode;
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
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return bool
     */
    public function is3xx()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * @return bool
     */
    public function is4xx()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * @return bool
     */
    public function is5xx()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
