<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageProducts extends Page
{
    const PAGE_ID = "products";

    /** @var Heart */
    private $heart;

    /** @var Auth */
    private $auth;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth,
        ServiceModuleManager $serviceModuleManager,
        UserServiceAccessService $userServiceAccessService
    ) {
        parent::__construct($template, $translationManager);
        $this->heart = $heart;
        $this->auth = $auth;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->userServiceAccessService = $userServiceAccessService;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("products");
    }

    public function getContent(Request $request)
    {
        $cards = collect($this->heart->getServices())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule &&
                    $serviceModule->showOnWeb() &&
                    $this->userServiceAccessService->canUserUseService(
                        $service,
                        $this->auth->user()
                    );
            })
            ->map(function (Service $service) {
                return $this->template->render("shop/components/products/product_card", [
                    "name" => $service->getName(),
                    "description" => $service->getShortDescription(),
                ]);
            })
            ->join();

        return $this->template->render("shop/pages/products", compact("cards"));
    }
}
