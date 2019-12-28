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

    public function __construct(
        Path $path,
        Settings $settings,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator
    ) {
        $this->path = $path;
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->urlGenerator = $urlGenerator;
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
        $path = $this->resolvePath($title);

        if (!$path) {
            return false;
        }

        return $this->readTemplate($path, $title, $htmlcomments, $eslashes);
    }

    private function resolvePath($title)
    {
        if (strlen($this->lang->getCurrentLanguageShort())) {
            $filename = $title . "." . $this->lang->getCurrentLanguageShort();
            $path = $this->path->to("themes/{$this->settings['theme']}/{$filename}.html");
            if (file_exists($path)) {
                return $path;
            }

            $path = $this->path->to("themes/default/{$filename}.html");
            if (file_exists($path)) {
                return $path;
            }
        }

        $filename = $title;
        $path = $this->path->to("themes/{$this->settings['theme']}/{$filename}.html");
        if (file_exists($path)) {
            return $path;
        }

        $path = $this->path->to("themes/default/{$filename}.html");
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    private function readTemplate($path, $title, $htmlcomments, $eslashes)
    {
        $template = file_get_contents($path);

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
