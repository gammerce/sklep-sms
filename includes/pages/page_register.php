<?php

$heart->register_page("register", "PageRegister");

class PageRegister extends Page implements I_BeLoggedCannot
{
	const PAGE_ID = "register";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('register');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $templates;

		$antispam_question = $db->fetch_array_assoc($db->query(
			"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"ORDER BY RAND() " .
			"LIMIT 1"
		));
		$_SESSION['asid'] = $antispam_question['id'];

		$output = eval($templates->render("register"));

		return $output;
	}
}