<?php
namespace App\View\Pages;

use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\UserServiceAccessService;
use App\System\Auth;
use App\View\Interfaces\IBeLoggedMust;

class PagePurchase extends Page
{
    const PAGE_ID = 'purchase';

    /** @var Auth */
    private $auth;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    public function __construct(Auth $auth, UserServiceAccessService $userServiceAccessService)
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('purchase');
        $this->auth = $auth;
        $this->userServiceAccessService = $userServiceAccessService;
    }

    public function getContent(array $query, array $body)
    {
        return $this->content($query, $body);
    }

    protected function content(array $query, array $body)
    {
        $serviceId = array_get($query, 'service');

        $serviceModule = $this->heart->getServiceModule($serviceId);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t('site_not_exists');
        }

        if (strlen($this->getPageId())) {
            $path = "build/js/shop/pages/{$this->getPageId()}/";
            $pathFile = $path . "main.js";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->addScript($this->url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".js";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->addScript($this->url->versioned($pathFile));
            }
        }

        if (strlen($this->getPageId())) {
            $path = "build/css/shop/pages/{$this->getPageId()}/";
            $pathFile = $path . "main.css";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->addStyle($this->url->versioned($pathFile));
            }

            $pathFile = $path . $serviceModule->getModuleId() . ".css";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->heart->addStyle($this->url->versioned($pathFile));
            }
        }

        $this->heart->pageTitle .= " - " . $serviceModule->service->getName();

        if ($serviceModule instanceof IBeLoggedMust && !$this->auth->check()) {
            return $this->lang->t('must_be_logged_in');
        }

        if (
            !$this->userServiceAccessService->canUserUseService(
                $serviceModule->service,
                $this->auth->user()
            )
        ) {
            return $this->lang->t('service_no_permission');
        }

        if (strlen($serviceModule->descriptionLongGet())) {
            $showMore = $this->template->render("services/show_more");
        } else {
            $showMore = "";
        }

        $output = $this->template->render("services/short_description", [
            "shortDescription" => $serviceModule->descriptionShortGet(),
            "showMore" => $showMore,
        ]);

        return $output . $serviceModule->purchaseFormGet($query);
    }
}
