<?php

class Translator
{

	/**
	 * Current language
	 *
	 * @var string
	 */
	protected $language;

	/**
	 * Array of language => language short
	 *
	 * @var array
	 */
	protected $langList;

	/**
	 * Array of translations
	 *
	 * @var array
	 */
	protected $translations;

	function __construct($lang = 'polish')
	{
		$this->langList = array(
			'polish' => "pl",
			'english' => "en"
		);

		$this->setLanguage($lang);
	}

	public function getCurrentLanguage()
	{
		return $this->language;
	}

	public function getCurrentLanguageShort()
	{
		return $this->langList[$this->getCurrentLanguage()];
	}

	/**
	 * Returns full language name by its shortcut
	 *
	 * @param string $short
	 * @return string
	 */
	public function getLanguageByShort($short)
	{
		return array_search(strtolower($short), $this->langList);
	}

	/**
	 * Sets current language
	 *
	 * @param string $language Full language name
	 */
	public function setLanguage($language)
	{
		$language = strtolower($language);
		if (!strlen($language) || !isset($this->langList[$language]) || $this->getCurrentLanguage() == $language
			|| !is_dir(SCRIPT_ROOT . "includes/languages/" . $language)
		) {
			return;
		}

		// Ustawiamy obeny język
		$this->language = $language;

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

			$l = include $path;
//			ksort($l);
//
//			$save = array();
//			$save[] = '<?php';
//			$save[] = '';
//			$save[] = 'return array(';
//			foreach ($l as $key => $value) {
//				$value = str_replace("'", "\\'", $value);
//				$key = str_replace("'", "\\'", $key);
//				$save[] = "	'{$key}' => '{$value}',";
//			}
//			$save[] = '';
//			$save[] = ');';
//
//			file_put_contents($path, implode("\n", $save));

			if (!isset($l) || !is_array($l)) {
				continue;
			}

			foreach ($l as $key => $val) {
				$this->translations[$key] = $val;
			}
		}
	}

	/**
	 * Translate key to text
	 *
	 * @param string $key
	 * @return string
	 */
	public function translate($key)
	{
		return if_isset($this->translations[$key], $key);
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public function sprintf($string)
	{
		$arg_list = func_get_args();
		$num_args = count($arg_list);

		for ($i = 1; $i < $num_args; $i++) {
			$string = str_replace('{' . $i . '}', $arg_list[$i], $string);
		}

		return $string;
	}

	/**
	 * Strtoupper function
	 *
	 * @param $string
	 * @return string
	 */
	public function strtoupper($string)
	{
		return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
	}

}