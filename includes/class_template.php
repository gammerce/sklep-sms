<?php

class Templates
{
    public function render($template, $eslashes = true, $htmlcomments = true)
    {
        return 'return "' . $this->get_template($template, $eslashes, $htmlcomments) . '";';
    }

    public function install_render($template, $eslashes = true, $htmlcomments = true)
    {
        return 'return "' . $this->get_install_template($template, $eslashes, $htmlcomments) . '";';
    }

    public function install_full_render($template, $eslashes = true, $htmlcomments = true)
    {
        return 'return "' . $this->get_install_template($template, true, $eslashes, $htmlcomments) . '";';
    }

    public function install_update_render($template, $eslashes = true, $htmlcomments = true)
    {
        return 'return "' . $this->get_install_template($template, false, $eslashes, $htmlcomments) . '";';
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
        global $settings, $lang;

        if (strlen($lang->getCurrentLanguageShort())) {
            $filename = $title . "." . $lang->getCurrentLanguageShort();
            $temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
                if (file_exists($temp)) {
                    $path = $temp;
                }
            }
        }

        if (!isset($path)) {
            $filename = $title;
            $temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
            if (file_exists($temp)) {
                $path = $temp;
            } else {
                $temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
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

    private function get_install_template($title, $full, $eslashes = true, $htmlcomments = true)
    {
        global $lang;

        if (strlen($lang->getCurrentLanguageShort())) {
            $filename = $title . "." . $lang->getCurrentLanguageShort();
            $temp = $this->get_install_path($filename, $full);
            if (file_exists($temp)) {
                $path = $temp;
            }
        }

        if (!isset($path)) {
            $filename = $title;
            $temp = $this->get_install_path($filename, $full);
            if (file_exists($temp)) {
                $path = $temp;
            }
        }

        if (!isset($path)) {
            return false;
        }

        return $this->read_template($path, $title, $htmlcomments, $eslashes);
    }

    private function get_install_path($filename, $full)
    {
        if ($full) {
            return SCRIPT_ROOT . "install/full/templates/{$filename}.html";
        }

        return SCRIPT_ROOT . "install/update/templates/{$filename}.html";
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

        $template = str_replace("{__VERSION__}", VERSION, $template);

        return $template;
    }
}