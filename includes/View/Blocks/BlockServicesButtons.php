<?php
namespace App\View\Blocks;

use App\Models\Service;
use App\Routing\UrlGenerator;
use App\Services\ServiceListService;
use App\Support\Template;
use App\System\Auth;
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

    /** @var ServiceListService */
    private $serviceListService;

    public function __construct(
        Auth $auth,
        Template $template,
        UrlGenerator $url,
        ServiceListService $serviceListService
    ) {
        $this->auth = $auth;
        $this->template = $template;
        $this->url = $url;
        $this->serviceListService = $serviceListService;
    }

    public function getContentClass()
    {
        return "services-buttons";
    }

    public function getContent(Request $request, array $params)
    {
        $services = collect($this->serviceListService->getWebSupportedForUser($this->auth->user()))
            ->map(function (Service $service) {
                return $this->template->render("shop/components/navbar/navigation_item", [
                    "text" => $service->getNameI18n(),
                    "link" => $this->url->to("/page/purchase", ["service" => $service->getId()]),
                ]);
            })
            ->join();

        return $this->template->render("shop/layout/services_buttons", compact("services"));
    }
}
