<?php

use App\CurrentPage;
use App\Heart;
use App\Settings;
use App\TranslationManager;
use App\Translator;

abstract class Page
{
    const PAGE_ID = "";
    protected $title = "";

    /** @var Heart */
    protected $heart;

    /** @var Settings */
    protected $settings;

    /** @var CurrentPage */
    protected $currentPage;

    /** @var Translator */
    protected $lang;

    public function __construct()
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->heart = app()->make(Heart::class);
        $this->settings = app()->make(Settings::class);
        $this->currentPage = app()->make(CurrentPage::class);
    }

    /**
     * Zwraca treść danej strony po przejściu wszystkich filtrów
     *
     * @param array $get - dane get
     * @param array $post - dane post
     *
     * @return string - zawartość do wyświetlenia
     */
    public function get_content($get, $post)
    {
        // Dodajemy wszystkie skrypty
        $path = "jscripts/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".js")) {
                    $this->heart->script_add($this->settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Dodajemy wszystkie css
        $path = "styles/pages/" . $this::PAGE_ID . "/";
        if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path)) {
            foreach (scandir(SCRIPT_ROOT . $path) as $file) {
                if (ends_at($file, ".css")) {
                    $this->heart->style_add($this->settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);
                }
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        if (in_array($this::PAGE_ID, ["purchase", "user_own_services", "service_take_over", "payment_log"])) {
            foreach ($this->heart->get_services_modules() as $module_info) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $this->heart->style_add($this->settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists(SCRIPT_ROOT . $path)) {
                    $this->heart->script_add($this->settings['shop_url_slash'] . $path . "?version=" . VERSION);
                }
            }
        }


        return $this->content($get, $post);
    }

    /**
     * Zwraca treść danej strony
     *
     * @param string $get
     * @param string $post
     *
     * @return string
     */
    abstract protected function content($get, $post);
}

abstract class PageSimple extends Page
{
    protected $template = null;

    public function __construct()
    {
        if (!isset($this->template)) {
            throw new Exception('Class ' . get_class($this) . ' has to have field $template because it extends class PageSimple');
        }

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $lang, $templates;

        return eval($templates->render($this->template));
    }
}