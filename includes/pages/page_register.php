<?php

$heart->register_page("register", "PageRegister");

class PageRegister extends Page
{

	protected $require_login = -1;

	function __construct()
	{
		global $lang;
		$this->title = $lang->register;

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $scripts, $stylesheets;

		$antispam_question = $db->fetch_array_assoc($db->query(
			"SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
			"ORDER BY RAND() " .
			"LIMIT 1"
		));
		$_SESSION['asid'] = $antispam_question['id'];

		$scripts[] = $settings['shop_url_slash'] . "jscripts/register.js?version=" . VERSION;
		$stylesheets[] = $settings['shop_url_slash'] . "styles/style_register.css?version=" . VERSION;

		eval("\$output = \"" . get_template("register") . "\";");
		return $output;
	}

}