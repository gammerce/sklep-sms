<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageMain extends Page
{
    const PAGE_ID = "home";
    const SERVICE_LIMIT = 5;

    /** @var Heart */
    private $heart;

    /** @var Auth */
    private $auth;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    /** @var UrlGenerator */
    private $url;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth,
        ServiceModuleManager $serviceModuleManager,
        UserServiceAccessService $userServiceAccessService,
        UrlGenerator $url
    ) {
        parent::__construct($template, $translationManager);
        $this->heart = $heart;
        $this->auth = $auth;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->url = $url;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("main_page");
    }

    public function getContent(Request $request)
    {
        $services = collect($this->heart->getServices())
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
            ->map(function (Service $service) {
                return $this->template->render("shop/components/home/service_tile", [
                    "link" => $this->url->to("/page/purchase", ["service" => $service->getId()]),
                    "name" => $service->getName(),
                ]);
            })
            ->join();

        return $this->template->render("shop/pages/home", [
            "services" => $services,
            "signUpSectionClass" => $this->auth->check() ? "is-hidden" : "",
        ]);
    }
}
