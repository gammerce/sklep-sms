<?php

use App\Models\Server;
use App\Models\User;
use App\Support\Collection;
use App\System\Auth;
use App\System\Database;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\View\Html\Div;
use App\View\Html\DOMElement;
use App\View\Html\Li;
use App\View\Html\Ul;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get the available container instance.
 *
 * @param string $abstract
 * @param array $parameters
 * @return mixed|\Illuminate\Container\Container|\App\System\Application
 */
function app($abstract = null, array $parameters = [])
{
    if ($abstract === null) {
        return Container::getInstance();
    }

    return Container::getInstance()->makeWith($abstract, $parameters);
}

function get_row_limit($page, $rowLimit = 0)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $rowLimit = $rowLimit ? $rowLimit : $settings['row_limit'];

    return ($page - 1) * $rowLimit . "," . $rowLimit;
}

/* User functions */
/**
 * Sprawddza czy użytkownik jest zalogowany
 *
 * @return bool
 */
function is_logged()
{
    /** @var Auth $auth */
    $auth = app()->make(Auth::class);

    return $auth->check();
}

/**
 * @param string $privilege
 * @param User $user
 *
 * @return bool
 */
function get_privileges($privilege, $user = null)
{
    // Jeżeli nie podano użytkownika
    if ($user === null) {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();
    }

    if ($user === null) {
        return false;
    }

    $adminPrivileges = [
        "manage_settings",
        "view_groups",
        "manage_groups",
        "view_player_flags",
        "view_user_services",
        "manage_user_services",
        "view_income",
        "view_users",
        "manage_users",
        "view_sms_codes",
        "manage_sms_codes",
        "view_service_codes",
        "manage_service_codes",
        "view_antispam_questions",
        "manage_antispam_questions",
        "view_services",
        "manage_services",
        "view_servers",
        "manage_servers",
        "view_logs",
        "manage_logs",
        "update",
    ];

    if (in_array($privilege, $adminPrivileges)) {
        return $user->hasPrivilege('acp') && $user->hasPrivilege($privilege);
    }

    return $user->hasPrivilege($privilege);
}

function create_dom_element($name, $content = "", $data = [])
{
    $element = new DOMElement($content);
    $element->setName($name);

    foreach ($data as $key => $value) {
        $element->setParam($key, $value);
    }

    return $element;
}

function get_platform($platform)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    if (in_array($platform, ["engine_amxx", Server::TYPE_AMXMODX])) {
        return $lang->t('amxx_server');
    }

    if (in_array($platform, ["engine_sm", Server::TYPE_SOURCEMOD])) {
        return $lang->t('sm_server');
    }

    return $platform;
}

function is_server_platform($platform)
{
    return in_array($platform, [Server::TYPE_AMXMODX, Server::TYPE_SOURCEMOD]);
}

function get_ip(Request $request = null)
{
    $request = $request ?: app()->make(Request::class);

    if ($request->server->has('HTTP_CF_CONNECTING_IP')) {
        $cfIpRanges = [
            "103.21.244.0/22",
            "103.22.200.0/22",
            "103.31.4.0/22",
            "104.16.0.0/12",
            "108.162.192.0/18",
            "131.0.72.0/22",
            "141.101.64.0/18",
            "162.158.0.0/15",
            "172.64.0.0/13",
            "173.245.48.0/20",
            "188.114.96.0/20",
            "190.93.240.0/20",
            "197.234.240.0/22",
            "198.41.128.0/17",
        ];

        foreach ($cfIpRanges as $range) {
            if (ip_in_range($request->server->get('REMOTE_ADDR'), $range)) {
                return $request->server->get('HTTP_CF_CONNECTING_IP');
            }
        }
    }

    return $request->server->get('REMOTE_ADDR');
}

/**
 * Zwraca datę w odpowiednim formacie
 *
 * @param int|string $timestamp
 * @param string $format
 *
 * @return string
 */
function convert_date($timestamp, $format = "")
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    if (!strlen($format)) {
        $format = $settings->getDateFormat();
    }

    if (my_is_integer($timestamp)) {
        $date = new DateTime("@$timestamp");
    } else {
        $date = new DateTime($timestamp);
    }

    return $date->format($format);
}

/**
 * Returns sms cost net by number
 *
 * @param string $number
 *
 * @return int
 */
function get_sms_cost($number)
{
    if (strlen($number) < 4) {
        return 0;
    }

    if ($number[0] == "7") {
        return $number[1] == "0" ? 50 : intval($number[1]) * 100;
    }

    if ($number[0] == "9") {
        return intval($number[1] . $number[2]) * 100;
    }

    return 0;
}

/**
 * Returns sms provision from given net price
 *
 * @param int $smsPrice
 * @return int
 */
function get_sms_provision($smsPrice)
{
    return (int) ceil($smsPrice / 2);
}

function hash_password($password, $salt)
{
    return md5(md5($password) . md5($salt));
}

function escape_filename($filename)
{
    $filename = str_replace('/', '_', $filename);
    $filename = str_replace(' ', '_', $filename);
    $filename = str_replace('.', '_', $filename);

    return $filename;
}

function get_random_string($length)
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"; //length:36
    $finalRand = "";
    for ($i = 0; $i < $length; $i++) {
        $finalRand .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $finalRand;
}

function is_steamid_valid($steamid)
{
    return preg_match('/\bSTEAM_([0-9]{1}):([0-9]{1}):([0-9])+$/', $steamid) ? '1' : '0';
}

function seconds_to_time($seconds)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");

    return $dtF
        ->diff($dtT)
        ->format("%a {$lang->t('days')} {$lang->t('and')} %h {$lang->t('hours')}");
}

function custom_mb_str_split($string)
{
    return preg_split('/(?<!^)(?!$)/u', $string);
}

function searchWhere($searchIds, $search, &$where)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $searchWhere = [];
    $searchLike = $db->escape('%' . implode('%', custom_mb_str_split($search)) . '%');

    foreach ($searchIds as $searchId) {
        $searchWhere[] = "{$searchId} LIKE '{$searchLike}'";
    }

    if (!empty($searchWhere)) {
        $searchWhere = implode(" OR ", $searchWhere);
        if (strlen($where)) {
            $where .= " AND ";
        }

        $where .= "( {$searchWhere} )";
    }
}

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
            $netmask = str_replace('*', '0', $netmask);
            $netmaskDec = ip2long($netmask);

            return (ip2long($ip) & $netmaskDec) == (ip2long($range) & $netmaskDec);
        } else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode('.', $range);
            while (count($x) < 4) {
                $x[] = '0';
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf(
                "%u.%u.%u.%u",
                empty($a) ? '0' : $a,
                empty($b) ? '0' : $b,
                empty($c) ? '0' : $c,
                empty($d) ? '0' : $d
            );
            $rangeDec = ip2long($range);
            $ipDec = ip2long($ip);

            # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
            #$netmaskDec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

            # Strategy 2 - Use math to create it
            $wildcardDec = pow(2, 32 - $netmask) - 1;
            $netmaskDec = ~$wildcardDec;

            return ($ipDec & $netmaskDec) == ($rangeDec & $netmaskDec);
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !== false) {
            // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-') !== false) {
            // A-B format
            list($lower, $upper) = explode('-', $range, 2);
            $lowerDec = (float) sprintf("%u", ip2long($lower));
            $upperDec = (float) sprintf("%u", ip2long($upper));
            $ipDec = (float) sprintf("%u", ip2long($ip));

            return $ipDec >= $lowerDec && $ipDec <= $upperDec;
        }

        return false;
    }
}

function ends_at($string, $end)
{
    return substr($string, -strlen($end)) == $end;
}

function starts_with($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === (string) $needle;
}

function str_contains($string, $needle)
{
    return strpos($string, $needle) !== false;
}

/**
 * Prints var_dump in pre
 *
 * @param mixed $a
 */
function pr($a)
{
    echo "<pre>";
    var_dump($a);
    echo "</pre>";
}

/**
 * @param mixed $val
 *
 * @return bool
 */
function my_is_integer($val)
{
    return strlen($val) && trim($val) === strval(intval($val));
}

function array_get($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

function captureRequest()
{
    $queryAttributes = [];
    foreach ($_GET as $key => $value) {
        $queryAttributes[$key] = urldecode($value);
    }

    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->query->replace($queryAttributes);

    return $request;
}

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
                $warnings[] = $lang->t('field_length_min_warn', 2);
            }
            if ($data != htmlspecialchars($data)) {
                $warnings[] = $lang->t('username_chars_warn');
            }

            break;

        case "nick":
            if (strlen($data) < 2) {
                $warnings[] = $lang->t('field_length_min_warn', 2);
            } else {
                if (strlen($data) > 32) {
                    $warnings[] = $lang->t('field_length_max_warn', 32);
                }
            }

            break;

        case "password":
            if (strlen($data) < 6) {
                $warnings[] = $lang->t('field_length_min_warn', 6);
            }

            break;

        case "email":
            if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                $warnings[] = $lang->t('wrong_email');
            }

            break;

        case "ip":
            if (!filter_var($data, FILTER_VALIDATE_IP)) {
                $warnings[] = $lang->t('wrong_ip');
            }

            break;

        case "sid":
            if (!is_steamid_valid($data) || strlen($data) > 32) {
                $warnings[] = $lang->t('wrong_sid');
            }

            break;

        case "uid":
            if (!strlen($data)) {
                $warnings[] = $lang->t('field_no_empty');
            } else {
                if (!is_numeric($data)) {
                    $warnings[] = $lang->t('field_must_be_number');
                }
            }

            break;

        case "service_description":
            if (strlen($data) > 28) {
                $warnings[] = $lang->t('field_length_max_warn', 28);
            }

            break;

        case "sms_code":
            if (!strlen($data)) {
                $warnings[] = $lang->t('field_no_empty');
            } else {
                if (strlen($data) > 16) {
                    $warnings[] = $lang->t('return_code_length_warn');
                }
            }

            break;

        case "number":
            if (!strlen($data)) {
                $warnings[] = $lang->t('field_no_empty');
            } else {
                if (!is_numeric($data)) {
                    $warnings[] = $lang->t('field_must_be_number');
                }
            }

            break;
    }

    return $warnings;
}

function format_warnings(array $warnings)
{
    $output = [];

    foreach ($warnings as $brick => $warning) {
        if ($warning) {
            $items = collect($warning)
                ->map(function ($text) {
                    return new Li($text);
                })
                ->all();

            $help = new Ul($items);
            $help->addClass("form_warning help is-danger");
            $output[$brick] = $help->toHtml();
        }
    }

    return $output;
}

function get_error_code(PDOException $e)
{
    return $e->errorInfo[1];
}

function semantic_to_number($version)
{
    $parts = explode('.', $version);

    if (count($parts) < 3) {
        return null;
    }

    return $parts[0] * 10000 + $parts[1] * 100 + $parts[2];
}

function collect($items)
{
    return new Collection($items);
}

function is_list(array $array)
{
    return ctype_digit(implode('', array_keys($array)));
}

/**
 * @param mixed $value
 * @return int|null
 */
function as_int($value)
{
    if ($value === null || $value === "") {
        return null;
    }

    return (int) $value;
}

// https://stackoverflow.com/questions/7153000/get-class-name-from-file/44654073
function get_class_from_file($path)
{
    $fp = fopen($path, 'r');
    $buffer = '';
    $i = 0;

    while (!feof($fp)) {
        $buffer .= fread($fp, 512);
        $tokens = token_get_all($buffer);

        if (strpos($buffer, '{') === false) {
            continue;
        }

        for (; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === '{') {
                        return $tokens[$i + 2][1];
                    }
                }
            }
        }
    }

    return null;
}
