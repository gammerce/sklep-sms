<?php

use App\Auth;
use App\Settings;
use App\Template;

class PagePurchase extends Page
{
    const PAGE_ID = 'purchase';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('purchase');
    }

    public function get_content($get, $post)
    {
        return $this->content($get, $post);
    }

    protected function content($get, $post)
    {
        $heart = $this->heart;
        $lang = $this->lang;

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        if (($service_module = $heart->get_service_module($get['service'])) === null) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy wszystkie skrypty
        if (strlen($this::PAGE_ID)) {
            $path = "jscripts/pages/" . $this::PAGE_ID . "/";
            $path_file = $path . "main.js";
            if (file_exists($this->app->path($path_file))) {
                $heart->script_add($settings['shop_url_slash'] . $path_file . "?version=" . $this->app->version());
            }

            $path_file = $path . $service_module->get_module_id() . ".js";
            if (file_exists($this->app->path($path_file))) {
                $heart->script_add($settings['shop_url_slash'] . $path_file . "?version=" . $this->app->version());
            }
        }

        // Dodajemy wszystkie css
        if (strlen($this::PAGE_ID)) {
            $path = "styles/pages/" . $this::PAGE_ID . "/";
            $path_file = $path . "main.css";
            if (file_exists($this->app->path($path_file))) {
                $heart->style_add($settings['shop_url_slash'] . $path_file . "?version=" . $this->app->version());
            }

            $path_file = $path . $service_module->get_module_id() . ".css";
            if (file_exists($this->app->path($path_file))) {
                $heart->style_add($settings['shop_url_slash'] . $path_file . "?version=" . $this->app->version());
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        foreach ($heart->get_services_modules() as $module_info) {
            if ($module_info['id'] == $service_module->get_module_id()) {
                $path = "styles/services/" . $module_info['id'] . ".css";
                if (file_exists($this->app->path($path))) {
                    $heart->style_add($settings['shop_url_slash'] . $path . "?version=" . $this->app->version());
                }

                $path = "jscripts/services/" . $module_info['id'] . ".js";
                if (file_exists($this->app->path($path))) {
                    $heart->script_add($settings['shop_url_slash'] . $path . "?version=" . $this->app->version());
                }

                break;
            }
        }

        $heart->page_title .= " - " . $service_module->service['name'];

        // Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
        // Jeżeli wymaga, to to sprawdzamy
        if (object_implements($service_module, "I_BeLoggedMust") && !is_logged()) {
            return $lang->translate('must_be_logged_in');
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$heart->user_can_use_service($user->getUid(), $service_module->service)) {
            return $lang->translate('service_no_permission');
        }

        // Nie ma formularza zakupu, to tak jakby strona nie istniała
        if (!object_implements($service_module, "IService_PurchaseWeb")) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy długi opis
        $show_more = '';
        if (strlen($service_module->description_full_get())) {
            $show_more = $template->render("services/show_more");
        }

        $output = $template->render(
            "services/short_description",
            compact('service_module', 'show_more')
        );

        return $output . $service_module->purchase_form_get();
    }
}