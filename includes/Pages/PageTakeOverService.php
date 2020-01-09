<?php
namespace App\Pages;

use App\Interfaces\IBeLoggedMust;
use App\ServiceModules\Interfaces\IServiceTakeOver;

class PageTakeOverService extends Page implements IBeLoggedMust
{
    const PAGE_ID = 'service_take_over';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('take_over_service');
    }

    protected function content(array $query, array $body)
    {
        $servicesOptions = "";
        foreach ($this->heart->getServices() as $service) {
            if (($serviceModule = $this->heart->getServiceModule($service->getId())) === null) {
                continue;
            }

            // Moduł danej usługi nie zezwala na jej przejmowanie
            if (!($serviceModule instanceof IServiceTakeOver)) {
                continue;
            }

            $servicesOptions .= create_dom_element("option", $service->getName(), [
                'value' => $service->getId(),
            ]);
        }

        return $this->template->render("service_take_over", compact('servicesOptions'));
    }
}
