<?php
namespace App\Pages;

use App\Auth;
use App\Interfaces\IBeLoggedMust;
use App\Routes\UrlGenerator;
use App\Settings;
use App\Template;
use App\Services\Interfaces\IServicePurchaseWeb;

class PagePurchase extends Page
{
    const PAGE_ID = 'purchase';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('purchase');
    }

    public function getContent(array $query, array $body)
    {
        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        $heart = $this->heart;
        $lang = $this->lang;

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $template */
        $template = $this->app->make(Template::class);

        /** @var UrlGenerator $url */
        $url = $this->app->make(UrlGenerator::class);

        if (($serviceModule = $heart->getServiceModule($query['service'])) === null) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy wszystkie skrypty
        if (strlen($this::PAGE_ID)) {
            $path = "build/js_old/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.js";
            if (file_exists($this->app->path($pathFile))) {
                $heart->scriptAdd($url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".js";
            if (file_exists($this->app->path($pathFile))) {
                $heart->scriptAdd($url->to($pathFile));
            }
        }

        // Dodajemy wszystkie css
        if (strlen($this::PAGE_ID)) {
            $path = "build/stylesheets_old/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.css";
            if (file_exists($this->app->path($pathFile))) {
                $heart->styleAdd($url->to($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".css";
            if (file_exists($this->app->path($pathFile))) {
                $heart->styleAdd($url->to($pathFile));
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        foreach ($heart->getServicesModules() as $moduleInfo) {
            if ($moduleInfo['id'] == $serviceModule->getModuleId()) {
                $path = "build/stylesheets_old/services/" . $moduleInfo['id'] . ".css";
                if (file_exists($this->app->path($path))) {
                    $heart->styleAdd($url->to($path));
                }

                $path = "build/js_old/services/" . $moduleInfo['id'] . ".js";
                if (file_exists($this->app->path($path))) {
                    $heart->scriptAdd($url->to($path));
                }

                break;
            }
        }

        $heart->pageTitle .= " - " . $serviceModule->service['name'];

        // Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
        // Jeżeli wymaga, to to sprawdzamy
        if ($serviceModule instanceof IBeLoggedMust && !is_logged()) {
            return $lang->translate('must_be_logged_in');
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$heart->userCanUseService($user->getUid(), $serviceModule->service)) {
            return $lang->translate('service_no_permission');
        }

        // Nie ma formularza zakupu, to tak jakby strona nie istniała
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy długi opis
        $showMore = '';
        if (strlen($serviceModule->descriptionFullGet())) {
            $showMore = $template->render("services/show_more");
        }

        $output = $template->render(
            "services/short_description",
            compact('serviceModule', 'showMore')
        );

        return $output . $serviceModule->purchaseFormGet();
    }
}
