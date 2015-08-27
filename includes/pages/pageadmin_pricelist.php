<?php

$heart->register_page("pricelist", "PageAdminPriceList", "admin");

class PageAdminPriceList extends PageAdmin implements IPageAdmin_ActionBox
{

	const PAGE_ID = "pricelist";
	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->pricelist;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $heart, $db, $lang, $G_PAGE, $templates;

		// Pobranie cen
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * " .
			"FROM `" . TABLE_PREFIX . "pricelist` " .
			"ORDER BY `service`, `server`, `tariff` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			// Pobranie przycisku edycji oraz usuwania
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang->edit . " " . $row['tariff']
			));
			$button_delete = create_dom_element("img", "", array(
				'id' => "delete_row_{$i}",
				'src' => "images/bin.png",
				'title' => $lang->delete . " " . $row['tariff']
			));

			if ($row['server'] != -1) {
				$temp_server = $heart->get_server($row['server']);
				$row['server'] = $temp_server['name'];
				unset($temp_server);
			} else
				$row['server'] = $lang->all_servers;

			$service = $heart->get_service($row['service']);

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/pricelist_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pobranie przycisku dodającego cenę
		$buttons = create_dom_element("input", "", array(
			'id' => "price_button_add",
			'type' => "button",
			'value' => $lang->add_price
		));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/pricelist_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $heart, $db, $lang, $templates;

		if (!get_privilages("manage_settings"))
			return array(
				'status' => "not_logged_in",
				'text' => $lang->not_logged_or_no_perm
			);

		if ($box_id == "price_edit") {
			$result = $db->query($db->prepare(
				"SELECT * FROM `" . TABLE_PREFIX . "pricelist` " .
				"WHERE `id` = '%d'",
				array($data['id'])
			));
			$price = $db->fetch_array_assoc($result);

			$all_servers = $price['server'] == -1 ? "selected" : "";
		}

		// Pobranie usług
		$services = "";
		foreach ($heart->get_services() as $service_id => $service)
			$services .= create_dom_element("option", $service['name'] . " ( " . $service['id'] . " )", array(
				'value' => $service['id'],
				'selected' => isset($price) && $price['service'] == $service['id'] ? "selected" : ""
			));

		// Pobranie serwerów
		$servers = "";
		foreach ($heart->get_servers() as $server_id => $server)
			$servers .= create_dom_element("option", $server['name'], array(
				'value' => $server['id'],
				'selected' => isset($price) && $price['server'] == $server['id'] ? "selected" : ""
			));

		// Pobranie taryf
		$tariffs = "";
		foreach ($heart->getTariffs() as $tariff)
			$tariffs .= create_dom_element("option", $tariff->getId(), array(
				'value' => $tariff->getId(),
				'selected' => isset($price) && $price['tariff'] == $tariff->getId() ? "selected" : ""
			));

		switch ($box_id) {
			case "price_add":
				$output = eval($templates->render("admin/action_boxes/price_add"));
				break;

			case "price_edit":
				$output = eval($templates->render("admin/action_boxes/price_edit"));
				break;
		}

		return array(
			'status' => 'ok',
			'template' => $output
		);
	}

}