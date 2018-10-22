<?php
namespace App\Exceptions;

use App\Requesting\Response;
use Exception;

class InvalidResponse extends Exception
{
    /** @var Response */
    public $response;

    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}