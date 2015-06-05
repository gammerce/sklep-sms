<?php

$heart->register_page("register", "PageRegister");

class PageRegister extends Page {

	protected $require_login = -1;
	protected $title = "Formularz rejestracyjny";

	protected function content($get, $post) {
		global $db, $settings, $lang;

		$antispam_question = $db->fetch_array_assoc($db->query(
			"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"ORDER BY RAND() " .
			"LIMIT 1"
		));

		$sign = md5($antispam_question['id'] . $settings['random_key']);

		eval("\$output = \"" . get_template("register") . "\";");
		return $output;
	}

}