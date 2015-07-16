<?php

abstract class Block
{

	abstract public function get_content_class();

	abstract public function get_content_id();

	/**
	 * Zwraca treść danego bloku po przejściu wszystkich filtrów
	 *
	 * @param array $get - dane get
	 * @param array $post - dane post
	 * @return string|null - zawartość do wyświetlenia
	 */
	public function get_content($get, $post)
	{
		if ((object_implements($this, "I_BeLoggedMust") && !is_logged()) || (object_implements($this, "I_BeLoggedCannot") && is_logged()))
			return NULL;

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
	 * @return string|null
	 */
	public function get_content_enveloped($get, $post)
	{
		$content = $this->get_content($get, $post);

		return create_dom_element("div", $content, array(
			'id' => $this->get_content_id(),
			'class' => $content !== NULL ? $this->get_content_class() : ""
		));
	}

}

abstract class BlockSimple extends Block
{

	function __construct()
	{
		if (!isset($this->template))
			throw new Exception('Class ' . get_class($this) . ' has to have field $template because it extends class BlockSimple');
	}

	protected function content($get, $post)
	{
		global $user, $lang, $settings;

		eval("\$output = \"" . get_template($this->template) . "\";");
		return $output;
	}

}