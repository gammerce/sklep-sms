<?php

$heart->register_block("content", "BlockContent");

class BlockContent extends Block
{

	/** @var  Page */
	protected $page;

	public function get_content_class()
	{
		return "content";
	}

	public function get_content_id()
	{
		return "content";
	}

	// Nadpisujemy get_content, aby wyswieltac info gdy nie jest zalogowany lub jest zalogowany, lecz nie powinien
	public function get_content($get, $post)
	{
		global $heart, $lang, $G_PID;

		if (($this->page = $heart->get_page($G_PID)) === NULL)
			return NULL;

		if (object_implements($this->page, "I_BeLoggedMust") && !is_logged())
			return $lang->translate('must_be_logged_in');

		if (object_implements($this->page, "I_BeLoggedCannot") && is_logged())
			return $lang->translate('must_be_logged_out');

		return $this->content($get, $post);
	}

	protected function content($get, $post)
	{
		return $this->page->get_content($get, $post);
	}

}