<?php

$heart->register_page("forgotten_password", "PageForgottenPassword");

class PageForgottenPassword extends PageSimple {

	protected $template = "forgotten_password";
	protected $require_login = -1;
	protected $title = "Odzyskanie hasła";

}