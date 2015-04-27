<?php

class Language
{

    private $language;

    function __construct($lang = "polish")
    {
        $this->set_language($lang);
    }

    public function get_current_language()
    {
        return $this->language;
    }

    public function set_language($language)
    {
        $language = escape_filename($language);
        if (!is_dir(SCRIPT_ROOT . "includes/languages/" . $language))
            return;

        global $lang;
        $this->language = $language;

        // Ładujemy ogólną bibliotekę językową
        if (file_exists(SCRIPT_ROOT . "includes/languages/{$language}/{$language}.php"))
            include SCRIPT_ROOT . "includes/languages/{$language}/{$language}.php";

        if (in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin"))) { // Ładujemy bilioteki dla PA
            // Ładujemy wszystkie biblioteki językowe
            foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/admin") as $file) {
                if (substr($file, -4) == ".php")
                    include SCRIPT_ROOT . "includes/languages/{$language}/admin/{$file}";
            }
        } else {
            // Ładujemy wszystkie biblioteki językowe
            foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}") as $file) {
                if (substr($file, -4) == ".php" && $file != "{$language}.php")
                    include SCRIPT_ROOT . "includes/languages/{$language}/{$file}";
            }
        }
    }

}