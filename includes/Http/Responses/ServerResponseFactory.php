<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\AcceptHeader;

class ServerResponseFactory
{
    public function create(
        AcceptHeader $acceptHeader,
        $status,
        $text,
        $positive,
        array $data = [],
        $statusCode = 200
    ) {
        if ($acceptHeader->has("application/json")) {
            return new JsonResponse(
                array_merge(
                    [
                        'status' => $status,
                        'text' => $text,
                    ],
                    $data
                ),
                $statusCode
            );
        }

        if ($acceptHeader->has("application/assoc")) {
            return new AssocResponse(
                array_merge(
                    [
                        'status' => $status,
                        'text' => $text,
                    ],
                    $data
                ),
                $statusCode
            );
        }

        return new XmlResponse(
            array_merge(
                [
                    'return_value' => $status,
                    'text' => $text,
                    'positive' => $positive ? 1 : 0,
                ],
                $data
            ),
            $statusCode
        );
    }
}
