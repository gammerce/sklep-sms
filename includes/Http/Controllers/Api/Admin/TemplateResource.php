<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Theme\Config;
use App\Theme\TemplateContentService;
use App\Theme\TemplateNotFoundException;
use App\Theme\TemplateRepository;
use App\Theme\EditableTemplateRepository;
use App\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TemplateResource
{
    private EditableTemplateRepository $editableTemplateRepository;

    public function __construct(EditableTemplateRepository $editableTemplateRepository)
    {
        $this->editableTemplateRepository = $editableTemplateRepository;
    }

    public function get(
        $template,
        Request $request,
        TemplateContentService $templateContentService,
        TemplateRepository $templateRepository
    ): JsonResponse {
        $theme = $request->query->get("theme");
        $lang = $request->query->get("lang");

        $this->guardAgainstInvalidLang($lang);
        $this->guardAgainstInvalidTheme($theme);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);

        $templateModel = $templateRepository->find($decodedTemplate, $theme, $lang);
        if ($templateModel) {
            $content = $templateModel->getContent();
        } else {
            try {
                $content = $templateContentService->readFromFile(
                    $theme ?? Config::DEFAULT_THEME,
                    $decodedTemplate,
                    $lang
                );
            } catch (TemplateNotFoundException $e) {
                $content = "";
            }
        }

        return new JsonResponse([
            "name" => $decodedTemplate,
            "content" => $content,
        ]);
    }

    public function put(
        $template,
        Request $request,
        TemplateRepository $templateRepository
    ): Response {
        $theme = $request->query->get("theme");
        $lang = $request->query->get("lang");
        $content = trim($request->request->get("content"));

        $this->guardAgainstInvalidLang($lang);
        $this->guardAgainstInvalidTheme($theme);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);

        $templateModel = $templateRepository->find($decodedTemplate, $theme, $lang);

        // Update
        if ($templateModel) {
            $templateRepository->update($templateModel->getId(), $content);
        }
        // Create
        else {
            $templateRepository->create($decodedTemplate, $theme, $lang, $content);
        }

        return new Response("", Response::HTTP_NO_CONTENT);
    }

    public function delete(
        $template,
        Request $request,
        TemplateRepository $templateRepository
    ): Response {
        $theme = $request->query->get("theme");
        $lang = $request->query->get("lang");

        $this->guardAgainstInvalidLang($lang);
        $this->guardAgainstInvalidTheme($theme);
        $decodedTemplate = $this->guardAgainstInvalidTemplate($template);

        $templateModel = $templateRepository->find($decodedTemplate, $theme, $lang);
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

    private function guardAgainstInvalidTheme($theme): void
    {
        if ($theme === TemplateRepository::DEFAULT) {
            throw new EntityNotFoundException();
        }
    }
}
