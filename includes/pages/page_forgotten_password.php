<?php

$heart->register_page("forgotten_password", "PageForgottenPassword");

class PageForgottenPassword extends PageSimple implements I_BeLoggedCannot
{
	const PAGE_ID = "forgotten_password";
	protected $template = "forgotten_password";

	function __construct()
	{
		global $lang;
		$this->title = $lang->translate('forgotten_password');

		parent::__construct();
	}
}