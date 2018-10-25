<?php
namespace App;

class Template
{
    /** @var Application */
    protected $app;

    /** @var Settings */
    protected $settings;

    /** @var Translator */
    protected $lang;

    public function __construct(Application $app, Settings $settings, Translator $lang)
    {
        $this->app = $app;
        $this->settings = $settings;
        $this->lang = $lang;
    }

    public function render2($template, array $data = [], $eslashes = true, $htmlcomments = true)
    {
        $__content = $this->get_template($template, $eslashes, $htmlcomments);

        $data = $this->addDefaultVariables($data);
        extract($data);

        return eval('return "' . $__content . '";');
    }

    public function render($template, $eslashes = true, $htmlcomments = true)
    {
        $__content = $this->get_template($template, $eslashes, $htmlcomments);
        return 'return "' . $__content . '";';
    }

    public function install_render($template, array $data = [])
    {
        $__content = $this->get_install_template($template, function ($filename) {
            return $this->app->path("install/templates/{$filename}.html");
        });

        $data = $this->addDefaultVariables($data);
        extract($data);

        return eval('return "' . $__content . '";');
    }

    public function install_full_render($template, array $data = [])
    {
        $__content = $this->get_install_template($template, function ($filename) {
            return $this->app->path("install/templates/full/{$filename}.html");
        });

        $data = $this->addDefaultVariables($data);
        extract($data);

        return eval('return "' . $__content . '";');
    }

    public function install_update_render($template, array $data = [])
    {
        $__content = $this->get_install_template($template, function ($filename) {
            return $this->app->path("install/templates/update/{$filename}.html");
        });

        $data = $this->addDefaultVariables($data);
        extract($data);

        return eval('return "' . $__content . '";');
    }

    /**
     * Pobranie szablonu.
     *
     * @param string $title Nazwa szablonu
     * @param bool   $eslashes Prawda, jeżeli zawartość szablonu ma być "escaped".
     * @param bool   $htmlcomments Prawda, jeżeli chcemy dodać komentarze o szablonie.
     *
     * @return string|bool Szablon.
     */
    private function get_template($title, $eslashes = true, $htmlcomments = true)
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

        return $this->read_template($path, $title, $htmlcomments, $eslashes);
    }

    private function get_install_template($title, callable $pathResolver)
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

        return $this->read_template($path, $title, true, true);
    }

    private function read_template($path, $title, $htmlcomments, $eslashes)
    {
        $template = file_get_contents($path);

        if ($htmlcomments) {
            $template = "<!-- start: " . htmlspecialchars($title) . " -->\n{$template}\n<!-- end: " . htmlspecialchars($title) . " -->";
        }

        if ($eslashes) {
            $template = str_replace("\\'", "'", addslashes($template));
        }

        $template = str_replace("{__VERSION__}", $this->app->version(), $template);

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

        return $data;
    }
}
