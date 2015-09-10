<?php

class Language
{

	private $g_language;

	private $g_language_short;

	private $g_lang_list;

	/** @var  array */
	protected $translations;

	function __construct($lang = "polish")
	{
		$this->g_lang_list = array(
			'polish' => "pl",
			'english' => "en"
		);
		$this->set_language($lang);
	}

	public function get_current_language()
	{
		return $this->g_language;
	}

	public function get_current_language_short()
	{
		return $this->g_language_short;
	}

	public function get_language_by_short($short)
	{
		return array_search(strtolower($short), $this->g_lang_list);
	}

	public function set_language($language)
	{
		$language = escape_filename(strtolower($language));
		if (!strlen($language) || !is_dir(SCRIPT_ROOT . "includes/languages/" . $language)) {
			return;
		}

		if ($this->g_language == $language) {
			return;
		}

		// Ustawiamt obeny język
		$this->g_language = $language;
		$this->g_language_short = if_isset($this->g_lang_list[$language], "");

		$filesToInclude = array();

		// Ładujemy ogólną bibliotekę językową
		$filesToInclude[] = SCRIPT_ROOT . "includes/languages/general.php";

		// Ładujemy ogólne biblioteki językowe języka
		foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}") as $file) {
			if (ends_at($file, ".php")) {
				$filesToInclude[] = SCRIPT_ROOT . "includes/languages/{$language}/{$file}";
			}
		}

		// Ładujemy bilioteki dla PA
		if (admin_session()) {
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/admin") as $file) {
				if (ends_at($file, ".php")) {
					$filesToInclude[] = SCRIPT_ROOT . "includes/languages/{$language}/admin/{$file}";
				}
			}
		} else {
			foreach (scandir(SCRIPT_ROOT . "includes/languages/{$language}/user") as $file) {
				if (ends_at($file, ".php")) {
					$filesToInclude[] = SCRIPT_ROOT . "includes/languages/{$language}/user/{$file}";
				}
			}
		}

		// Dodajemy translacje
		foreach ($filesToInclude as $path) {
			if (!file_exists($path)) {
				continue;
			}

			if (!isset($l) || !is_array($l)) {
				continue;
			}

			foreach ($l as $key => $val) {
				$this->translations[$key] = $val;
			}
		}
	}

	public function get($key)
	{
		return if_isset($this->translations[$key], $key);
	}

	public function sprintf($string)
	{
		$arg_list = func_get_args();
		$num_args = count($arg_list);

		for ($i = 1; $i < $num_args; $i++)
			$string = str_replace('{' . $i . '}', $arg_list[$i], $string);

		return $string;
	}

	public function strtoupper($string)
	{
		return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
	}

}