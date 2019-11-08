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

    public function render($template, array $data = [], $eslashes = true, $htmlcomments = true)
    {
        $__content = $this->getTemplate($template, $eslashes, $htmlcomments);

        return $this->evalTemplate($__content, $data);
    }

    private function evalTemplate($__content, array $data)
    {
        $data = $this->addDefaultVariables($data);
        extract($data);

        return eval('return "' . $__content . '";');
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
        if (strlen($this->lang->getCurrentLanguageShort())) {
            $filename = $title . "." . $this->lang->getCurrentLanguageShort();
            $temp = $this->path->to("themes/{$this->settings['theme']}/{$filename}.html");
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = $this->path->to("themes/default/{$filename}.html");
                if (file_exists($temp)) {
                    $path = $temp;
                }
            }
        }

        if (!isset($path)) {
            $filename = $title;
            $temp = $this->path->to("themes/{$this->settings['theme']}/{$filename}.html");
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = $this->path->to("themes/default/{$filename}.html");
                if (file_exists($temp)) {
                    $path = $temp;
                }
            }
        }

        if (!isset($path)) {
            return false;
        }

        return $this->readTemplate($path, $title, $htmlcomments, $eslashes);
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

    public function addDefaultVariables(array $data)
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
}
