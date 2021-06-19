<?php
namespace App\Theme;

use App\Routing\UrlGenerator;
use App\Support\FileSystemContract;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

final class Template
{
    private Settings $settings;
    private Translator $lang;
    private UrlGenerator $urlGenerator;
    private FileSystemContract $fileSystem;
    private array $cachedTemplates = [];

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        UrlGenerator $urlGenerator,
        FileSystemContract $fileSystem
    ) {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
        $this->urlGenerator = $urlGenerator;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param string $templateName
     * @param array $data
     * @param bool $escapeSlashes
     * @param bool $htmlComments
     * @return string
     */
    public function render(
        $templateName,
        array $data = [],
        $escapeSlashes = true,
        $htmlComments = true
    ): string {
        $template = $this->getTemplate($templateName, $escapeSlashes, $htmlComments);
        $compiled = $this->compileTemplate($template);
        return $this->evalTemplate($compiled, $data);
    }

    /**
     * @param string $templateName
     * @param array $data
     * @return string
     */
    public function renderNoComments($templateName, array $data = []): string
    {
        return $this->render($templateName, $data, true, false);
    }

    public function get($name): string
    {
        return $this->fileSystem->get("themes/{${Config::DEFAULT_THEME}}/$name.html");
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
        if (!array_key_exists($title, $this->cachedTemplates)) {
            $path = $this->resolvePath($title);
            $this->cachedTemplates[$title] = $path
                ? $this->readTemplate($path, $title, $htmlcomments, $eslashes)
                : false;
        }

        return $this->cachedTemplates[$title];
    }

    private function resolvePath($title): ?string
    {
        foreach ($this->getPossiblePaths($title) as $path) {
            if ($this->fileSystem->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function getPossiblePaths($title): array
    {
        $theme = $this->settings->getTheme();
        $language = $this->lang->getCurrentLanguageShort();

        $paths = [];

        if (strlen($language)) {
            $paths[] = "themes/$theme/$title.$language";
            $paths[] = "themes/$theme/$title.$language.html";
            $paths[] = "themes/{${Config::DEFAULT_THEME}}/$title.$language";
            $paths[] = "themes/{${Config::DEFAULT_THEME}}/$title.$language.html";
        }

        $paths[] = "themes/$theme/$title";
        $paths[] = "themes/$theme/$title.html";
        $paths[] = "themes/{${Config::DEFAULT_THEME}}/$title";
        $paths[] = "themes/{${Config::DEFAULT_THEME}}/$title.html";

        return $paths;
    }

    private function readTemplate($path, $title, $htmlcomments, $eslashes): string
    {
        $template = $this->fileSystem->get($path);

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

    private function evalTemplate($__content, array $data): string
    {
        $data = $this->addDefaultVariables($data);
        extract($data);

        $e = fn($value) => htmlspecialchars($value);
        $v = fn($value) => $value;
        $addSlashes = fn($value) => addslashes($value);

        return eval("return \"$__content\";");
    }

    private function addDefaultVariables(array $data): array
    {
        if (!array_key_exists("lang", $data)) {
            $data["lang"] = $this->lang;
        }

        if (!array_key_exists("settings", $data)) {
            $data["settings"] = $this->settings;
        }

        if (!array_key_exists("url", $data)) {
            $data["url"] = $this->urlGenerator;
        }

        return $data;
    }

    private function compileTemplate($template): string
    {
        return preg_replace(
            ["/{{\s*/", "/\s*}}/", "/{!!\s*/", "/\s*!!}/"],
            ['{$e(', ")}", '{$v(', ")}"],
            $template
        );
    }
}
