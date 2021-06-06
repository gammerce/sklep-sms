<?php
namespace App\Http\Controllers\Api\Admin;

use App\Theme\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeTemplateCollection
{
    public function get(ThemeService $themeService): JsonResponse
    {
        return new JsonResponse([
            "data" => collect($themeService->getEditableTemplates())
                ->map(fn($name) => compact("name"))
                ->all(),
        ]);
    }
}
