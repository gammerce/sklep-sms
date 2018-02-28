<?php
namespace App;

class Translator
{
    /** @var Application */
    protected $app;

    /**
     * Current language
     *
     * @var string
     */
    protected $language;

    /**
     * Language of loaded translations
     *
     * @var string
     */
    protected $loadedLanguage;

    /**
     * Array of language => language short
     *
     * @var array
     */
    protected $langList = [
        'polish'  => 'pl',
        'english' => 'en',
    ];

    /**
     * Array of translations
     *
     * @var array
     */
    protected $translations;

    function __construct($lang = 'polish')
    {
        $this->app = app();
        $this->setLanguage($lang);
    }

    public function getCurrentLanguage()
    {
        return $this->language;
    }

    public function getCurrentLanguageShort()
    {
        return $this->langList[$this->getCurrentLanguage()];
    }

    /**
     * Returns full language name by its shortcut
     *
     * @param string $short
     *
     * @return string
     */
    public function getLanguageByShort($short)
    {
        return array_search(strtolower($short), $this->langList);
    }

    /**
     * Sets current language
     *
     * @param string $language Full language name
     */
    public function setLanguage($language)
    {
        $language = strtolower($language);

        if (
            !strlen($language) ||
            !isset($this->langList[$language]) ||
            $this->getCurrentLanguage() == $language ||
            !is_dir($this->app->path("includes/languages/" . $language))
        ) {
            return;
        }

        $this->language = $language;
    }

    /**
     * Translate key to text
     *
     * @param string $key
     *
     * @return string
     */
    public function translate($key)
    {
        $this->load($this->getCurrentLanguage());

        return array_get($this->translations, $key, $key);
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    public function sprintf($string)
    {
        $arg_list = func_get_args();
        $num_args = count($arg_list);

        for ($i = 1; $i < $num_args; $i++) {
            $string = str_replace('{' . $i . '}', $arg_list[$i], $string);
        }

        return $string;
    }

    /**
     * Strtoupper function
     *
     * @param $string
     *
     * @return string
     */
    public function strtoupper($string)
    {
        return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
    }

    protected function load($language)
    {
        if ($this->loadedLanguage === $language) {
            return;
        }

        $filesToInclude = [];

        // Ładujemy ogólną bibliotekę językową
        $filesToInclude[] = $this->app->path("includes/languages/general.php");

        // Ładujemy ogólne biblioteki językowe języka
        foreach (scandir($this->app->path("includes/languages/{$language}")) as $file) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->app->path("includes/languages/{$language}/{$file}");
            }
        }

        // Ładujemy bilioteki dla PA
        if (admin_session()) {
            foreach (scandir($this->app->path("includes/languages/{$language}/admin")) as $file) {
                if (ends_at($file, ".php")) {
                    $filesToInclude[] = $this->app->path("includes/languages/{$language}/admin/{$file}");
                }
            }
        } else {
            foreach (scandir($this->app->path("includes/languages/{$language}/user")) as $file) {
                if (ends_at($file, ".php")) {
                    $filesToInclude[] = $this->app->path("includes/languages/{$language}/user/{$file}");
                }
            }
        }

        // Dodajemy translacje
        foreach ($filesToInclude as $path) {
            if (!file_exists($path)) {
                continue;
            }

            $l = include $path;

            if (!isset($l) || !is_array($l)) {
                continue;
            }

            foreach ($l as $key => $val) {
                $this->translations[$key] = $val;
            }
        }

        $this->loadedLanguage = $language;
    }
}
