<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Theme\TemplateContentService;
use App\Theme\TemplateNotFoundException;
use App\Theme\TemplateRepository;
use App\Theme\EditableTemplateRepository;
use App\Translation\Translator;
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
        TemplateContentService $templateContentService,
        $theme,
        $template,
        $lang = null
    ): JsonResponse {
        $this->guardAgainstInvalidLang($lang);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);

        try {
            $content = $templateContentService->get($theme, $decodedTemplate, $lang);
        } catch (TemplateNotFoundException $e) {
            $content = "";
        }

        return new JsonResponse([
            "name" => $decodedTemplate,
            "content" => $content,
        ]);
    }

    public function put(
        Request $request,
        TemplateRepository $templateRepository,
        $theme,
        $template,
        $lang = null
    ): Response {
        $this->guardAgainstInvalidLang($lang);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);
        $content = trim($request->request->get("content"));

        $templateModel = $templateRepository->find($theme, $decodedTemplate, $lang);

        // Update
        if ($templateModel) {
            $templateRepository->update($templateModel->getId(), $content);
        }
        // Create
        else {
            $templateRepository->create($theme, $decodedTemplate, $lang, $content);
        }

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    public function delete(TemplateRepository $templateRepository, $theme, $template, $lang = null)
    {
        $this->guardAgainstInvalidLang($lang);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);
        $templateModel = $templateRepository->find($theme, $decodedTemplate, $lang);
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

    private function guardAgainstInvalidLang($lang): void
    {
        if ($lang !== null && !Translator::languageShortExists($lang)) {
            throw new EntityNotFoundException();
        }
    }
}
