<?php
namespace App\Http\Controllers\Api\Admin;

use App\Theme\TemplateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeCollection
{
    public function get(TemplateRepository $templateRepository): JsonResponse
    {
        $data = collect($templateRepository->listThemes())
            ->map(fn($name) => compact("name"))
            ->all();

        return new JsonResponse(compact("data"));
    }
}
