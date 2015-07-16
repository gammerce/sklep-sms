<?php

$heart->register_page("regulations", "PageRegulations");

class PageRegulations extends PageSimple
{

	const PAGE_ID = "regulations";
	protected $template = "regulations_desc";

	function __construct()
	{
		global $lang;
		$this->title = $lang->regulations;

		parent::__construct();
	}

}