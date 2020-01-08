<?php
namespace App\System;

use App\Routes\UrlGenerator;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class Template
{
    /** @var Path */
    private $path;

    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var FileSystemContract */
    private $fileSystem;

    /** @var array */
    private $cachedTemplates = [];

    public function __construct(
        Path $path,
        Settings $settings,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator,
        FileSystemContract $fileSystem
    ) {
        $this->path = $path;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->urlGenerator = $urlGenerator;
        $this->fileSystem = $fileSystem;
    }

    public function render($templateName, array $data = [], $eslashes = true, $htmlcomments = true)
    {
        $template = $this->getTemplate($templateName, $eslashes, $htmlcomments);
        $compiled = $this->compileTemplate($template);
        return $this->evalTemplate($compiled, $data);
    }

    /**
     * Pobranie szablonu.
     *
     * @param string $title Nazwa szablonu
     * @param bool $eslashes Prawda, jeżeli zawartość szablonu ma być "escaped".
     * @param bool $htmlcomments Prawda, jeżeli chcemy dodać komentarze o szablonie.
     *
     * @return string|bool Szablon.
     */
    private function getTemplate($title, $eslashes = true, $htmlcomments = true)
    {
        if (!array_key_exists($title, $this->cachedTemplates)) {
            $path = $this->resolvePath($title);
            $this->cachedTemplates[$title] = $path
                ? $this->readTemplate($path, $title, $htmlcomments, $eslashes)
                : false;
        }

        return $this->cachedTemplates[$title];
    }

    private function resolvePath($title)
    {
        foreach ($this->getPossiblePaths($title) as $path) {
            if ($this->fileSystem->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function getPossiblePaths($title)
    {
        $theme = $this->settings['theme'];
        $language = $this->lang->getCurrentLanguageShort();

        $paths = [];

        if (strlen($language)) {
            $paths[] = "themes/$theme/$title.$language";
            $paths[] = "themes/$theme/$title.$language.html";
            $paths[] = "themes/default/$title.$language";
            $paths[] = "themes/default/$title.$language.html";
        }

        $paths[] = "themes/$theme/$title";
        $paths[] = "themes/$theme/$title.html";
        $paths[] = "themes/default/$title";
        $paths[] = "themes/default/$title.html";

        return $paths;
    }

    private function readTemplate($path, $title, $htmlcomments, $eslashes)
    {
        $template = $this->fileSystem->get($path);

        if ($htmlcomments) {
            $template =
                "<!-- start: " .
                htmlspecialchars($title) .
                " -->\n{$template}\n<!-- end: " .
                htmlspecialchars($title) .
                " -->";
        }

        if ($eslashes) {
            $template = str_replace("\\'", "'", addslashes($template));
        }

        return $template;
    }

    private function evalTemplate($__content, array $data)
    {
        $data = $this->addDefaultVariables($data);
        extract($data);

        $e = function ($value) {
            return htmlspecialchars($value);
        };

        $addSlashes = function ($value) {
            return addslashes($value);
        };

        return eval('return "' . $__content . '";');
    }

    private function addDefaultVariables(array $data)
    {
        if (!array_key_exists('lang', $data)) {
            $data['lang'] = $this->lang;
        }

        if (!array_key_exists('settings', $data)) {
            $data['settings'] = $this->settings;
        }

        if (!array_key_exists('url', $data)) {
            $data['url'] = $this->urlGenerator;
        }

        return $data;
    }

    private function compileTemplate($template)
    {
        return preg_replace(
            ["/{{\s*/", "/\s*}}/", "/{!!\s*/", "/\s*!!}/"],
            ['{$e(', ')}', '{', '}'],
            $template
        );
    }
}
