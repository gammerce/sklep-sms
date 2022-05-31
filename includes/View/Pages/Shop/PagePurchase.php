<?php
namespace App\View\Pages\Shop;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Managers\ServiceModuleManager;
use App\Managers\WebsiteHeader;
use App\Routing\UrlGenerator;
use App\Service\UserServiceAccessService;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Support\FileSystem;
use App\Support\BasePath;
use App\Theme\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PagePurchase extends Page
{
    const PAGE_ID = "purchase";

    private Auth $auth;
    private UserServiceAccessService $userServiceAccessService;
    private WebsiteHeader $websiteHeader;
    private BasePath $path;
    private FileSystem $fileSystem;
    private UrlGenerator $url;
    private ServiceModuleManager $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        UserServiceAccessService $userServiceAccessService,
        WebsiteHeader $websiteHeader,
        ServiceModuleManager $serviceModuleManager,
        BasePath $path,
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

    public function getTitle(Request $request = null): string
    {
        $title = "";

        if ($request) {
            $serviceModule = $this->getServiceModule($request);
            if ($serviceModule) {
                $title .= $serviceModule->service->getNameI18n() . " - ";
            }
        }

        return $title . $this->lang->t("purchase");
    }

    public function getContent(Request $request)
    {
        $serviceModule = $this->getServiceModule($request);

        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            throw new EntityNotFoundException();
        }

        if ($serviceModule instanceof IBeLoggedMust && !$this->auth->check()) {
            throw new UnauthorizedException();
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
            $showMore = $this->template->render("shop/components/purchase/show_more");
        } else {
            $showMore = "";
        }

        $description = $this->template->render("shop/components/purchase/short_description", [
            "shortDescription" =>
                $serviceModule->descriptionShortGet() ?: $serviceModule->service->getNameI18n(),
            "showMore" => $showMore,
        ]);
        $purchaseForm = $serviceModule->purchaseFormGet($request->query->all());

        return $this->template->render(
            "shop/pages/purchase",
            compact("description", "purchaseForm")
        );
    }

    public function addScripts(Request $request): void
    {
        $path = "build/js/shop/pages/{$this->getId()}/";
        $pathFile = $path . "main.js";
        if ($this->fileSystem->exists($this->path->to($pathFile))) {
            $this->websiteHeader->addScript($this->url->versioned($pathFile));
        }

        $serviceModule = $this->getServiceModule($request);
        if ($serviceModule) {
            $pathFile = $path . $serviceModule->getModuleId() . ".js";
            if ($this->fileSystem->exists($this->path->to($pathFile))) {
                $this->websiteHeader->addScript($this->url->versioned($pathFile));
            }
        }
    }

    private function getServiceModule(Request $request): ?ServiceModule
    {
        $serviceId = $request->query->get("service");
        return $this->serviceModuleManager->get($serviceId);
    }
}
