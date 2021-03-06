<?php
namespace App\Http\Controllers\Api\Admin;

use App\Models\Template;
use App\Theme\EditableTemplateRepository;
use App\Theme\TemplateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class TemplateCollection
{
    public function get(
        Request $request,
        EditableTemplateRepository $editableTemplateRepository,
        TemplateRepository $templateRepository
    ): JsonResponse {
        $theme = $request->query->get("theme");
        $lang = $request->query->get("lang");

        $templateMapping = collect($templateRepository->listTemplates($theme, $lang))->flatMap(
            fn(Template $template) => [$template->getName() => $template]
        );

        return new JsonResponse([
            "data" => collect($editableTemplateRepository->all())
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
