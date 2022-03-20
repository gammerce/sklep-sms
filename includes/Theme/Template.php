<?php
namespace App\Theme;

use App\Routing\UrlGenerator;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

final class Template
{
    private Settings $settings;
    private Translator $lang;
    private UrlGenerator $urlGenerator;
    private TemplateContentService $templateContentService;
    private ContentEvaluator $contentEvaluator;

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator,
        TemplateContentService $templateContentService,
        ContentEvaluator $contentEvaluator
    ) {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->urlGenerator = $urlGenerator;
        $this->templateContentService = $templateContentService;
        $this->contentEvaluator = $contentEvaluator;
    }

    /**
     * @throws TemplateNotFoundException
     */
    public function render(
        string $templateName,
        array $data = [],
        bool $htmlComments = true
    ): string {
        $template = $this->templateContentService->get(
            $templateName,
            $this->settings->getTheme(),
            $this->lang->getCurrentLanguageShort(),
            $htmlComments
        );
        $data = $this->enrichData($data);
        return $this->contentEvaluator->evaluate($template, $data);
    }

    /**
     * @param string $templateName
     * @param array $data
     * @return string
     * @throws TemplateNotFoundException
     */
    public function renderNoComments($templateName, array $data = []): string
    {
        return $this->render($templateName, $data, false);
    }

    private function enrichData(array $data): array
    {
        $data["lang"] = $data["lang"] ?? $this->lang;
        $data["settings"] = $data["settings"] ?? $this->settings;
        $data["url"] = $data["url"] ?? $this->urlGenerator;
        return $data;
    }
}
