<?php
namespace App\Translation;

use App\Support\FileSystemContract;
use App\Support\Path;

class Translator
{
    private Path $path;
    private FileSystemContract $fileSystem;

    /**
     * Current language
     */
    private string $language;

    /**
     * Language of loaded translations
     */
    private ?string $loadedLanguage = null;

    /**
     * Array of language => language short
     */
    private array $langList = [
        "polish" => "pl",
        "english" => "en",
    ];

    /**
     * Array of translations
     */
    private array $translations = [];

    public function __construct($lang = "polish")
    {
        $this->path = app()->make(Path::class);
        $this->fileSystem = app()->make(FileSystemContract::class);
        $this->setLanguage($lang);
    }

    /**
     * @return string
     */
    public function getCurrentLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getCurrentLanguageShort()
    {
        return $this->langList[$this->getCurrentLanguage()];
    }

    /**
     * @param string $lang
     * @return bool
     */
    public function languageExists($lang)
    {
        return array_key_exists($lang, $this->langList);
    }

    /**
     * Returns full language name by its shortcut
     *
     * @param string $short
     * @return string|null
     */
    public function getLanguageByShort($short)
    {
        $mapping = array_flip($this->langList);
        return array_get($mapping, strtolower($short));
    }

    /**
     * Sets current language
     *
     * @param string $language Full language name
     */
    public function setLanguage($language)
    {
        $language = escape_filename(strtolower($language));

        if (
            !strlen($language) ||
            !isset($this->langList[$language]) ||
            !$this->fileSystem->isDirectory($this->path->to("translations/" . $language))
        ) {
            return;
        }

        $this->language = $language;
    }

    /**
     * @param string $key
     * @param mixed ...$args
     * @return string
     */
    public function t($key, ...$args)
    {
        return $this->sprintf($this->translate($key), ...$args);
    }

    /**
     * Translate key to text
     *
     * @param string $key
     * @return string
     */
    private function translate($key)
    {
        $this->load();
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
            $string = str_replace("{{$i}}", $argList[$i], $string);
        }

        return $string;
    }

    private function load()
    {
        $language = $this->getCurrentLanguage();

        if ($this->loadedLanguage === $language) {
            return;
        }

        $filesToInclude = [];

        $filesToInclude[] = $this->path->to("translations/general.php");

        foreach (
            $this->fileSystem->scanDirectory($this->path->to("translations/{$language}"))
            as $file
        ) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/{$file}");
            }
        }

        foreach (
            $this->fileSystem->scanDirectory($this->path->to("translations/{$language}/admin"))
            as $file
        ) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/admin/{$file}");
            }
        }

        foreach (
            $this->fileSystem->scanDirectory($this->path->to("translations/{$language}/user"))
            as $file
        ) {
            if (ends_at($file, ".php")) {
                $filesToInclude[] = $this->path->to("translations/{$language}/user/{$file}");
            }
        }

        foreach ($filesToInclude as $path) {
            if (!$this->fileSystem->exists($path)) {
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

    /**
     * @return array
     */
    public function getTranslations()
    {
        $this->load();
        return $this->translations;
    }
}
