<?php
namespace App\Theme;

use App\Support\FileSystemContract;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class TemplateContentService
{
    private FileSystemContract $fileSystem;
    private Settings $settings;
    private TemplateRepository $templateRepository;
    private Translator $lang;
    private array $cachedTemplates = [];

    public function __construct(
        FileSystemContract $fileSystem,
        Settings $settings,
        TemplateRepository $templateRepository,
        TranslationManager $translationManager
    ) {
        $this->fileSystem = $fileSystem;
        $this->settings = $settings;
        $this->templateRepository = $templateRepository;
        $this->lang = $translationManager->user();
    }

    /**
     * Get template's content
     *
     * @param string $theme
     * @param string $name Template's name
     * @param bool $escapeSlashes Escape template's content
     * @param bool $htmlComments Wrap with comments
     *
     * @return string|null
     */
    public function get($theme, $name, $escapeSlashes = false, $htmlComments = false): ?string
    {
        $cacheKey = "$theme#$name#$escapeSlashes#$htmlComments";

        if (!array_key_exists($cacheKey, $this->cachedTemplates)) {
            $content = $this->read($theme, $name);

            if ($htmlComments) {
                $content =
                    "<!-- start: " .
                    htmlspecialchars($name) .
                    " -->\n{$content}\n<!-- end: " .
                    htmlspecialchars($name) .
                    " -->";
            }

            if ($escapeSlashes) {
                $content = str_replace("\\'", "'", addslashes($content));
            }

            $this->cachedTemplates[$cacheKey] = $content;
        }

        return $this->cachedTemplates[$cacheKey];
    }

    private function read($theme, $name): ?string
    {
        $template = $this->templateRepository->find($theme, $name);
        if ($template) {
            return $template->getContent();
        }

        $template = $this->templateRepository->find(Config::DEFAULT_THEME, $name);
        if ($template) {
            return $template->getContent();
        }

        return $this->getFromFile($theme, $name);
    }

    private function getFromFile($theme, $name): ?string
    {
        $path = $this->resolvePath($theme, $name);
        return $this->fileSystem->get($path);
    }

    /**
     * @param string $theme
     * @param string $templateName
     * @return string|null
     */
    private function resolvePath($theme, $templateName): ?string
    {
        foreach ($this->getPossiblePaths($theme, $templateName) as $path) {
            if ($this->fileSystem->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param string $theme
     * @param string $templateName
     * @return string[]
     */
    private function getPossiblePaths($theme, $templateName): array
    {
        $language = $this->lang->getCurrentLanguageShort();

        $paths = [];

        if (strlen($language)) {
            $paths[] = "themes/$theme/$templateName.$language";
            $paths[] = "themes/$theme/$templateName.$language.html";
            $paths[] = "themes/{${Config::DEFAULT_THEME}}/$templateName.$language";
            $paths[] = "themes/{${Config::DEFAULT_THEME}}/$templateName.$language.html";
        }

        $paths[] = "themes/$theme/$templateName";
        $paths[] = "themes/$theme/$templateName.html";
        $paths[] = "themes/{${Config::DEFAULT_THEME}}/$templateName";
        $paths[] = "themes/{${Config::DEFAULT_THEME}}/$templateName.html";

        return $paths;
    }
}
