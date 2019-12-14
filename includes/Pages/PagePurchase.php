<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\System\Auth;

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

        if (($serviceModule = $heart->getServiceModule($query['service'])) === null) {
            return $lang->translate('site_not_exists');
        }

        // Dodajemy wszystkie skrypty
        if (strlen($this::PAGE_ID)) {
            $path = "build/js/static/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.js";
            if (file_exists($this->path->to($pathFile))) {
                $heart->scriptAdd($this->url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".js";
            if (file_exists($this->path->to($pathFile))) {
                $heart->scriptAdd($this->url->to($pathFile));
            }
        }

        // Dodajemy wszystkie css
        if (strlen($this::PAGE_ID)) {
            $path = "build/css/static/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.css";
            if (file_exists($this->path->to($pathFile))) {
                $heart->styleAdd($this->url->to($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".css";
            if (file_exists($this->path->to($pathFile))) {
                $heart->styleAdd($this->url->to($pathFile));
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        foreach ($heart->getServicesModules() as $moduleInfo) {
            if ($moduleInfo['id'] == $serviceModule->getModuleId()) {
                $path = "build/css/static/services/" . $moduleInfo['id'] . ".css";
                if (file_exists($this->path->to($path))) {
                    $heart->styleAdd($this->url->to($path));
                }

                $path = "build/js/static/services/" . $moduleInfo['id'] . ".js";
                if (file_exists($this->path->to($path))) {
                    $heart->scriptAdd($this->url->to($path));
                }

                break;
            }
        }

        $heart->pageTitle .= " - " . $serviceModule->service['name'];

        // Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
        // Jeżeli wymaga, to to sprawdzamy
        if ($serviceModule instanceof IBeLoggedMust && !$auth->check()) {
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
        if (strlen($serviceModule->descriptionLongGet())) {
            $showMore = $this->template->render("services/show_more");
        }

        $output = $this->template->render(
            "services/short_description",
            compact('serviceModule', 'showMore')
        );

        return $output . $serviceModule->purchaseFormGet();
    }
}
