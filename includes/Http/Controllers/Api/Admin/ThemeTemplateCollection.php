<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Template;
use App\Repositories\TemplateRepository;
use App\Theme\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeTemplateCollection
{
    public function get(
        $theme,
        ThemeService $themeService,
        TemplateRepository $templateRepository
    ): JsonResponse {
        $templateMapping = collect($templateRepository->listTemplates($theme))->flatMap(
            fn(Template $template) => [$template->getName() => $template]
        );

        return new JsonResponse([
            "data" => collect($themeService->getEditableTemplates())
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
