<?php
namespace App\Theme;

use App\Support\FileSystemContract;
use App\System\Settings;

class TemplateContentService
{
    private FileSystemContract $fileSystem;
    private Settings $settings;
    private TemplateRepository $templateRepository;
    private EditableTemplateRepository $editableTemplateRepository;
    private array $cachedTemplates = [];

    // TODO Add cache contract

    public function __construct(
        FileSystemContract $fileSystem,
        Settings $settings,
        TemplateRepository $templateRepository,
        EditableTemplateRepository $editableTemplateRepository
    ) {
        $this->fileSystem = $fileSystem;
        $this->settings = $settings;
        $this->templateRepository = $templateRepository;
        $this->editableTemplateRepository = $editableTemplateRepository;
    }

    /**
     * Get template's content
     *
     * @param string $name Template's name
     * @param string|null $theme
     * @param string|null $lang
     * @param bool $escapeSlashes Escape template's content
     * @param bool $htmlComments Wrap with comments
     * @return string|null
     * @throws TemplateNotFoundException
     */
    public function get(
        $name,
        $theme,
        $lang,
        $escapeSlashes = false,
        $htmlComments = false
    ): ?string {
        $cacheKey = "$name#$theme#$lang#$escapeSlashes#$htmlComments";

        if (!array_key_exists($cacheKey, $this->cachedTemplates)) {
            $content = $this->read($name, $theme, $lang);

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
     * @param string $name
     * @param string|null $theme
     * @param string|null $lang
     * @return string
     * @throws TemplateNotFoundException
     */
    private function read($name, $theme, $lang): string
    {
        try {
            return $this->readFromDB($name, $theme, $lang);
        } catch (TemplateNotFoundException $e) {
            return $this->getFromFile($theme ?? Config::DEFAULT_THEME, $name, $lang);
        }
    }

    /**
     * @param string $name
     * @param string|null $theme
     * @param string|null $lang
     * @return string
     * @throws TemplateNotFoundException
     */
    private function readFromDB($name, $theme, $lang): string
    {
        if ($this->editableTemplateRepository->isEditable($name)) {
            // TODO Think about it
            $template =
                $this->templateRepository->find($name, $theme, $lang) ??
                ($this->templateRepository->find($name, $theme, null) ??
                    ($this->templateRepository->find($name, null, $lang) ??
                        $this->templateRepository->find($name, null, null)));

            if ($template) {
                return $template->getContent();
            }
        }

        throw new TemplateNotFoundException();
    }

    /**
     * @param string $theme
     * @param string $name
     * @param string|null $lang
     * @return string
     * @throws TemplateNotFoundException
     */
    private function getFromFile($theme, $name, $lang): string
    {
        $path = $this->resolvePath($theme, $name, $lang);
        if ($path === null) {
            throw new TemplateNotFoundException();
        }
        return $this->fileSystem->get($path);
    }

    /**
     * @param string $theme
     * @param string $templateName
     * @param string|null $lang
     * @return string|null
     */
    private function resolvePath($theme, $templateName, $lang): ?string
    {
        foreach ($this->getPossiblePaths($theme, $templateName, $lang) as $path) {
            if ($this->fileSystem->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @param string $theme
     * @param string $templateName
     * @param string|null $lang
     * @return string[]
     */
    private function getPossiblePaths($theme, $templateName, $lang): array
    {
        $paths = [];

        if ($lang) {
            $paths[] = $this->getTemplatePath($theme, $templateName, $lang);
            $paths[] = $this->getTemplatePath($theme, $templateName, $lang, "html");
            $paths[] = $this->getTemplatePath(Config::DEFAULT_THEME, $templateName, $lang);
            $paths[] = $this->getTemplatePath(Config::DEFAULT_THEME, $templateName, $lang, "html");
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
