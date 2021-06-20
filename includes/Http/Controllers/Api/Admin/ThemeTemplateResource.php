<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Theme\TemplateContentService;
use App\Theme\TemplateNotFoundException;
use App\Theme\TemplateRepository;
use App\Theme\EditableTemplateRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThemeTemplateResource
{
    private EditableTemplateRepository $editableTemplateRepository;

    public function __construct(EditableTemplateRepository $editableTemplateRepository)
    {
        $this->editableTemplateRepository = $editableTemplateRepository;
    }

    public function get(
        $theme,
        $template,
        TemplateContentService $templateContentService
    ): JsonResponse {
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);

        try {
            $content = $templateContentService->get($theme, $decodedTemplate);
        } catch (TemplateNotFoundException $e) {
            $content = "";
        }

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
        $template = str_replace("-", "/", $name);

        if (!in_array($template, $this->editableTemplateRepository->list())) {
            throw new EntityNotFoundException();
        }

        return $template;
    }
}
