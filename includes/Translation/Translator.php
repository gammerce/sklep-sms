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
     * Array of translations
     */
    private array $translations = [];

    /**
     * Array of language => language short
     * @return string[]
     */
    public static function langList(): array
    {
        return [
            "polish" => "pl",
            "english" => "en",
        ];
    }

    /**
     * @param string $lang
     * @return bool
     */
    public static function languageExists($lang): bool
    {
        return array_key_exists($lang, self::langList());
    }

    /**
     * @param string $lang
     * @return bool
     */
    public static function languageShortExists($lang): bool
    {
        return in_array($lang, array_values(self::langList()), true);
    }

    /**
     * Returns full language name by its shortcut
     *
     * @param string $short
     * @return string|null
     */
    public static function getLanguageByShort($short): ?string
    {
        $mapping = array_flip(self::langList());
        return array_get($mapping, strtolower($short));
    }

    public function __construct($lang = "polish")
    {
        $this->path = app()->make(Path::class);
        $this->fileSystem = app()->make(FileSystemContract::class);
        $this->setLanguage($lang);
    }

    public function getCurrentLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getCurrentLanguageShort(): string
    {
        return self::langList()[$this->getCurrentLanguage()];
    }

    /**
     * Sets current language
     *
     * @param string $language Full language name
     */
    public function setLanguage($language): void
    {
        $language = escape_filename(strtolower($language));

        if (
            !strlen($language) ||
            !array_key_exists($language, $this->langList()) ||
            !$this->fileSystem->isDirectory($this->path->to("translations/{$language}"))
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
    public function t($key, ...$args): string
    {
        return $this->sprintf($this->translate($key), ...$args);
    }

    /**
     * Translate key to text
     *
     * @param string $key
     * @return string
     */
    private function translate($key): string
    {
        $this->load();
        return array_get($this->translations, $key, $key);
    }

    /**
     * @param $string
     * @return string
     */
    private function sprintf($string): string
    {
        $argList = func_get_args();
        $numArgs = count($argList);

        for ($i = 1; $i < $numArgs; $i++) {
            $string = str_replace("{{$i}}", $argList[$i], $string);
        }

        return $string;
    }

    private function load(): void
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

    public function getTranslations(): array
    {
        $this->load();
        return $this->translations;
    }
}
