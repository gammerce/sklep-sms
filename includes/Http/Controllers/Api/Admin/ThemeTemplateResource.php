<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Repositories\TemplateRepository;
use App\Theme\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeTemplateResource
{
    private ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function get($theme, $template, ThemeService $themeService): JsonResponse
    {
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);
        $content = $themeService->getTemplateContent($theme, $decodedTemplate);

        return new JsonResponse([
            "name" => $decodedTemplate,
            "content" => $content,
        ]);
    }

    public function put(
        $theme,
        $template,
        Request $request,
        TemplateRepository $templateRepository
    ): Response {
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);
        $content = trim($request->request->get("content"));
        $templateModel = $templateRepository->find($theme, $decodedTemplate);

        // Update
        if ($templateModel) {
            $templateRepository->update($templateModel->getId(), $content);
        }
        // Create
        else {
            $templateRepository->create($theme, $decodedTemplate, $content);
        }

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    public function delete($theme, $template, TemplateRepository $templateRepository)
    {
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);
        $templateModel = $templateRepository->find($theme, $decodedTemplate);
        if (!$templateModel) {
            throw new EntityNotFoundException();
        }

        $templateRepository->delete($templateModel->getId());

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    private function guardAgainstInvalidTemplate($name): string
    {
        $template = $this->themeService->resolveTemplate($name);

        if (!in_array($template, $this->themeService->getEditableTemplates())) {
            throw new EntityNotFoundException();
        }

        return $template;
    }
}
