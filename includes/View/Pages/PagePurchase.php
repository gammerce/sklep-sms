<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\System\Auth;

class PagePurchase extends Page
{
    const PAGE_ID = 'purchase';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('purchase');
    }

    public function getContent(array $query, array $body)
    {
        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        $serviceId = $query['service'];

        $serviceModule = $this->heart->getServiceModule($serviceId);

        if (!$serviceModule) {
            return $this->lang->t('site_not_exists');
        }

        // Dodajemy wszystkie skrypty
        if (strlen($this::PAGE_ID)) {
            $path = "build/js/static/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.js";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->scriptAdd($this->url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".js";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->scriptAdd($this->url->versioned($pathFile));
            }
        }

        // Dodajemy wszystkie css
        if (strlen($this::PAGE_ID)) {
            $path = "build/css/static/pages/" . $this::PAGE_ID . "/";
            $pathFile = $path . "main.css";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->styleAdd($this->url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".css";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->styleAdd($this->url->versioned($pathFile));
            }
        }

        // Globalne jsy cssy konkretnych modułów usług
        foreach ($this->heart->getServicesModules() as $moduleInfo) {
            if ($moduleInfo['id'] == $serviceModule->getModuleId()) {
                $path = "build/css/static/services/" . $moduleInfo['id'] . ".css";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->styleAdd($this->url->versioned($path));
                }

                $path = "build/js/static/services/" . $moduleInfo['id'] . ".js";
                if ($this->fileSystem->exists($this->path->to($path))) {
                    $this->heart->scriptAdd($this->url->versioned($path));
                }

                break;
            }
        }

        $this->heart->pageTitle .= " - " . $serviceModule->service->getName();

        // Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
        // Jeżeli wymaga, to to sprawdzamy
        if ($serviceModule instanceof IBeLoggedMust && !$auth->check()) {
            return $this->lang->t('must_be_logged_in');
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$this->heart->userCanUseService($user->getUid(), $serviceModule->service)) {
            return $this->lang->t('service_no_permission');
        }

        // Nie ma formularza zakupu, to tak jakby strona nie istniała
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t('site_not_exists');
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

        return $output . $serviceModule->purchaseFormGet($query);
    }
}
