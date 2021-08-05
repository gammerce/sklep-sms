<?php
namespace App\Http\Responses;

class SuccessApiResponse extends ApiResponse
{
    public function __construct($message, array $data = [])
    {
        parent::__construct("ok", $message, true, $data);
    }
}
