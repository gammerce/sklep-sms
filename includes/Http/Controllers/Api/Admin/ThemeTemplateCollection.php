<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Template;
use App\Theme\TemplateRepository;
use App\Theme\EditableTemplateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeTemplateCollection
{
    public function get(
        $theme,
        EditableTemplateRepository $editableTemplateRepository,
        TemplateRepository $templateRepository
    ): JsonResponse {
        $templateMapping = collect($templateRepository->listTemplates($theme))->flatMap(
            fn(Template $template) => [$template->getName() => $template]
        );

        return new JsonResponse([
            "data" => collect($editableTemplateRepository->list())
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
