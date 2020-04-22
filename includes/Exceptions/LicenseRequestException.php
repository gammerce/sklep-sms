<?php
namespace App\Exceptions;

use App\Requesting\Response;
use Exception;

class LicenseRequestException extends LicenseException
{
    /** @var Response|null */
    public $response;

    public function __construct(Response $response = null, Exception $previous = null)
    {
        parent::__construct("", 0, $previous);
        $this->response = $response;
    }
}
