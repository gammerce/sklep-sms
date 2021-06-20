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
    private EditableTemplateRepository $editableTemplateRepository;
    private Translator $lang;
    private array $cachedTemplates = [];

    public function __construct(
        FileSystemContract $fileSystem,
        Settings $settings,
        TemplateRepository $templateRepository,
        EditableTemplateRepository $editableTemplateRepository,
        TranslationManager $translationManager
    ) {
        $this->fileSystem = $fileSystem;
        $this->settings = $settings;
        $this->templateRepository = $templateRepository;
        $this->lang = $translationManager->user();
        $this->editableTemplateRepository = $editableTemplateRepository;
    }

    /**
     * Get template's content
     *
     * @param string $theme
     * @param string $name Template's name
     * @param bool $escapeSlashes Escape template's content
     * @param bool $htmlComments Wrap with comments
     * @return string|null
     * @throws TemplateNotFoundException
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

    /**
     * @param string $theme
     * @param string $name
     * @return string
     * @throws TemplateNotFoundException
     */
    private function read($theme, $name): string
    {
        try {
            return $this->readFromDB($theme, $name);
        } catch (TemplateNotFoundException $e) {
            return $this->getFromFile($theme, $name);
        }
    }

    /**
     * @param string $theme
     * @param string $name
     * @return string
     * @throws TemplateNotFoundException
     */
    private function readFromDB($theme, $name): string
    {
        if ($this->editableTemplateRepository->isEditable($name)) {
            $template = $this->templateRepository->find($theme, $name);
            if ($template) {
                return $template->getContent();
            }

            $template = $this->templateRepository->find(Config::DEFAULT_THEME, $name);
            if ($template) {
                return $template->getContent();
            }
        }

        throw new TemplateNotFoundException();
    }

    /**
     * @param string $theme
     * @param string $name
     * @return string|null
     * @throws TemplateNotFoundException
     */
    private function getFromFile($theme, $name): ?string
    {
        $path = $this->resolvePath($theme, $name);
        if ($path === null) {
            throw new TemplateNotFoundException();
        }
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
            $paths[] = $this->getTemplatePath($theme, $templateName, $language);
            $paths[] = $this->getTemplatePath($theme, $templateName, $language, "html");
            $paths[] = $this->getTemplatePath(Config::DEFAULT_THEME, $templateName, $language);
            $paths[] = $this->getTemplatePath(
                Config::DEFAULT_THEME,
                $templateName,
                $language,
                "html"
            );
        }

        $paths[] = $this->getTemplatePath($theme, $templateName);
        $paths[] = $this->getTemplatePath($theme, $templateName, null, "html");
        $paths[] = $this->getTemplatePath(Config::DEFAULT_THEME, $templateName);
        $paths[] = $this->getTemplatePath(Config::DEFAULT_THEME, $templateName, null, "html");

        return $paths;
    }

    /**
     * @param string $theme
     * @param string $templateName
     * @param string|null $language
     * @param string|null $ext
     * @return string
     */
    private function getTemplatePath($theme, $templateName, $language = null, $ext = null): string
    {
        $output = "themes/$theme/$templateName";
        if ($language) {
            $output .= ".$language";
        }

        if ($ext) {
            $output .= ".$ext";
        }

        return $output;
    }
}
