<?php

abstract class Page
{

	const PAGE_ID = "";
	protected $title = "";

	function __construct()
	{
		global $heart;

		$heart->page_title = $this->title;
	}

	/**
	 * Zwraca treść danej strony po przejściu wszystkich filtrów
	 *
	 * @param array $get - dane get
	 * @param array $post - dane post
	 * @return string - zawartość do wyświetlenia
	 */
	public function get_content($get, $post)
	{
		global $heart, $settings;

		// Dodajemy wszystkie skrypty
		$path = "jscripts/pages/" . $this::PAGE_ID . "/";
		if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path))
			foreach (scandir(SCRIPT_ROOT . $path) as $file)
				if (ends_at($file, ".js"))
					$heart->script_add($settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);

		// Dodajemy wszystkie css
		$path = "styles/pages/" . $this::PAGE_ID . "/";
		if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $path))
			foreach (scandir(SCRIPT_ROOT . $path) as $file)
				if (ends_at($file, ".css"))
					$heart->style_add($settings['shop_url_slash'] . $path . $file . "?version=" . VERSION);

		// Globalne jsy cssy konkretnych modułów usług
		if (in_array($this::PAGE_ID, array("purchase", "user_own_services", "take_over_service", "payment_log"))) {
			foreach ($heart->get_services_modules() as $module_info) {
				$path = "styles/services/" . $module_info['id'] . ".css";
				if (file_exists(SCRIPT_ROOT . $path))
					$heart->style_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);

				$path = "jscripts/services/" . $module_info['id'] . ".js";
				if (file_exists(SCRIPT_ROOT . $path))
					$heart->script_add($settings['shop_url_slash'] . $path . "?version=" . VERSION);
			}
		}


		return $this->content($get, $post);
	}

	/**
	 * Zwraca treść danej strony
	 *
	 * @param string $get
	 * @param string $post
	 * @return string
	 */
	abstract protected function content($get, $post);

}

abstract class PageSimple extends Page
{

	function __construct()
	{
		if (!isset($this->template))
			throw new Exception('Class ' . get_class($this) . ' has to have field $template because it extends class PageSimple');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $lang;

		eval("\$output = \"" . get_template($this->template) . "\";");
		return $output;
	}

}