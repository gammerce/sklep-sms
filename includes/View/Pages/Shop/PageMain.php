<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Service\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageMain extends Page
{
    const PAGE_ID = "home";
    const SERVICE_LIMIT = 5;

    /** @var Auth */
    private $auth;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    /** @var UrlGenerator */
    private $url;

    /** @var ServiceManager */
    private $serviceManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Auth $auth,
        ServiceModuleManager $serviceModuleManager,
        ServiceManager $serviceManager,
        UserServiceAccessService $userServiceAccessService,
        UrlGenerator $url
    ) {
        parent::__construct($template, $translationManager);
        $this->auth = $auth;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->url = $url;
        $this->serviceManager = $serviceManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("main_page");
    }

    public function getContent(Request $request)
    {
        $services = collect($this->serviceManager->all())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule &&
                    $serviceModule->showOnWeb() &&
                    $this->userServiceAccessService->canUserUseService(
                        $service,
                        $this->auth->user()
                    );
            })
            ->limit($this::SERVICE_LIMIT)
            ->map(
                fn(Service $service) => $this->template->render(
                    "shop/components/home/service_tile",
                    [
                        "link" => $this->url->to("/page/purchase", [
                            "service" => $service->getId(),
                        ]),
                        "name" => $service->getNameI18n(),
                    ]
                )
            )
            ->join();

        return $this->template->render("shop/pages/home", [
            "services" => $services,
            "signUpSectionClass" => $this->auth->check() ? "is-hidden" : "",
        ]);
    }
}
