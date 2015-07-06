<?php

abstract class PageAdmin extends Page
{
	const PAGE_ID = "";
	protected $privilage = "acp";

	public function get_content($get, $post)
	{
		global $scripts, $settings;

		if (!get_privilages($this->privilage)) {
			global $lang;
			return $lang->no_privilages;
		}

		// Dodajemy wszystkie skrypty
		$script_path = "jscripts/admin/pages/" . $this::PAGE_ID . "/";
		if (strlen($this::PAGE_ID) && file_exists(SCRIPT_ROOT . $script_path))
			foreach (scandir(SCRIPT_ROOT . $script_path) as $file)
				if (ends_at($file, ".js"))
					$scripts[] = $settings['shop_url_slash'] . $script_path . $file . "?version=" . VERSION;

		return $this->content($get, $post);
	}
}