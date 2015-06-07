<?php

$heart->register_block("wallet", "BlockWallet");

class BlockWallet extends BlockSimple
{

	protected $template = "wallet";
	protected $require_login = 1;

	public function get_content_class()
	{
		return "wallet_status";
	}

	public function get_content_id()
	{
		return "wallet";
	}

	public function get_content_enveloped($get, $post)
	{
		if (($content = $this->get_content($get, $post)) === NULL)
			return NULL;

		return create_dom_element("a", $content, array(
			'id' => $this->get_content_id(),
			'class' => $this->get_content_class(),
			'href' => "index.php?pid=payment_log"
		));
	}

}