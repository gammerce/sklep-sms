<?php

$heart->register_page("contact", "PageContact");

class PageContact extends PageSimple
{

	const PAGE_ID = "contact";
	protected $template = "contact";

	function __construct()
	{
		global $lang;
		$this->title = $lang->contact;

		parent::__construct();
	}

}