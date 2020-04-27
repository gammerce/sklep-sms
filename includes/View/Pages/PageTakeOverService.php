<?php
namespace App\View\Pages;

use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\View\Interfaces\IBeLoggedMust;
use Symfony\Component\HttpFoundation\Request;

class PageTakeOverService extends Page implements IBeLoggedMust
{
    const PAGE_ID = "service_take_over";

    public function getTitle(Request $request)
    {
        return $this->lang->t("take_over_service");
    }

    public function getContent(array $query, array $body)
    {
        $servicesOptions = "";
        foreach ($this->heart->getServices() as $service) {
            if (($serviceModule = $this->heart->getServiceModule($service->getId())) === null) {
                continue;
            }

            // Service module doesn't allow taking the service over
            if (!($serviceModule instanceof IServiceTakeOver)) {
                continue;
            }

            $servicesOptions .= create_dom_element("option", $service->getName(), [
                "value" => $service->getId(),
            ]);
        }

        return $this->template->render("service_take_over", compact("servicesOptions"));
    }
}
