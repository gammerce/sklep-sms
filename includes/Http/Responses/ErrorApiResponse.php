<?php
namespace App\Http\Responses;

class ErrorApiResponse extends ApiResponse
{
    public function __construct($message, array $data = [])
    {
        parent::__construct("error", $message, false, $data);
    }
}
