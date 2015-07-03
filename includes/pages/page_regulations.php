<?php

$heart->register_page("regulations", "PageRegulations");

class PageRegulations extends PageSimple
{

	protected $template = "regulations_desc";

	function __construct()
	{
		global $lang;
		$this->title = $lang->regulations;

		parent::__construct();
	}

}