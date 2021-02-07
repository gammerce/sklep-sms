<?php
namespace App\Exceptions;

use App\Requesting\Response;

class LicenseRequestException extends LicenseException
{
    public ?Response $response = null;

    public function __construct(Response $response = null, $previous = null)
    {
        parent::__construct("", 0, $previous);
        $this->response = $response;
    }
}
