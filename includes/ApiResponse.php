<?php
namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    public function __construct($id, $text = "", $positive = false, $data = [])
    {
        $output['return_id'] = $id;
        $output['text'] = $text;
        $output['positive'] = $positive;

        if (is_array($data) && !empty($data)) {
            $output = array_merge($output, $data);
        }

        parent::__construct($output, 200, [
            "Expires"       => "Sat, 1 Jan 2000 01:00:00 GMT",
            "Last-Modified" => gmdate("D, d M Y H:i:s") . " GMT",
            "Cache-Control" => "no-cache, must-revalidate",
            "Pragma"        => "no-cache",
        ]);
    }
}