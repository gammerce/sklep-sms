<?php
namespace App\View\Pages\Shop;

use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Models\Service;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\View\Html\Option;
use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;
use Symfony\Component\HttpFoundation\Request;

class PageTakeOverService extends Page implements IBeLoggedMust
{
    const PAGE_ID = "service_take_over";

    private ServiceModuleManager $serviceModuleManager;
    private ServiceManager $serviceManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        ServiceModuleManager $serviceModuleManager,
        ServiceManager $serviceManager
    ) {
        parent::__construct($template, $translationManager);
        $this->serviceModuleManager = $serviceModuleManager;
        $this->serviceManager = $serviceManager;
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("service_takeover");
    }

    public function getContent(Request $request)
    {
        $servicesOptions = collect($this->serviceManager->all())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->get($service->getId());
                // Service module doesn't allow taking the service over
                return $serviceModule instanceof IServiceTakeOver;
            })
            ->map(fn(Service $service) => new Option($service->getNameI18n(), $service->getId()))
            ->join();

        return $this->template->render("shop/pages/service_take_over", compact("servicesOptions"));
    }
}
