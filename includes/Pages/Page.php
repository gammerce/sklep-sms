<?php
namespace App\Pages;

use App\Html\I_ToHtml;
use App\Routes\UrlGenerator;
use App\System\Application;
use App\System\CurrentPage;
use App\System\Database;
use App\System\FileSystemContract;
use App\System\Heart;
use App\System\Path;
use App\System\Settings;
use App\System\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class Page
{
    const PAGE_ID = "";
    protected $title = "";

    /** @var Application */
    protected $app;

    /** @var Heart */
    protected $heart;

    /** @var Settings */
    protected $settings;

    /** @var CurrentPage */
    protected $currentPage;

    /** @var Translator */
    protected $lang;

    /** @var Template */
    protected $template;

    /** @var Database */
    protected $db;

    /** @var UrlGenerator */
    protected $url;

    /** @var Path */
    protected $path;

    /** @var FileSystemContract */
    protected $fileSystem;

    public function __construct()
    {
        $this->app = app();
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->heart = $this->app->make(Heart::class);
        $this->settings = $this->app->make(Settings::class);
        $this->currentPage = $this->app->make(CurrentPage::class);
        $this->template = $this->app->make(Template::class);
        $this->db = $this->app->make(Database::class);
        $this->url = $this->app->make(UrlGenerator::class);
        $this->path = $this->app->make(Path::class);
        $this->fileSystem = $this->app->make(FileSystemContract::class);
    }

    /**
     * Zwraca treść danej strony po przejściu wszystkich filtrów
     *
     * @param array $query
     * @param array $body
     *
     * @return I_ToHtml|string
     */
    public function getContent(array $query, array $body)
    {
        // Dodajemy wszystkie skrypty
        $path = "build/js/static/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && $this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->scriptAdd($this->url->versioned($path . $file));
                }
            }
        }

        // Let's add all css
        $path = "build/css/static/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && $this->fileSystem->exists($this->path->to($path))) {
            foreach ($this->fileSystem->scanDirectory($this->path->to($path)) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->styleAdd($this->url->versioned($path . $file));
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (
            in_array($this::PAGE_ID, [
                "purchase",
                "user_own_services",
                "service_take_over",
                "payment_log",
            ])
        ) {
            foreach ($this->heart->getServicesModules() as $moduleInfo) {
                $path = "build/css/static/services/" . $moduleInfo['id'] . ".css";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->styleAdd($this->url->versioned($path));
                }

                $path = "build/js/static/services/" . $moduleInfo['id'] . ".js";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->scriptAdd($this->url->versioned($path));
                }
            }
        }

        return $this->content($query, $body);
    }

    /**
     * Zwraca treść danej strony
     *
     * @param array $query
     * @param array $body
     *
     * @return string|I_ToHtml
     */
    abstract protected function content(array $query, array $body);
}
