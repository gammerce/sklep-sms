<?php

$heart->register_block("wallet", "BlockWallet");

class BlockWallet extends BlockSimple implements I_BeLoggedMust
{

	protected $template = "wallet";

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
		$content = $this->get_content($get, $post);

		return create_dom_element("a", $content, array(
			'id' => $this->get_content_id(),
			'class' => $content !== NULL ? $this->get_content_class() : "",
			'href' => "index.php?pid=payment_log"
		));
	}

}