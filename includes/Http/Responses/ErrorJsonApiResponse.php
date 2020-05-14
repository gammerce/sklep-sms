<?php
namespace App\Http\Responses;

class ErrorJsonApiResponse extends JsonApiResponse
{
    public function __construct($text, array $data = [])
    {
        parent::__construct("error", $text, false, $data);
    }
}
