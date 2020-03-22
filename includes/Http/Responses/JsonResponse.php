<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

class JsonResponse extends BaseJsonResponse
{
    public function __construct($data = null, $status = 200, $headers = [])
    {
        parent::__construct($data, $status, $headers);
        $this->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }
}
