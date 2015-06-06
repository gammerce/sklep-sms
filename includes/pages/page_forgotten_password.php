<?php

$heart->register_page("forgotten_password", "PageForgottenPassword");

class PageForgottenPassword extends PageSimple
{

	protected $template = "forgotten_password";
	protected $require_login = -1;
	protected $title = "Odzyskanie hasła";

	protected function content($get, $post) {
		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/modify_password.js?version=" . VERSION;

		return parent::content($get, $post);
	}

}