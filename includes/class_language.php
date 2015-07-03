<?php

class Language
{

	private $language;
	private $language_short;
	private $lang_list;

	function __construct($lang = "polish")
	{
		$this->lang_list = array(
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

	public function get_language_by_short($short)
	{
		return array_search(strtolower($short), $this->lang_list);
	}

	public function set_language($language)
	{
		$language = escape_filename(strtolower($language));
		if (!strlen($language) || !is_dir(SCRIPT_ROOT . "includes/languages/" . $language))
			return;

		if ($this->language == $language)
			return;

		$this->language = $language;
		$this->language_short = if_isset($this->lang_list[$language], "");

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

		// We must unite and protect our language variables!
		$lang_keys_ignore = array('language', 'language_short', 'lang_list');

		if (isset($l) && is_array($l))
			foreach ($l as $key => $val)
				if (!in_array($key, $lang_keys_ignore))
					$this->$key = $val;
	}

	public function sprintf($string)
	{
		$arg_list = func_get_args();
		$num_args = count($arg_list);

		for ($i = 1; $i < $num_args; $i++)
			$string = str_replace('{' . $i . '}', $arg_list[$i], $string);

		return $string;
	}

}