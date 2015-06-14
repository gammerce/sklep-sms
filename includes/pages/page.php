<?php

abstract class Page
{

	protected $title = "";

	function __construct()
	{
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
	public function get_content($get, $post)
	{
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

abstract class PageSimple extends Page
{

	function __construct()
	{
		if (!isset($this->template))
			throw new Exception('Class ' . get_class($this) . ' has to have field $template because it extends class PageSimple');

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $lang;

		eval("\$output = \"" . get_template($this->template) . "\";");
		return $output;
	}

}