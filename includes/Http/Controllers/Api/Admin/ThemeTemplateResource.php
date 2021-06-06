<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Support\FileSystemContract;
use App\Theme\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;

class ThemeTemplateResource
{
    public function get(
        $template,
        ThemeService $themeService,
        FileSystemContract $fileSystem
    ): JsonResponse {
        $decodedTemplate = str_replace("-", "/", $template);

        if (!in_array($decodedTemplate, $themeService->getEditableTemplates())) {
            throw new EntityNotFoundException();
        }

        $content = $fileSystem->get("themes/fusion/{$decodedTemplate}.html");

        return new JsonResponse([
            "name" => $decodedTemplate,
            "content" => $content,
        ]);
    }
}
