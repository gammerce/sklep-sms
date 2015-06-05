<?php

abstract class Page {

	/**
	 * @var int 	-1 musi byc niezalogowany
	 * 				0 obojetne
	 * 				1 musi byc zalogowany
	 */
	protected $require_login = 0;

	function __construct() {
		global $heart;

		$heart->page_title = $this->title;
	}

	/**
	 * Zwraca treść danej strony po przejściu wszystkich filtrów
	 *
	 * @param array $get - dane get
	 * @param array $post - dane post
	 * @return string - zawartość do wyświetlenia
	 */
	public function get_content($get, $post) {
		global $lang;

		if ($this->require_login == 1 && !is_logged())
			return $lang['must_be_logged_in'];

		if ($this->require_login == -1 && is_logged())
			return $lang['must_be_logged_out'];

		return $this->content($get, $post);
	}

	/**
	 * Zwraca treść danej strony
	 *
	 * @param string $get
	 * @param string $post
	 * @return string
	 */
	abstract protected function content($get, $post);

}

abstract class PageSimple extends Page {

	function __construct() {
		if (!isset($this->template) )
			throw new Exception('Class ' . __CLASS__ . ' has to have field $template because it extends class PageSimple');
	}

	protected function content($get, $post) {
		global $lang;

		eval("\$output = \"" . get_template($this->template) . "\";");
		return $output;
	}

}