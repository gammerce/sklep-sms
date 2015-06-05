<?php

$heart->register_block("content", "BlockContent");

class BlockContent extends Block {

	public function get_content_class() {
		return "content";
	}

	public function get_content_id() {
		return "content";
	}

	protected function content($get, $post) {
		global $heart, $G_PID;

		if (($page = $heart->get_page($G_PID)) === NULL)
			return NULL;

		return $page->get_content($get, $post);
	}

}