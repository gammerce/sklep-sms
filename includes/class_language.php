<?php

class Language
{

	private $language;
	private $language_short;
	private $languages;

	function __construct($lang = "polish")
	{
		$this->languages = array(
			'polish' => "pl",
			'english' => "en"
		);
		$this->set_language($lang);
	}

	public function get_current_language()
	{
		return $this->language;
	}

	public function get_current_language_short()
	{
		return $this->language_short;
	}

	public function set_language($language)
	{
		$language = escape_filename(strtolower($language));
		if (!is_dir(SCRIPT_ROOT . "includes/languages/" . $language))
			return;

		if ($this->language == $language)
			return;

		global $lang;
		$this->language = $language;
		$this->language_short = if_isset($this->languages[$language], "");

		// Ładujemy globalną bibliotekę językową
		if (file_exists(SCRIPT_ROOT . "includes/languages/global.php"))
			include SCRIPT_ROOT . "includes/languages/global.php";

		// Ładujemy ogólną bibliotekę językową
		if (file_exists(SCRIPT_ROOT . "includes/languages/{$language}/{$language}.php"))
			include SCRIPT_ROOT . "includes/languages/{$language}/{$language}.php";

		if (in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin"))) { // Ładujemy bilioteki dla PA
			// Ładujemy wszystkie biblioteki językowe
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/admin") as $file)
				if (substr($file, -4) == ".php")
					include SCRIPT_ROOT . "includes/languages/{$language}/admin/{$file}";
		} else {
			// Ładujemy wszystkie biblioteki językowe
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}") as $file)
				if (substr($file, -4) == ".php" && $file != "{$language}.php")
					include SCRIPT_ROOT . "includes/languages/{$language}/{$file}";
		}
	}

	public function get_language_by_short($short)
	{
		return array_search(strtolower($short), $this->languages);
	}

}