<?php
namespace App\View\Blocks;

use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Services\UserServiceAccessService;
use App\Support\Template;
use App\System\Auth;
use App\System\Heart;

class BlockServicesButtons extends Block
{
    /** @var Auth */
    private $auth;

    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var UrlGenerator */
    private $url;

    /** @var UserServiceAccessService */
    private $userServiceAccessService;

    public function __construct(
        Auth $auth,
        Template $template,
        Heart $heart,
        UrlGenerator $url,
        UserServiceAccessService $userServiceAccessService
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->heart = $heart;
        $this->url = $url;
        $this->userServiceAccessService = $userServiceAccessService;
    }

    public function getContentClass()
    {
        return "services_buttons";
    }

    public function getContentId()
    {
        return "services_buttons";
    }

    protected function content(array $query, array $body, array $params)
    {
        $user = $this->auth->user();

        $services = collect($this->heart->getServices())
            ->filter(function (Service $service) use ($user) {
                $serviceModule = $this->heart->getServiceModule($service->getId());
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

        return $this->template->render("services_buttons", compact('services'));
    }
}
