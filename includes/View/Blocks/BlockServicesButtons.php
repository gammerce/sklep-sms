<?php
namespace App\View\Blocks;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\Routing\UrlGenerator;
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

    public function __construct(
        Auth $auth,
        Template $template,
        Heart $heart,
        ServiceModuleManager $serviceModuleManager,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->heart = $heart;
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
        $user = $this->auth->user();

        $services = collect($this->heart->getServices())
            ->filter(function (Service $service) use ($user) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                return $serviceModule &&
                    $serviceModule->showOnWeb() &&
                    $this->userServiceAccessService->canUserUseService($service, $user);
            })
            ->map(function (Service $service) {
                return create_dom_element(
                    "li",
                    create_dom_element("a", $service->getName(), [
                        'href' => $this->url->to(
                            "/page/purchase?service=" . urlencode($service->getId())
                        ),
                    ])
                );
            })
            ->join();

        return $this->template->render("shop/layout/services_buttons", compact('services'));
    }
}
