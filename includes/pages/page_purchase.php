<?php

$heart->register_page("purchase", "PagePurchase");

class PagePurchase extends Page
{

	const PAGE_ID = "purchase";

	function __construct()
	{
		global $lang;
		$this->title = $lang->purchase;

		parent::__construct();
	}

	public function get_content($get, $post)
	{
		return $this->content($get, $post);
	}

	protected function content($get, $post)
	{
		global $heart, $user, $lang, $settings, $stylesheets, $scripts;

		if (($service_module = $heart->get_service_module($get['service'])) === NULL)
			return $lang->site_not_exists;

		// Dodajemy wszystkie skrypty
		$script_path = "jscripts/pages/" . $this::PAGE_ID . "/";
		if (strlen($this::PAGE_ID)) {
			$path = $script_path . "main.js";
			if (file_exists(SCRIPT_ROOT . $path))
				$scripts[] = $settings['shop_url_slash'] . $path . "?version=" . VERSION;

			$path = $script_path . $service_module->get_module_id() . ".js";
			if (file_exists(SCRIPT_ROOT . $path))
				$scripts[] = $settings['shop_url_slash'] . $path . "?version=" . VERSION;
		}

		$heart->page_title .= " - " . $service_module->service['name'];

		// Sprawdzamy, czy usluga wymaga, by użytkownik był zalogowany
		// Jeżeli wymaga, to to sprawdzamy
		if (object_implements($service_module, "I_BeLoggedMust") && !is_logged())
			return $lang->must_be_logged_in;

		// Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
		if (!$heart->user_can_use_service($user['uid'], $service_module->service))
			return $lang->service_no_permission;

		// Nie ma formularza zakupu, to tak jakby strona nie istniała
		if (!object_implements($service_module, "IService_PurchaseWeb"))
			return $lang->site_not_exists;

		// Dodajemy długi opis
		if (strlen($service_module->description_full_get()))
			eval("\$show_more = \"" . get_template("services/show_more") . "\";");

		$stylesheets[] = $settings['shop_url_slash'] . "styles/style_purchase.css?version=" . VERSION;

		eval("\$output = \"" . get_template("services/short_description") . "\";"); // Dodajemy krótki opis
		return $output . $service_module->purchase_form_get();
	}

}