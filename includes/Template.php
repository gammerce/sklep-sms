<?php
namespace App;

use App\Routes\UrlGenerator;

class Template
{
    /** @var Application */
    protected $app;

    /** @var Settings */
    protected $settings;

    /** @var Translator */
    protected $lang;

    /** @var UrlGenerator */
    protected $urlGenerator;

    public function __construct(
        Application $app,
        Settings $settings,
        Translator $lang,
        UrlGenerator $urlGenerator
    ) {
        $this->app = $app;
        $this->settings = $settings;
        $this->lang = $lang;
        $this->urlGenerator = $urlGenerator;
    }

    public function render($template, array $data = [], $eslashes = true, $htmlcomments = true)
    {
        $__content = $this->getTemplate($template, $eslashes, $htmlcomments);

        return $this->evalTemplate($__content, $data);
    }

    public function installRender($template, array $data = [])
    {
        $__content = $this->getInstallTemplate($template, function ($filename) {
            return $this->app->path("install/templates/{$filename}.html");
        });

        return $this->evalTemplate($__content, $data);
    }

    public function installFullRender($template, array $data = [])
    {
        $__content = $this->getInstallTemplate($template, function ($filename) {
            return $this->app->path("install/templates/full/{$filename}.html");
        });

        return $this->evalTemplate($__content, $data);
    }

    public function installUpdateRender($template, array $data = [])
    {
        $__content = $this->getInstallTemplate($template, function ($filename) {
            return $this->app->path("install/templates/update/{$filename}.html");
        });

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
            $temp = $this->app->path("themes/{$this->settings['theme']}/{$filename}.html");
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = $this->app->path("themes/default/{$filename}.html");
                if (file_exists($temp)) {
                    $path = $temp;
                }
            }
        }

        if (!isset($path)) {
            $filename = $title;
            $temp = $this->app->path("themes/{$this->settings['theme']}/{$filename}.html");
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = $this->app->path("themes/default/{$filename}.html");
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

    private function getInstallTemplate($title, callable $pathResolver)
    {
        if (strlen($this->lang->getCurrentLanguageShort())) {
            $filename = $title . "." . $this->lang->getCurrentLanguageShort();
            $temp = call_user_func($pathResolver, $filename);
            if (file_exists($temp)) {
                $path = $temp;
            }
        }

        if (!isset($path)) {
            $filename = $title;
            $temp = call_user_func($pathResolver, $filename);
            if (file_exists($temp)) {
                $path = $temp;
            }
        }

        if (!isset($path)) {
            return false;
        }

        return $this->readTemplate($path, $title, true, true);
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
