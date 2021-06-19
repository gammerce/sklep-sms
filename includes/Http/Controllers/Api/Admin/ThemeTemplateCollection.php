<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Template;
use App\Theme\TemplateRepository;
use App\Theme\TemplateService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeTemplateCollection
{
    public function get(
        $theme,
        TemplateService $templateService,
        TemplateRepository $templateRepository
    ): JsonResponse {
        $templateMapping = collect($templateRepository->listTemplates($theme))->flatMap(
            fn(Template $template) => [$template->getName() => $template]
        );

        return new JsonResponse([
            "data" => collect($templateService->listEditable())
                ->map(
                    fn($name) => [
                        "name" => $name,
                        "deletable" => $templateMapping->offsetExists($name),
                    ]
                )
                ->all(),
        ]);
    }
}
