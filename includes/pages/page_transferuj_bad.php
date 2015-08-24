/**
* Created by MilyGosc.
* URL: http://forum.sklep-sms.pl/showthread.php?tid=88
*/

<?php

$heart->register_page("transferuj_bad", "PageTransferujBad");

class PageTransferujBad extends PageSimple // PageExample - nazwa klasy, która bêdzie odpowiedzialna za obs³ugê strony
{

	const PAGE_ID = "transferuj_bad";
	protected $template = "transferuj_bad";

	function __construct()
	{
		global $lang;
		$this->title = "Płatność Odrzucona";

		parent::__construct();
	}

}