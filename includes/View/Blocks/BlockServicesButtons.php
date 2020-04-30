<?php
namespace App\View\Blocks;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Services\ServiceListService;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;
use Symfony\Component\HttpFoundation\Request;

class BlockServicesButtons extends Block
{
    const BLOCK_ID = "services_buttons";

    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var UrlGenerator */
    private $url;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var Heart */
    private $heart;

    /** @var ServiceListService */
    private $serviceListService;

    public function __construct(
        Auth $auth,
        Template $template,
        Heart $heart,
        ServiceModuleManager $serviceModuleManager,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService,
        ServiceListService $serviceListService
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->heart = $heart;
        $this->serviceListService = $serviceListService;
    }

    public function getContentClass()
    {
        return "services_buttons";
    }

    public function getContentId()
    {
        return "services_buttons";
    }

    protected function content(Request $request, array $params)
    {
        $services = collect($this->serviceListService->getWebSupportedForUser($this->auth->user()))
            ->map(function (Service $service) {
                return $this->template->render("shop/components/navbar/navigation_item", [
                    "text" => $service->getName(),
                    "link" => $this->url->to("/page/purchase", ["service" => $service->getId()]),
                ]);
            })
            ->join();

        return $this->template->render("shop/layout/services_buttons", compact('services'));
    }
}
