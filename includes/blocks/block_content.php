<?php

$heart->register_block("content", "BlockContent");

class BlockContent extends Block
{

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
		global $lang;

		if ($this->require_login === 1 && !is_logged())
			return $lang->must_be_logged_in;

		if ($this->require_login === -1 && is_logged())
			return $lang->must_be_logged_out;

		return $this->content($get, $post);
	}

	protected function content($get, $post)
	{
		global $heart, $G_PID;

		if (($page = $heart->get_page($G_PID)) === NULL)
			return NULL;

		return $page->get_content($get, $post);
	}

}