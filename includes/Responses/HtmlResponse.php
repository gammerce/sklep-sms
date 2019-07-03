<?php
namespace App\Responses;

use Symfony\Component\HttpFoundation\Response;

class HtmlResponse extends Response
{
    public function __construct($output)
    {
        parent::__construct($output, 200, [
            "Expires" => "Sat, 1 Jan 2000 01:00:00 GMT",
            "Last-Modified" => gmdate("D, d M Y H:i:s") . " GMT",
            "Cache-Control" => "no-cache, must-revalidate",
            "Pragma" => "no-cache",
            "Content-Type" => "text/html; charset=\"UTF-8\"",
        ]);
    }
}
