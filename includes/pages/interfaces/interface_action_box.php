<?php

interface IPageAdmin_ActionBox
{

	/**
	 * Zwraca html action boxa
	 *
	 * @param $box_id
	 * @param array $post Dane $_POST
	 *
	 * @return string|null mixed
	 */
	public function get_action_box($box_id, $post);

}