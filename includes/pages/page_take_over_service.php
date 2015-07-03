<?php

$heart->register_page("take_over_service", "PageTakeOverService");

class PageTakeOverService extends Page
{

	protected $require_login = 1;

	function __construct()
	{
		global $lang;
		$this->title = $lang->take_over_service;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $lang, $settings, $scripts, $stylesheets;

		$services_options = "";
		$services = $heart->get_services();
		foreach ($services as $service) {
			if (($service_module = $heart->get_service_module($service['id'])) === NULL)
				continue;

			// Moduł danej usługi nie zezwala na jej przejmowanie
			if (!class_has_interface($service_module, "IServiceTakeOver"))
				continue;

			$services_options .= create_dom_element("option", $service['name'], array(
				'value' => $service['id']
			));
		}

		$scripts[] = $settings['shop_url_slash'] . "jscripts/take_over_service.js?version=" . VERSION;
		$stylesheets[] = $settings['shop_url_slash'] . "styles/take_over_service.css?version=" . VERSION;

		eval("\$output = \"" . get_template("take_over_service") . "\";");
		return $output;
	}

}