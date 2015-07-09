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

		// Ładujemy ogólną bibliotekę językową
		if (file_exists(SCRIPT_ROOT . "includes/languages/general.php"))
			include SCRIPT_ROOT . "includes/languages/general.php";

		// Ładujemy ogólne biblioteki językowe języka
		foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}") as $file)
			if (ends_at($file, ".php"))
				include SCRIPT_ROOT . "includes/languages/{$language}/{$file}";

		// Ładujemy bilioteki dla PA
		if (admin_session()) {
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/admin") as $file)
				if (ends_at($file, ".php"))
					include SCRIPT_ROOT . "includes/languages/{$language}/admin/{$file}";
		} else {
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/user") as $file)
				if (ends_at($file, ".php"))
					include SCRIPT_ROOT . "includes/languages/{$language}/user/{$file}";
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

	public function strtoupper($string) {
		return strtoupper($string);
	}

}