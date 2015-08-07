<?php

$heart->register_page("user_own_services", "Page_UserOIwnServices");

class Page_UserOIwnServices extends Page implements I_BeLoggedMust
{

	const PAGE_ID = "user_own_services";

	function __construct()
	{
		global $lang;
		$this->title = $lang->user_own_services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $settings, $user, $lang, $G_PAGE, $templates;

		$user_own_services = "";
		$result = $db->query($db->prepare(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "players_services` " .
			"WHERE `uid` = '%d' " .
			"ORDER BY `id` DESC " .
			"LIMIT " . get_row_limit($G_PAGE, 4),
			array($user->getUid())
		));
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		while ($row = $db->fetch_array_assoc($result)) {
			if (($service_module = $heart->get_service_module($row['service'])) === NULL)
				continue;

			if (!object_implements($service_module, "IService_UserOwnServices"))
				continue;

			if ($settings['user_edit_service'] && object_implements($service_module, "IService_UserOwnServicesEdit"))
				$button_edit = create_dom_element("img", "", array(
					'class' => "edit_row",
					'src' => "images/pencil.png",
					'title' => $lang->edit,
					'style' => array(
						'height' => '24px'
					)
				));

			$user_own_services .= create_brick($service_module->user_own_service_info_get($row, $button_edit));
		}

		// Nie znalazło żadnych usług danego gracza
		if (!strlen($user_own_services))
			$user_own_services = $lang->no_data;

		$pagination = get_pagination($rows_count, $G_PAGE, "index.php", $get, 4);
		$pagination_class = strlen($pagination) ? "" : "display_none";

		$output = eval($templates->render("user_own_services"));

		return $output;
	}

}