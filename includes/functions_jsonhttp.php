<?php

use App\TranslationManager;

/**
 * Sprawdza czy podane dane są prawidłowe dla danego typu
 *
 * @param string $type
 * @param        $data
 *
 * @return array
 */
function check_for_warnings($type, $data)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    $warnings = [];
    switch ($type) {
        case "username":
            if (strlen($data) < 2) {
                $warnings[] = $lang->sprintf($lang->translate('field_length_min_warn'), 2);
            }
            if ($data != htmlspecialchars($data)) {
                $warnings[] = $lang->translate('username_chars_warn');
            }

            break;

        case "nick":
            if (strlen($data) < 2) {
                $warnings[] = $lang->sprintf($lang->translate('field_length_min_warn'), 2);
            } else {
                if (strlen($data) > 32) {
                    $warnings[] = $lang->sprintf($lang->translate('field_length_max_warn'), 32);
                }
            }

            break;

        case "password":
            if (strlen($data) < 6) {
                $warnings[] = $lang->sprintf($lang->translate('field_length_min_warn'), 6);
            }

            break;

        case "email":
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $warnings[] = $lang->translate('wrong_email');
            }

            break;

        case "ip":
            if (!filter_var($data, FILTER_VALIDATE_IP)) {
                $warnings[] = $lang->translate('wrong_ip');
            }

            break;

        case "sid":
            if (!valid_steam($data) || strlen($data) > 32) {
                $warnings[] = $lang->translate('wrong_sid');
            }

            break;

        case "uid":
            if (!strlen($data)) {
                $warnings[] = $lang->translate('field_no_empty');
            } else {
                if (!is_numeric($data)) {
                    $warnings[] = $lang->translate('field_must_be_number');
                }
            }

            break;

        case "service_description":
            if (strlen($data) > 28) {
                $warnings[] = $lang->sprintf($lang->translate('field_length_max_warn'), 28);
            }

            break;

        case "sms_code":
            if (!strlen($data)) {
                $warnings[] = $lang->translate('field_no_empty');
            } else {
                if (strlen($data) > 16) {
                    $warnings[] = $lang->translate('return_code_length_warn');
                }
            }

            break;

        case "number":
            if (!strlen($data)) {
                $warnings[] = $lang->translate('field_no_empty');
            } else {
                if (!is_numeric($data)) {
                    $warnings[] = $lang->translate('field_must_be_number');
                }
            }

            break;
    }

    return $warnings;
}

/**
 * @param        $id
 * @param string $text
 * @param bool   $positive
 * @param array  $data
 */
function json_output($id, $text = "", $positive = false, $data = [])
{
    $output['return_id'] = $id;
    $output['text'] = $text;
    $output['positive'] = $positive;

    if (is_array($data) && !empty($data)) {
        $output = array_merge($output, $data);
    }

    output_page(json_encode($output), 1);
}
