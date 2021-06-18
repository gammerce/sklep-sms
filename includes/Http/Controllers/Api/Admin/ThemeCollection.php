<?php
namespace App\Http\Controllers\Api\Admin;

use App\Repositories\TemplateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeCollection
{
    public function get(TemplateRepository $templateRepository): JsonResponse
    {
        $themes = $templateRepository->listThemes();
        if (!in_array("fusion", $themes, true)) {
            $themes = array_merge(["fusion"], $themes);
        }

        return new JsonResponse([
            "data" => collect($themes)
                ->map(fn($name) => compact("name"))
                ->all(),
        ]);
    }
}
