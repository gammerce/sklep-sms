<?php
namespace App\Translation;

use App\System\Path;

class Translator
{
    /** @var Path */
    private $path;

    /**
     * Current language
     *
     * @var string
     */
    private $language;

    /**
     * Language of loaded translations
     *
     * @var string
     */
    private $loadedLanguage;

    /**
     * Array of language => language short
     *
     * @var array
     */
    private $langList = [
        'polish' => 'pl',
        'english' => 'en',
    ];

    /**
     * Array of translations
     *
     * @var array
     */
    private $translations;

    public function __construct($lang = 'polish')
    {
        $this->path = app()->make(Path::class);
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
            !is_dir($this->path->to("translations/" . $language))
        ) {
            return;
        }

        $this->language = $language;
    }

    /**
     * @param string $key
     * @param string ...$args
     * @return string
     */
    public function t($key, ...$args)
    {
        return $this->sprintf($this->translate($key), ...$args);
    }

    /**
     * Strtoupper function
     *
     * @param $string
     * @return string
     */
    public function strtoupper($string)
    {
        return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
    }

    /**
     * Translate key to text
     *
     * @param string $key
     *
     * @return string
     */
    private function translate($key)
    {
        $this->load($this->getCurrentLanguage());

        return array_get($this->translations, $key, $key);
    }

    /**
     * @param $string
     * @return string
     */
    private function sprintf($string)
    {
        $argList = func_get_args();
        $numArgs = count($argList);

        for ($i = 1; $i < $numArgs; $i++) {
            $string = str_replace('{' . $i . '}', $argList[$i], $string);
        }

        return $string;
    }

    private function load($language)
    {
        if ($this->loadedLanguage === $language) {
            return;
        }

        $filesToInclude = [];

        $filesToInclude[] = $this->path->to("translations/general.php");

        foreach (scandir($this->path->to("translations/{$language}")) as $file) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/{$file}");
            }
        }

        foreach (scandir($this->path->to("translations/{$language}/admin")) as $file) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/admin/{$file}");
            }
        }

        foreach (scandir($this->path->to("translations/{$language}/user")) as $file) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/user/{$file}");
            }
        }

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
