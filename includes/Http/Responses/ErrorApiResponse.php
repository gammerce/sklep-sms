<?php
namespace App\Http\Responses;

// TODO Use it globally
class ErrorApiResponse extends ApiResponse
{
    public function __construct($text, array $data = [])
    {
        parent::__construct("error", $text, false, $data);
    }
}
