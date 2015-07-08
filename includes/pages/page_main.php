<?php

$heart->register_page("main_content", "PageMain");

class PageMain extends PageSimple
{

	const PAGE_ID = "main_content";
	protected $template = "main_content";

	function __construct()
	{
		global $lang;
		$this->title = $lang->main_page;

		parent::__construct();
	}

}