<?php

class PageTakeOverService extends Page implements I_BeLoggedMust
{
    const PAGE_ID = 'service_take_over';

    public function __construct()
    {
        parent::__construct();

        $this->heart->page_title = $this->title = $this->lang->translate('take_over_service');
    }

    protected function content($get, $post)
    {
        $services_options = "";
        $services = $this->heart->get_services();
        foreach ($services as $service) {
            if (($service_module = $this->heart->get_service_module($service['id'])) === null) {
                continue;
            }

            // Moduł danej usługi nie zezwala na jej przejmowanie
            if (!object_implements($service_module, "IService_TakeOver")) {
                continue;
            }

            $services_options .= create_dom_element("option", $service['name'], [
                'value' => $service['id'],
            ]);
        }

        return $this->template->render("service_take_over", compact('services_options'));
    }
}
