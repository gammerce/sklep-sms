<?php

$heart->register_page("purchase", "PagePurchase");

class PagePurchase extends Page
{

	function __construct()
	{
		global $lang;
		$this->title = $lang->purchase;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $user, $lang, $settings, $scripts, $stylesheets;

		if (($service_module = $heart->get_service_module($get['service'])) === NULL)
			return $lang->site_not_exists;

		$heart->page_title .= " - " . $service_module->service['name'];

		// Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
		// Jeżeli wymaga, to to sprawdzamy
		if (object_implements($service_module, "IServiceMustBeLogged") && !is_logged())
			return $lang->must_be_logged_in;

		// Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
		if (!$heart->user_can_use_service($user['uid'], $service_module->service))
			return $lang->service_no_permission;

		// Nie ma formularza zakupu, to tak jakby strona nie istniała
		if (!object_implements($service_module, "IServicePurchaseWeb"))
			return $lang->site_not_exists;

		// Dodajemy długi opis
		if (strlen($service_module->get_full_description()))
			eval("\$show_more = \"" . get_template("services/show_more") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/purchase.js?version=" . VERSION;
		$stylesheets[] = $settings['shop_url_slash'] . "styles/style_purchase.css?version=" . VERSION;

		eval("\$output = \"" . get_template("services/short_description") . "\";"); // Dodajemy krótki opis
		return $output . $service_module->form_purchase_service();
	}

}