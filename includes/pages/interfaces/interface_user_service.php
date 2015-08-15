<?php

interface IPageAdmin_UserService {

	/**
	 * @return string
	 */
	public function get_title();

	/**
	 * @param array $get
	 * @param array $post
	 * @return string|array
	 */
	public function get_content($get, $post);

}