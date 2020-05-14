<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonApiResponse extends JsonResponse
{
    public function __construct($id, $text = "", $positive = false, $data = [], $status = 200)
    {
        $output["return_id"] = $id;
        $output["text"] = $text;
        $output["positive"] = $positive;

        if (is_array($data) && !empty($data)) {
            $output = array_merge($output, $data);
        }

        parent::__construct($output, $status);
    }
}
