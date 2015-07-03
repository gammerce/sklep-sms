<?php

$heart->register_page("main_content", "PageMain");

class PageMain extends PageSimple
{

	protected $template = "main_content";

	function __construct()
	{
		global $lang;
		$this->title = $lang->main_page;

		parent::__construct();
	}

}