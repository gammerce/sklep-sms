<?php
namespace App\Http\Responses;

class ErrorApiResponse extends ApiResponse
{
    public function __construct($text, $data = [])
    {
        parent::__construct("error", $text, false, $data);
    }
}
