<?php
namespace App\Http\Controllers\Api\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use App\Theme\ThemeRepository;

class ThemeCollection
{
    public function get(ThemeRepository $themeService): JsonResponse
    {
        $data = collect($themeService->list())
            ->map(fn($name) => compact("name"))
            ->all();

        return new JsonResponse(compact("data"));
    }
}
