<?php

$heart->register_page("reset_password", "PageResetPassword");

class PageResetPassword extends Page implements I_BeLoggedCannot
{

	const PAGE_ID = "reset_password";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('reset_password');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $db, $settings, $lang, $templates;

		// Brak podanego kodu
		if (!strlen($get['code'])) {
			return $lang->translate('no_reset_key');
		}

		$result = $db->query($db->prepare(
			"SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
			"WHERE `reset_password_key` = '%s'",
			array($get['code'])
		));

		if (!$db->num_rows($result)) // Nie znalazło użytkownika z takim kodem
		{
			return $lang->translate('wrong_reset_key');
		}

		$row = $db->fetch_array_assoc($result);
		$sign = md5($row['uid'] . $settings['random_key']);

		$output = eval($templates->render("reset_password"));

		return $output;
	}

}