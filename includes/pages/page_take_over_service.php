<?php

class PageTakeOverService extends Page implements I_BeLoggedMust
{
    const PAGE_ID = "service_take_over";

    public function __construct()
    {
        global $lang;
        $this->title = $lang->translate('take_over_service');

        parent::__construct();
    }

    protected function content($get, $post)
    {
        global $heart, $lang, $settings, $templates;

        $services_options = "";
        $services = $heart->get_services();
        foreach ($services as $service) {
            if (($service_module = $heart->get_service_module($service['id'])) === null) {
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

        $output = eval($templates->render("service_take_over"));

        return $output;
    }
}