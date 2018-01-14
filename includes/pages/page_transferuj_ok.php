<?php

/**
 * Created by MilyGosc.
 * URL: http://forum.sklep-sms.pl/showthread.php?tid=88
 */

$heart->register_page("transferuj_ok", "PageTransferujOk");

class PageTransferujOk extends PageSimple
{
	const PAGE_ID = "transferuj_ok";
	protected $template = "transferuj_ok";

	function __construct()
	{
		global $lang;
		$this->title = "Płatność Zaakceptowana";

		parent::__construct();
	}
}