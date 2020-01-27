<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class HtmlResponse extends Response
{
    public function __construct($output, $status = 200)
    {
        parent::__construct($output, $status, [
            "Expires" => "Sat, 1 Jan 2000 01:00:00 GMT",
            "Last-Modified" => gmdate("D, d M Y H:i:s") . " GMT",
            "Cache-Control" => "no-cache, must-revalidate",
            "Pragma" => "no-cache",
            "Content-Type" => "text/html; charset=\"UTF-8\"",
        ]);
    }
}
