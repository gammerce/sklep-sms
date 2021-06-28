<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{
    public function __construct(
        $code,
        $message = "",
        $positive = false,
        array $data = [],
        $status = self::HTTP_OK
    ) {
        $output["return_id"] = $code;
        $output["text"] = $message;
        $output["positive"] = $positive;

        if (!empty($data)) {
            $output = array_merge($output, $data);
        }

        parent::__construct($output, $status);
    }
}
