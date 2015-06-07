<?php

/**
 * Sprawdza czy podane dane są prawidłowe dla danego typu
 *
 * @param string $type
 * @param $data
 * @return string
 */
function check_for_warnings($type, $data)
{
	global $lang;

	$output = "";
	switch($type) {
		case "username":
			if (strlen($data) < 2)
				$output .= newsprintf($lang['field_length_min_warn'], 2) . "<br />";
			if ($data != htmlspecialchars($data))
				$output .= $lang['username_chars_warn'] . "<br />";

			break;

		case "nick":
			if (strlen($data) < 2)
				$output .= newsprintf($lang['field_length_min_warn'], 2) . "<br />";
			else if (strlen($data) > 32)
				$output .= newsprintf($lang['field_length_max_warn'], 32) . "<br />";

			break;

		case "password":
			if (strlen($data) < 6)
				$output = newsprintf($lang['field_length_min_warn'], 6) . "<br />";

			break;

		case "email":
			if (!filter_var($data, FILTER_VALIDATE_EMAIL))
				$output = $lang['wrong_email'] . "<br />";

			break;

		case "ip":
			if (!filter_var($data, FILTER_VALIDATE_IP))
				$output = $lang['wrong_ip'] . "<br />";

			break;

		case "sid":
			if (!valid_steam($data) || strlen($data) > 32)
				$output = $lang['wrong_sid'] . "<br />";

			break;

		case "uid":
			if (!strlen($data))
				$output = $lang['field_empty'] . "<br />";
			else if (!is_numeric($data))
				$output = $lang['field_must_be_number'] . "<br />";

			break;

		case "service_description":
			if (strlen($data) > 28)
				$output = newsprintf($lang['field_length_max_warn'], 28) . "<br />";

			break;

		case "sms_code":
			if (!strlen($data))
				$output = $lang['field_empty'] . "<br />";
			else if (strlen($data) > 16)
				$output = $lang['return_code_length_warn'] . "<br />";

			break;

		case "number":
			if (!strlen($data))
				$output = $lang['field_empty'] . "<br />";
			else if (!is_numeric($data))
				$output = $lang['field_must_be_number'] . "<br />";

			break;
	}

	return $output;
}

/**
 * @param $id
 * @param string $text
 * @param bool $positive
 * @param array $data
 */
function json_output($id, $text = "", $positive = false, $data = array())
{
	$output['return_id'] = $id;
	$output['text'] = $text;
	$output['positive'] = $positive;
	if (is_array($data) && !empty($data))
		$output = array_merge($output, $data);
	output_page(json_encode($output), "Content-type: text/plain; charset=\"UTF-8\"");
	exit;
}