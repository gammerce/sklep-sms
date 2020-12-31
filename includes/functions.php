<?php

use App\Loggers\FileLogger;
use App\Models\PaymentPlatform;
use App\Models\User;
use App\Payment\General\PaymentMethod;
use App\Routing\UrlGenerator;
use App\Server\Platform;
use App\Support\Collection;
use App\Support\Expression;
use App\Support\Money;
use App\Support\QueryParticle;
use App\System\Application;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\User\Permission;
use App\View\Html\DOMElement;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Get the available container instance.
 *
 * @param string $abstract
 * @param array $parameters
 * @return mixed|Container|Application
 */
function app($abstract = null, array $parameters = [])
{
    if ($abstract === null) {
        return Container::getInstance();
    }

    return Container::getInstance()->makeWith($abstract, $parameters);
}

/**
 * @param Permission $permission
 * @param User|null $user
 * @return bool
 */
function can(Permission $permission, User $user = null)
{
    if (!$user) {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();
    }

    return $user->can($permission);
}

/**
 * @param Permission $permission
 * @param User|null $user
 * @return bool
 */
function cannot(Permission $permission, User $user = null)
{
    return !can($permission, $user);
}

/**
 * @param string $name
 * @param string $content
 * @param array $params
 * @return DOMElement
 */
function create_dom_element($name, $content = "", array $params = [])
{
    return new DOMElement($name, $content, $params);
}

/**
 * @param string $platform
 * @return bool
 */
function is_server_platform($platform)
{
    return in_array($platform, [Platform::AMXMODX, Platform::SOURCEMOD], true);
}

/**
 * @param Request $request
 * @return string|null
 */
function get_ip(Request $request)
{
    if ($request->server->has("HTTP_CF_CONNECTING_IP")) {
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
            if (ip_in_range($request->server->get("REMOTE_ADDR"), $range)) {
                return $request->server->get("HTTP_CF_CONNECTING_IP");
            }
        }
    }

    return $request->server->get("REMOTE_ADDR");
}

/**
 * Provide request platform
 *
 * @param Request $request
 * @return string
 */
function get_platform(Request $request)
{
    return $request->headers->get("User-Agent", "");
}

/**
 * Returns sms cost net by number
 *
 * @param string $number
 * @return Money
 */
function get_sms_cost($number)
{
    if (strlen($number) < 4) {
        return new Money(0);
    }

    if ($number[0] == "7") {
        return $number[1] == "0" ? new Money(50) : new Money(intval($number[1]) * 100);
    }

    if ($number[0] == "9") {
        return new Money(intval($number[1] . $number[2]) * 100);
    }

    return new Money(0);
}

/**
 * Returns sms provision from given net price
 *
 * @param Money $smsPrice
 * @return Money
 */
function get_sms_provision(Money $smsPrice)
{
    return new Money(ceil($smsPrice->asInt() / 2));
}

function hash_password($password, $salt)
{
    return md5(md5($password) . md5($salt));
}

function escape_filename($filename)
{
    $filename = str_replace("/", "_", $filename);
    $filename = str_replace(" ", "_", $filename);
    $filename = str_replace(".", "_", $filename);

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

/**
 * @param string $steamId
 * @return string
 */
function is_steam_id_valid($steamId)
{
    return !!preg_match('/\bSTEAM_([0-9]):([0-9]):([0-9])+$/', $steamId);
}

/**
 * @param int $seconds
 * @return string
 */
function seconds_to_time($seconds)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");

    return $dtF
        ->diff($dtT)
        ->format("%a " . $lang->t("days") . " " . $lang->t("and") . " %h " . $lang->t("hours"));
}

/**
 * @param string $string
 * @return string[]
 */
function custom_mb_str_split($string)
{
    return preg_split('/(?<!^)(?!$)/u', $string);
}

/**
 * @param string[] $columns
 * @param string $search
 * @return QueryParticle|null
 */
function create_search_query($columns, $search)
{
    if (!$columns) {
        return null;
    }

    $searchLike = "%" . implode("%", custom_mb_str_split($search)) . "%";

    $params = [];
    $values = [];

    foreach ($columns as $searchId) {
        $params[] = "{$searchId} LIKE ?";
        $values[] = $searchLike;
    }

    $queryParticle = new QueryParticle();
    $query = implode(" OR ", $params);
    $queryParticle->add("( $query )", $values);

    return $queryParticle;
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
    if (strpos($range, "/") !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode("/", $range, 2);
        if (strpos($netmask, ".") !== false) {
            // $netmask is a 255.255.0.0 format
            $netmask = str_replace("*", "0", $netmask);
            $netmaskDec = ip2long($netmask);

            return (ip2long($ip) & $netmaskDec) == (ip2long($range) & $netmaskDec);
        } else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode(".", $range);
            while (count($x) < 4) {
                $x[] = "0";
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf(
                "%u.%u.%u.%u",
                empty($a) ? "0" : $a,
                empty($b) ? "0" : $b,
                empty($c) ? "0" : $c,
                empty($d) ? "0" : $d
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
        if (strpos($range, "*") !== false) {
            // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace("*", "0", $range);
            $upper = str_replace("*", "255", $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, "-") !== false) {
            // A-B format
            list($lower, $upper) = explode("-", $range, 2);
            $lowerDec = (float) sprintf("%u", ip2long($lower));
            $upperDec = (float) sprintf("%u", ip2long($upper));
            $ipDec = (float) sprintf("%u", ip2long($ip));

            return $ipDec >= $lowerDec && $ipDec <= $upperDec;
        }

        return false;
    }
}

/**
 * @param string $string
 * @param string $end
 * @return bool
 */
function ends_at($string, $end)
{
    return substr($string, -strlen($end)) == $end;
}

/**
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function starts_with($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === (string) $needle;
}

if (!function_exists("str_contains")) {
    /**
     * @param string $string
     * @param string $needle
     * @return bool
     */
    function str_contains($string, $needle)
    {
        return strpos($string, $needle) !== false;
    }
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

/**
 * @param mixed $array
 * @param mixed $key
 * @param mixed $default
 * @return mixed|null
 */
function array_get($array, $key, $default = null)
{
    return isset($array[$key]) ? $array[$key] : $default;
}

/**
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function array_dot_get($array, $key, $default = null)
{
    foreach (explode(".", $key) as $segment) {
        if (!isset($array[$segment])) {
            return $default;
        }

        $array = $array[$segment];
    }

    return $array;
}

/**
 * @return Request
 */
function captureRequest()
{
    $queryAttributes = [];
    foreach ($_GET as $key => $value) {
        $queryAttributes[$key] = urldecode($value);
    }

    $request = Request::createFromGlobals();
    $request->query->replace($queryAttributes);

    return $request;
}

/**
 * @param PDOException $e
 * @return int
 */
function get_error_code(PDOException $e)
{
    return $e->errorInfo[1];
}

/**
 * @param mixed $items
 * @return Collection
 */
function collect($items = [])
{
    return new Collection($items);
}

/**
 * @param array $array
 * @return bool
 */
function is_list(array $array)
{
    if (empty($array)) {
        return true;
    }

    return ctype_digit(implode("", array_keys($array)));
}

/**
 * @param mixed $value
 * @return Money|null
 */
function as_money($value)
{
    if ($value === null || $value === "") {
        return null;
    }

    return new Money($value);
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

    if ($value instanceof Money) {
        return $value->asInt();
    }

    return (int) $value;
}

/**
 * @param mixed $value
 * @return float|null
 */
function as_float($value)
{
    if ($value === null || $value === "") {
        return null;
    }

    if ($value instanceof Money) {
        return $value->asFloat();
    }

    return (float) $value;
}

/**
 * @param mixed $value
 * @return string|null
 */
function as_string($value)
{
    if ($value === null) {
        return null;
    }

    if ($value instanceof Money) {
        return $value->asPrice();
    }

    return (string) $value;
}

/**
 * @param string $value
 * @return PaymentMethod|null
 */
function as_payment_method($value)
{
    try {
        return new PaymentMethod($value);
    } catch (UnexpectedValueException $e) {
        return null;
    }
}

/**
 * @param string $value
 * @return Platform|null
 */
function as_server_type($value)
{
    try {
        return new Platform($value);
    } catch (UnexpectedValueException $e) {
        return null;
    }
}

/**
 * @param string|int|DateTime|null $value
 * @return DateTime|null
 */
function as_datetime($value)
{
    if (!$value) {
        return null;
    }

    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    if ($value instanceof DateTime) {
        $date = clone $value;
    } elseif (my_is_integer($value)) {
        $date = new DateTime("@$value");
    } else {
        $date = new DateTime($value);
    }

    $date->setTimezone(new DateTimeZone($settings->getTimeZone()));

    return $date;
}

/**
 * @param string|int|DateTime|null $value
 * @return string
 */
function as_date_string($value)
{
    $date = as_datetime($value);
    return $date ? $date->format("Y-m-d") : "";
}

/**
 * @param int|string|DateTime|null $value
 * @param string $format
 * @return string
 */
function as_datetime_string($value, $format = "")
{
    if (!strlen($format)) {
        /** @var Settings $settings */
        $settings = app()->make(Settings::class);
        $format = $settings->getDateFormat();
    }

    $date = as_datetime($value);
    return $date ? $date->format($format) : null;
}

/**
 * @param int|string|DateTime|null $value
 * @return string
 */
function as_expiration_date_string($value)
{
    if ($value === -1 || $value === null) {
        return __("never");
    }

    return as_date_string($value);
}

/**
 * @param int|string|DateTime|null $value
 * @return string
 */
function as_expiration_datetime_string($value)
{
    if ($value === -1 || $value === null) {
        return __("never");
    }

    return as_datetime_string($value);
}

/**
 * @param DateTime|null $date
 * @return string|null
 */
function serialize_date($date)
{
    return $date ? $date->format("Y-m-d H:i:s") : null;
}

/**
 * @param string|float $value
 * @return int|null
 */
function price_to_int($value)
{
    if ($value === null || $value === "") {
        return null;
    }

    // We do it that way because of the floating point issues
    return (int) str_replace(".", "", number_format($value, 2));
}

/**
 * @param Permission[] $permissions
 * @return array
 */
function as_permission_list($permissions)
{
    return collect($permissions)
        ->map(function ($permission) {
            try {
                return new Permission($permission);
            } catch (UnexpectedValueException $e) {
                return null;
            }
        })
        ->filter(function ($permission) {
            return $permission;
        })
        ->all();
}

if (!function_exists("is_iterable")) {
    /**
     * @param mixed $value
     * @return bool
     */
    function is_iterable($value)
    {
        return is_array($value) || $value instanceof Traversable;
    }
}

/**
 * @return bool
 */
function is_debug()
{
    $debug = getenv("APP_DEBUG");
    return $debug === "1" || $debug === "true" || $debug === 1;
}

/**
 * @return bool
 */
function is_testing()
{
    return getenv("APP_ENV") === "testing";
}

/**
 * @return bool
 */
function is_demo()
{
    return getenv("APP_ENV") === "demo";
}

/**
 * @param mixed $value
 * @return bool
 */
function has_value($value)
{
    if (is_array($value) || is_object($value)) {
        return !!$value;
    }

    return strlen($value) > 0;
}

/**
 * @param string $text
 * @param array $data
 */
function log_info($text, array $data = [])
{
    /** @var FileLogger $logger */
    $logger = app()->make(FileLogger::class);
    $logger->info($text, $data);
}

/**
 * @param mixed $data
 * @return array
 */
function map_to_params($data)
{
    $params = [];
    $values = [];

    foreach (to_array($data) as $key => $value) {
        if ($value === null) {
            $params[] = "$key IS NULL";
        } elseif ($value instanceof Expression && my_is_integer($key)) {
            $params[] = "$value";
        } elseif ($value instanceof Expression) {
            $params[] = "$key = $value";
        } else {
            $params[] = "$key = ?";
            $values[] = $value;
        }
    }

    return [$params, $values];
}

/**
 * @param mixed $items
 * @return array
 */
function to_array($items)
{
    if ($items instanceof Traversable) {
        return iterator_to_array($items);
    }

    if (is_array($items)) {
        return $items;
    }

    if ($items === null) {
        return [];
    }

    return [$items];
}

/**
 * @param string $key
 * @param mixed ...$args
 * @return string
 */
function __($key, ...$args)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    return $translationManager->user()->t($key, ...$args);
}

/**
 * @param string $path
 * @param array $query
 * @return string
 */
function url($path, array $query = [])
{
    /** @var UrlGenerator $url */
    $url = app()->make(UrlGenerator::class);
    return $url->to($path, $query);
}

/**
 * @param string $path
 * @param array $query
 * @return string
 */
function versioned($path, $query = [])
{
    /** @var UrlGenerator $url */
    $url = app()->make(UrlGenerator::class);
    return $url->versioned($path, $query);
}

if (!function_exists("dd")) {
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            VarDumper::dump($v);
        }

        exit(1);
    }
}

/**
 * @link https://stackoverflow.com/a/2040279
 * @return string
 */
function generate_uuid4()
{
    return sprintf(
        "%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
        // 32 bits for "time_low"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

/**
 * @param int $length
 * @return string
 */
function generate_id($length)
{
    return substr(hash("sha256", generate_uuid4()), 0, $length);
}

/**
 * @param $string
 * @return string
 */
function to_upper($string)
{
    return mb_convert_case($string, MB_CASE_UPPER, "UTF-8");
}

/**
 * @param mixed $a
 * @param mixed $b
 * @return mixed
 */
function merge_recursive($a, $b)
{
    if (!is_array($a) || !is_array($b)) {
        return $b;
    }

    $output = $a;

    foreach ($b as $key => $value) {
        if (!isset($a[$key])) {
            $output[$key] = $value;
        } elseif (is_int($key)) {
            $output[] = $value;
        } else {
            $output[$key] = merge_recursive($output[$key], $value);
        }
    }

    return $output;
}

/**
 * @param int|float $a
 * @param int|float $b
 * @return float|int|null
 */
function multiply($a, $b)
{
    if ($a === null || $b === null) {
        return null;
    }

    return $a * $b;
}

/**
 * @param PaymentMethod $paymentMethod
 * @param PaymentPlatform $paymentPlatform
 * @return string
 */
function make_charge_wallet_option(PaymentMethod $paymentMethod, PaymentPlatform $paymentPlatform)
{
    return $paymentMethod . "," . $paymentPlatform->getId();
}

/**
 * @param string $list
 * @param string $delimiter
 * @return array
 */
function explode_int_list($list, $delimiter = ",")
{
    if ($list === "" || $list === null) {
        return [];
    }

    return collect(explode($delimiter, $list))
        ->map(function ($value) {
            return (int) $value;
        })
        ->all();
}

function get_authorization_value(Request $request)
{
    $authorization = $request->headers->get("Authorization");
    if (!$authorization) {
        return null;
    }

    if (0 === stripos($authorization, "bearer ")) {
        return substr($authorization, 7);
    }

    return $authorization;
}
