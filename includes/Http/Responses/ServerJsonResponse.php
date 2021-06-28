<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

class ServerJsonResponse extends BaseJsonResponse
{
    public function __construct($data = null, $status = self::HTTP_OK, $headers = [])
    {
        parent::__construct($data, $status, $headers);
        $this->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }
}
