<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

class Templates
{
	/**
	 * Pobranie szablonu.
	 *
	 * @param string $title Nazwa szablonu
	 * @param bool $install Prawda, jeżeli pobieramy szablon instalacji.
	 * @param bool $eslashes Prawda, jeżeli zawartość szablonu ma być "escaped".
	 * @param bool $htmlcomments Prawda, jeżeli chcemy dodać komentarze o szablonie.
	 * @return string|bool Szablon.
	 */
	public function get_template($title, $install = false, $eslashes = true, $htmlcomments = true)
	{
		global $settings, $lang;

		if (!$install) {
			if (strlen($lang->get_current_language_short())) {
				$filename = $title . "." . $lang->get_current_language_short();
				$temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
				else {
					$temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
					if (file_exists($temp))
						$path = $temp;
				}
			}

			if (!isset($path)) {
				$filename = $title;
				$temp = SCRIPT_ROOT . "themes/{$settings['theme']}/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
				else {
					$temp = SCRIPT_ROOT . "themes/default/{$filename}.html";
					if (file_exists($temp))
						$path = $temp;
				}
			}
		} else {
			if (strlen($lang->get_current_language_short())) {
				$filename = $title . "." . $lang->get_current_language_short();
				$temp = SCRIPT_ROOT . "install/templates/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
			}

			if (!isset($path)) {
				$filename = $title;
				$temp = SCRIPT_ROOT . "install/templates/{$filename}.html";
				if (file_exists($temp))
					$path = $temp;
			}
		}

		if (!isset($path))
			return FALSE;

		$template = file_get_contents($path);

		if ($htmlcomments)
			$template = "<!-- start: " . htmlspecialchars($title) . " -->\n{$template}\n<!-- end: " . htmlspecialchars($title) . " -->";

		if ($eslashes)
			$template = str_replace("\\'", "'", addslashes($template));

		$template = str_replace("{__VERSION__}", VERSION, $template);

		return $template;
	}

	/**
	 * Prepare a template for rendering to a variable.
	 *
	 * @param string $template The name of the template to get.
	 * @param boolean $eslashes True if template contents must be escaped, false if not.
	 * @param boolean $htmlcomments True to output HTML comments, false to not output.
	 * @return string The eval()-ready PHP code for rendering the template
	 */
	function render($template, $eslashes=true, $htmlcomments=true)
	{
		return 'return "' . $this->get_template($template, false, $eslashes, $htmlcomments) . '";';
	}

	/**
	 * Prepare a template for rendering to a variable.
	 *
	 * @param string $template The name of the template to get.
	 * @param boolean $eslashes True if template contents must be escaped, false if not.
	 * @param boolean $htmlcomments True to output HTML comments, false to not output.
	 * @return string The eval()-ready PHP code for rendering the template
	 */
	function install_render($template, $eslashes=true, $htmlcomments=true)
	{
		return 'return "' . $this->get_template($template, true, $eslashes, $htmlcomments) . '";';
	}
}