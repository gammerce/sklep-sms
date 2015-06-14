<?php

$heart->register_page("my_current_services", "PageMyCurrentServices");

class PageMyCurrentServices extends Page
{

	protected $require_login = 1;

	function __construct()
	{
		global $lang;
		$this->title = $lang['my_current_services'];

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $user, $lang, $G_PAGE, $scripts, $stylesheets;

		$my_current_services = "";
		$result = $db->query($db->prepare(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `uid` = '%d' " .
			"ORDER BY `id` DESC " .
			"LIMIT " . get_row_limit($G_PAGE, 4),
			array($user['uid'])
		));
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		while ($row = $db->fetch_array_assoc($result)) {
			if (($service_module = $heart->get_service_module($row['service'])) === NULL)
				continue;

			if ($settings['user_edit_service'] && class_has_interface($service_module, "IServiceUserEdit"))
				$button_edit = create_dom_element("img", "", array(
					'class' => "edit_row",
					'src' => "images/pencil.png",
					'title' => $lang['edit'],
					'style' => array(
						'height' => '24px'
					)
				));

			if (!strlen($temp_text = $service_module->my_service_info($row, $button_edit)))
				continue;

			$my_current_services .= create_brick($temp_text);
		}

		// Nie znalazło żadnych usług danego gracza
		if (!strlen($my_current_services))
			$my_current_services = $lang['no_data'];

		$pagination = get_pagination($rows_count, $G_PAGE, "index.php", $get, 4);
		$pagination_class = strlen($pagination) ? "" : "display_none";

		eval("\$output = \"" . get_template("my_current_services") . "\";");

		$scripts[] = $settings['shop_url_slash'] . "jscripts/my_current_services.js?version=" . VERSION;
		$stylesheets[] = $settings['shop_url_slash'] . "styles/style_my_current_services.css?version=" . VERSION;

		return $output;
	}

}