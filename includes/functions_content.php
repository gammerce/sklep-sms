<?php

if (!defined("IN_SCRIPT"))
	die("Nie ma tu nic ciekawego.");

/**
 * @param string $element
 * @param bool $withenvelope
 * @return string
 */
function get_content($element, $withenvelope = true)
{
	global $heart;

	if (($block = $heart->get_block($element)) === NULL)
		return "";

	return $withenvelope ? $block->get_content_enveloped($_GET, $_POST) : $block->get_content($_GET, $_POST);
}