<?php
namespace App\Http\Responses;

// TODO Use it globally
class SuccessApiResponse extends ApiResponse
{
    public function __construct($text, array $data = [])
    {
        parent::__construct("ok", $text, false, $data);
    }
}
