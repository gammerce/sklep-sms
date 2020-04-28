<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageTakeOverService extends Page implements IBeLoggedMust
{
    const PAGE_ID = "service_take_over";

    /** @var Heart */
    private $heart;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        Heart $heart
    ) {
        parent::__construct($template, $translationManager);
        $this->heart = $heart;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("service_takeover");
    }

    public function getContent(Request $request)
    {
        $servicesOptions = collect($this->heart->getServices())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                // Service module doesn't allow taking the service over
                return $serviceModule instanceof IServiceTakeOver;
            })
            ->map(function (Service $service) {
                return create_dom_element("option", $service->getName(), [
                    "value" => $service->getId(),
                ]);
            })
            ->join();

        return $this->template->render("service_take_over", compact("servicesOptions"));
    }
}
