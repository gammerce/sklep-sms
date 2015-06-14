<?php

$heart->register_page("reset_password", "PageResetPassword");

class PageResetPassword extends Page
{

	protected $require_login = -1;

	function __construct()
	{
		global $lang;
		$this->title = $lang['reset_password'];

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $scripts;

		// Brak podanego kodu
		if (!strlen($get['code']))
			return $lang['no_reset_key'];

		$result = $db->query($db->prepare(
			"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
			"WHERE `reset_password_key` = '%s'",
			array($get['code'])
		));

		if (!$db->num_rows($result)) // Nie znalazło użytkownika z takim kodem
			return $lang['wrong_reset_key'];

		$row = $db->fetch_array_assoc($result);
		$sign = md5($row['uid'] . $settings['random_key']);

		$scripts[] = $settings['shop_url_slash'] . "jscripts/modify_password.js?version=" . VERSION;

		eval("\$output = \"" . get_template("reset_password") . "\";");
		return $output;
	}

}