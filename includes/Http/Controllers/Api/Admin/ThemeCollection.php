<?php
namespace App\Http\Controllers\Api\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeCollection
{
    public function get(): JsonResponse
    {
        return new JsonResponse([
            "data" => collect(["fusion"])
                ->map(fn($name) => compact("name"))
                ->all(),
        ]);
    }
}
