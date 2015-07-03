<?php

abstract class PageAdmin extends Page
{
	protected $privilage = "acp";

	public function get_content($get, $post)
	{
		if (!get_privilages($this->privilage)) {
			global $lang;
			return $lang->no_privilages;
		}

		return $this->content($get, $post);
	}
}