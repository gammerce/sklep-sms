<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    public function __construct($returnValue, $text, $positive, $extraData = "")
    {
        $output = "<return_value>{$returnValue}</return_value>";
        $output .= "<text>{$text}</text>";
        $output .= "<positive>{$positive}</positive>";
        $output .= $extraData;

        parent::__construct($output, 200, [
            "Content-type" => 'text/plain; charset="UTF-8"',
        ]);
    }
}
