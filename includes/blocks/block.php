<?php

abstract class Block {

	protected $require_login = 0;
	abstract public function get_content_class();
	abstract public function get_content_id();

	/**
	 * Zwraca treść danego bloku po przejściu wszystkich filtrów
	 *
	 * @param array $get - dane get
	 * @param array $post - dane post
	 * @return string - zawartość do wyświetlenia
	 */
	public function get_content($get, $post) {
		if ($this->require_login == 1 && !is_logged() || $this->require_login == -1 && is_logged())
			return "";

		return $this->content($get, $post);
	}

	/**
	 * Zwraca treść danego bloku
	 *
	 * @param string $get
	 * @param string $post
	 * @return string
	 */
	abstract protected function content($get, $post);

	/**
	 * Zwraca treść danego bloku w otoczce
	 *
	 * @param array $get
	 * @param array $post
	 * @return string
	 */
	public function get_content_enveloped($get, $post) {
		return create_dom_element("div", $this->get_content($get, $post), array(
			'id' => $this->get_content_id(),
			'class' => $this->get_content_class()
		));
	}

}

abstract class BlockSimple extends Block {

	function __construct() {
		if (!isset($this->template) )
			throw new Exception('Class ' . __CLASS__ . ' has to have field $template because it extends class BlockSimple');
	}

	protected function content($get, $post) {
		global $lang;

		eval("\$output = \"" . get_template($this->template) . "\";");
		return $output;
	}

}