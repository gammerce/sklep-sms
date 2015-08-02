<?php

$heart->register_page("transaction_services", "PageAdminTransactionServices", "admin");

class PageAdminTransactionServices extends PageAdmin implements IPageAdminActionBox
{

	const PAGE_ID = "transaction_services";
	protected $privilage = "manage_settings";

	function __construct()
	{
		global $lang;
		$this->title = $lang->transaction_services;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $lang, $G_PAGE, $templates;

		// Pobranie listy serwisów transakcyjnych
		$result = $db->query(
			"SELECT SQL_CALC_FOUND_ROWS * FROM `" . TABLE_PREFIX . "transaction_services` " .
			"LIMIT " . get_row_limit($G_PAGE)
		);
		$rows_count = $db->get_column("SELECT FOUND_ROWS()", "FOUND_ROWS()");

		$i = 0;
		$tbody = "";
		while ($row = $db->fetch_array_assoc($result)) {
			$i += 1;
			$row['sms'] = $row['sms'] ? $lang->strtoupper($lang->yes) : $lang->strtoupper($lang->no);
			$row['transfer'] = $row['transfer'] ? $lang->strtoupper($lang->yes) : $lang->strtoupper($lang->no);

			// Pobranie przycisku edycji
			$button_edit = create_dom_element("img", "", array(
				'id' => "edit_row_{$i}",
				'src' => "images/edit.png",
				'title' => $lang->edit . " " . $row['name']
			));

			// Pobranie danych do tabeli
			$tbody .= eval($templates->render("admin/transaction_services_trow"));
		}

		// Nie ma zadnych danych do wyswietlenia
		if (!strlen($tbody))
			$tbody = eval($templates->render("admin/no_records"));

		// Pobranie paginacji
		$pagination = get_pagination($rows_count, $G_PAGE, "admin.php", $get);
		if (strlen($pagination))
			$tfoot_class = "display_tfoot";

		// Pobranie nagłówka tabeli
		$thead = eval($templates->render("admin/transaction_services_thead"));

		// Pobranie struktury tabeli
		$output = eval($templates->render("admin/table_structure"));
		return $output;
	}

	public function get_action_box($box_id, $data)
	{
		global $db, $lang, $templates;

		if (!get_privilages("manage_settings"))
			return array(
				'id'	=> "not_logged_in",
				'text'	=> $lang->not_logged_or_no_perm
			);

		switch($box_id) {
			case "transaction_service_edit":
				// Pobranie danych o metodzie płatności
				$result = $db->query($db->prepare(
					"SELECT * FROM `" . TABLE_PREFIX . "transaction_services` " .
					"WHERE `id` = '%s'",
					array($data['id'])
				));
				$transaction_service = $db->fetch_array_assoc($result);

				$transaction_service['id'] = htmlspecialchars($transaction_service['id']);
				$transaction_service['name'] = htmlspecialchars($transaction_service['name']);
				$transaction_service['data'] = json_decode($transaction_service['data']);

				$data_values = "";
				foreach ($transaction_service['data'] as $name => $value) {
					switch ($name) {
						case 'sms_text':
							$text = $lang->strtoupper($lang->sms_code);
							break;
						case 'account_id':
							$text = $lang->strtoupper($lang->account_id);
							break;
						default:
							$text = $lang->strtoupper($name);
							break;
					}
					$data_values .= eval($templates->render("tr_name_input"));
				}

				$output = eval($templates->render("admin/action_boxes/transaction_service_edit"));
				break;
		}

		return array(
			'id'		=> "ok",
			'template'	=> $output
		);
	}

}