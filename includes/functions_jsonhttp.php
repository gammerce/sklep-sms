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

	$warnings = array();
	switch ($type) {
		case "username":
			if (strlen($data) < 2)
				$warnings[] = $lang->sprintf($lang->field_length_min_warn, 2);
			if ($data != htmlspecialchars($data))
				$warnings[] = $lang->username_chars_warn;

			break;

		case "nick":
			if (strlen($data) < 2)
				$warnings[] = $lang->sprintf($lang->field_length_min_warn, 2);
			else if (strlen($data) > 32)
				$warnings[] = $lang->sprintf($lang->field_length_max_warn, 32);

			break;

		case "password":
			if (strlen($data) < 6)
				$warnings[] = $lang->sprintf($lang->field_length_min_warn, 6);

			break;

		case "email":
			if (!filter_var($data, FILTER_VALIDATE_EMAIL))
				$warnings[] = $lang->wrong_email;

			break;

		case "ip":
			if (!filter_var($data, FILTER_VALIDATE_IP))
				$warnings[] = $lang->wrong_ip;

			break;

		case "sid":
			if (!valid_steam($data) || strlen($data) > 32)
				$warnings[] = $lang->wrong_sid;

			break;

		case "uid":
			if (!strlen($data))
				$warnings[] = $lang->field_no_empty;
			else if (!is_numeric($data))
				$warnings[] = $lang->field_must_be_number;

			break;

		case "service_description":
			if (strlen($data) > 28)
				$warnings[] = $lang->sprintf($lang->field_length_max_warn, 28);

			break;

		case "sms_code":
			if (!strlen($data))
				$warnings[] = $lang->field_no_empty;
			else if (strlen($data) > 16)
				$warnings[] = $lang->return_code_length_warn;

			break;

		case "number":
			if (!strlen($data))
				$warnings[] = $lang->field_no_empty;
			else if (!is_numeric($data))
				$warnings[] = $lang->field_must_be_number;

			break;
	}

	return $warnings;
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
}

/**
 * @param string $id
 * @param string $text
 * @param string $template
 */
function actionbox_output($id, $text = "", $template = "")
{
	$output['return_id'] = $id;
	$output['text'] = $text;
	if (strlen($template))
		$output['template'] = $template;

	output_page(json_encode($output), "Content-type: text/plain; charset=\"UTF-8\"");
}