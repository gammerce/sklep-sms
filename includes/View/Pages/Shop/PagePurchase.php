<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\Services\UserServiceAccessService;
use App\Support\FileSystem;
use App\Support\Path;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePurchase extends Page
{
    const PAGE_ID = "purchase";

    /** @var Auth */
    private $auth;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    /** @var WebsiteHeader */
    private $websiteHeader;

    /** @var Path */
    private $path;

    /** @var FileSystem */
    private $fileSystem;

    /** @var UrlGenerator */
    private $url;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        UserServiceAccessService $userServiceAccessService,
        WebsiteHeader $websiteHeader,
        ServiceModuleManager $serviceModuleManager,
        Path $path,
        FileSystem $fileSystem,
        UrlGenerator $url
    ) {
        parent::__construct($template, $translationManager);

        $this->auth = $auth;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->websiteHeader = $websiteHeader;
        $this->path = $path;
        $this->fileSystem = $fileSystem;
        $this->url = $url;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function getTitle(Request $request)
    {
        $serviceModule = $this->getServiceModule($request);
        $title = $this->lang->t("purchase");

        if ($serviceModule) {
            $title .= " - " . $serviceModule->service->getName();
        }

        return $title;
    }

    public function getContent(Request $request)
    {
        $serviceModule = $this->getServiceModule($request);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t("site_not_exists");
        }

        $path = "build/js/shop/pages/{$this->getId()}/";
        $pathFile = $path . "main.js";
        if ($this->fileSystem->exists($this->path->to($pathFile))) {
            $this->websiteHeader->addScript($this->url->versioned($pathFile));
        }

        $pathFile = $path . $serviceModule->getModuleId() . ".js";
        if ($this->fileSystem->exists($this->path->to($pathFile))) {
            $this->websiteHeader->addScript($this->url->versioned($pathFile));
        }

        if ($serviceModule instanceof IBeLoggedMust && !$this->auth->check()) {
            return $this->lang->t("must_be_logged_in");
        }

        if (
            !$this->userServiceAccessService->canUserUseService(
                $serviceModule->service,
                $this->auth->user()
            )
        ) {
            return $this->lang->t("service_no_permission");
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

        return $output . $serviceModule->purchaseFormGet($request->query->all());
    }

    private function getServiceModule(Request $request)
    {
        $serviceId = $request->query->get("service");
        return $this->serviceModuleManager->get($serviceId);
    }
}
