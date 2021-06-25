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

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator,
        TemplateContentService $templateContentService
    ) {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->urlGenerator = $urlGenerator;
        $this->templateContentService = $templateContentService;
    }

    /**
     * @param string $templateName
     * @param array $data
     * @param bool $escapeSlashes
     * @param bool $htmlComments
     * @return string
     * @throws TemplateNotFoundException
     */
    public function render(
        $templateName,
        array $data = [],
        $escapeSlashes = true,
        $htmlComments = true
    ): string {
        $template = $this->templateContentService->get(
            $templateName,
            $this->settings->getTheme(),
            $this->lang->getCurrentLanguageShort(),
            $escapeSlashes,
            $htmlComments
        );
        $compiled = $this->compile($template);
        return $this->eval($compiled, $data);
    }

    /**
     * @param string $templateName
     * @param array $data
     * @return string
     * @throws TemplateNotFoundException
     */
    public function renderNoComments($templateName, array $data = []): string
    {
        return $this->render($templateName, $data, true, false);
    }

    private function eval($__content, array $data): string
    {
        $data = $this->addDefaultVariables($data);
        extract($data);

        $e = fn($value) => htmlspecialchars($value);
        $v = fn($value) => $value;
        $addSlashes = fn($value) => addslashes($value);

        return eval("return \"$__content\";");
    }

    private function addDefaultVariables(array $data): array
    {
        if (!array_key_exists("lang", $data)) {
            $data["lang"] = $this->lang;
        }

        if (!array_key_exists("settings", $data)) {
            $data["settings"] = $this->settings;
        }

        if (!array_key_exists("url", $data)) {
            $data["url"] = $this->urlGenerator;
        }

        return $data;
    }

    private function compile($template): string
    {
        return preg_replace(
            ["/{{\s*/", "/\s*}}/", "/{!!\s*/", "/\s*!!}/"],
            ['{$e(', ")}", '{$v(', ")}"],
            $template
        );
    }
}
