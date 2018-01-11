<?php

if (!defined("IN_SCRIPT")) {
    die('There is nothing interesting here.');
}

error_reporting(E_ERROR | E_CORE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR);
ini_set('display_errors', 1);

foreach ($_GET as $key => $value) {
    $_GET[$key] = urldecode($value);
}

// Tworzenie / Wznawianie sesji
if (in_array(SCRIPT_NAME, ['admin', 'jsonhttp_admin'])) {
    session_name('admin');
    session_start();
} else {
    session_name('user');
    session_start();
}

$working_dir = dirname(__FILE__) ? dirname(__FILE__) : '.';
require_once $working_dir . '/includes/init.php';

$settings = [
    'date_format'    => 'Y-m-d H:i',
    'theme'          => 'default',
    'shop_url'       => '',
    'shop_url_slash' => '',
];

require_once SCRIPT_ROOT . "includes/mysqli.php";
require_once SCRIPT_ROOT . "includes/ShopState.php";

$db = DBInstance::get();

if (!ShopState::isInstalled() || !(new ShopState($db))->isUpToDate()) {
    header('Location: install');
    exit;
}

require_once SCRIPT_ROOT . "includes/License.php";
require_once SCRIPT_ROOT . "includes/exceptions/LicenseException.php";
require_once SCRIPT_ROOT . "includes/class_template.php";
require_once SCRIPT_ROOT . "includes/functions.php";
require_once SCRIPT_ROOT . "includes/class_heart.php";
require_once SCRIPT_ROOT . "includes/class_payment.php";
require_once SCRIPT_ROOT . "includes/class_translator.php";

set_exception_handler("exceptionHandler");

// Tworzymy obiekt posiadający mnóstwo przydatnych funkcji
$heart = new Heart();

// Tworzymy obiekt szablonów
$templates = new Templates();

// Tworzymy obiekt języka
$lang = new Translator();
$lang_shop = new Translator();

// Te interfejsy są potrzebne do klas różnego rodzajów
foreach (scandir(SCRIPT_ROOT . "includes/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/interfaces/" . $file;
    }
}

// Dodajemy klasy wszystkich modulow platnosci
require_once SCRIPT_ROOT . "includes/PaymentModule.php";
foreach (scandir(SCRIPT_ROOT . "includes/verification/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/verification/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/verification") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/verification/" . $file;
    }
}


// Dodajemy klasy wszystkich usług
require_once SCRIPT_ROOT . "includes/services/service.php";

// Pierwsze ładujemy interfejsy
foreach (scandir(SCRIPT_ROOT . "includes/services/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/services/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/services") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/services/" . $file;
    }
}


// Dodajemy klasy wszystkich bloków
require_once SCRIPT_ROOT . "includes/blocks/block.php";
foreach (scandir(SCRIPT_ROOT . "includes/blocks") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/blocks/" . $file;
    }
}


// Dodajemy klasy wszystkich stron
require_once SCRIPT_ROOT . "includes/pages/page.php";
require_once SCRIPT_ROOT . "includes/pages/pageadmin.php";

// Pierwsze ładujemy interfejsy
foreach (scandir(SCRIPT_ROOT . "includes/pages/interfaces") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/pages/interfaces/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/pages") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/pages/" . $file;
    }
}

foreach (scandir(SCRIPT_ROOT . "includes/entity") as $file) {
    if (ends_at($file, ".php")) {
        require_once SCRIPT_ROOT . "includes/entity/" . $file;
    }
}


// Pobieramy id strony oraz obecna numer strony
$G_PID = isset($_GET['pid']) ? $_GET['pid'] : "home";
$G_PAGE = isset($_GET['page']) && intval($_GET['page']) >= 1 ? intval($_GET['page']) : 1;

$user = $heart->get_user();

// Logowanie się do panelu admina
if (admin_session()) {
    // Logujemy się
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $user = $heart->get_user(0, $_POST['username'], $_POST['password']);

        if ($user->isLogged() && get_privilages("acp")) {
            $_SESSION['uid'] = $user->getUid();
        } else {
            $_SESSION['info'] = "wrong_data";
        }
    } // Wylogowujemy
    else {
        if ($_POST['action'] == "logout") {
            // Unset all of the session variables.
            $_SESSION = [];

            // If it's desired to kill the session, also delete the session cookie.
            // Note: This will destroy the session, and not just the session data!
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"],
                    $params["httponly"]);
            }

            // Finally, destroy the session.
            session_destroy();
        }
    }
}

// Pozyskujemy dane gracza, jeżeli jeszcze ich nie ma
if (!$user->isLogged() && isset($_SESSION['uid'])) {
    $user = $heart->get_user($_SESSION['uid']);
}

// Jeżeli próbujemy wejść do PA i nie jesteśmy zalogowani, to zmień stronę
if (admin_session() && (!$user->isLogged() || !get_privilages("acp"))) {
    $G_PID = "login";

    // Jeżeli jest zalogowany, ale w międzyczasie odebrano mu dostęp do PA
    if ($user->isLogged()) {
        $_SESSION['info'] = "no_privilages";
        $user = $heart->get_user();
    }
}

// Aktualizujemy aktywność użytkownika
$user->setLastip(get_ip());
$user->updateActivity();

// Pozyskanie ustawień sklepu
$result = $db->query("SELECT * FROM `" . TABLE_PREFIX . "settings`");
while ($row = $db->fetch_array_assoc($result)) {
    $settings[$row['key']] = $row['value'];
}

// Poprawiamy adres URL sklepu
if (strlen($settings['shop_url'])) {
    if (strpos($settings['shop_url'], "http://") !== 0 && strpos($settings['shop_url'], "https://") !== 0) {
        $settings['shop_url'] = "http://" . $settings['shop_url'];
    }

    $settings['shop_url'] = rtrim($settings['shop_url'], "/");
    $settings['shop_url_slash'] = $settings['shop_url'] . "/";
}

$settings['currency'] = htmlspecialchars($settings['currency']);
$settings['transactions_query'] = "(SELECT bs.id AS `id`,
bs.uid AS `uid`,
u.username AS `username`,
bs.payment AS `payment`,
bs.payment_id AS `payment_id`,
bs.service AS `service`,
bs.server AS `server`,
bs.amount AS `amount`,
bs.auth_data AS `auth_data`,
bs.email AS `email`,
bs.extra_data AS `extra_data`,
CONCAT_WS('', pa.ip, ps.ip, pt.ip, pw.ip, pc.ip) AS `ip`,
CONCAT_WS('', pa.platform, ps.platform, pt.platform, pw.platform, pc.platform) AS `platform`,
CONCAT_WS('', ps.income, pt.income) AS `income`,
CONCAT_WS('', ps.cost, pt.income, pw.cost) AS `cost`,
pa.aid AS `aid`,
u2.username AS `adminname`,
ps.code AS `sms_code`,
ps.text AS `sms_text`,
ps.number AS `sms_number`,
IFNULL(ps.free,0) AS `free`,
pc.code AS `service_code`,
bs.timestamp AS `timestamp`
FROM `" . TABLE_PREFIX . "bought_services` AS bs
LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = bs.uid
LEFT JOIN `" . TABLE_PREFIX . "payment_admin` AS pa ON bs.payment = 'admin' AND pa.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "users` AS u2 ON u2.uid = pa.aid
LEFT JOIN `" . TABLE_PREFIX . "payment_sms` AS ps ON bs.payment = 'sms' AND ps.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_transfer` AS pt ON bs.payment = 'transfer' AND pt.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_wallet` AS pw ON bs.payment = 'wallet' AND pw.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_code` AS pc ON bs.payment = 'service_code' AND pc.id = bs.payment_id)";

// Ustawianie strefy
if ($settings['timezone']) {
    date_default_timezone_set($settings['timezone']);
}

$settings['date_format'] = strlen($settings['date_format']) ? $settings['date_format'] : "Y-m-d H:i";

// Sprawdzanie czy taki szablon istnieje, jak nie to ustaw defaultowy
$settings['theme'] = file_exists(SCRIPT_ROOT . "themes/{$settings['theme']}") ? $settings['theme'] : "default";

// Ładujemy bibliotekę językową
if (isset($_GET['language'])) {
    $lang->setLanguage($_GET['language']);
} else {
    if (isset($_COOKIE['language'])) {
        $lang->setLanguage($_COOKIE['language']);
    } else {
        $details = json_decode(file_get_contents("http://ipinfo.io/" . get_ip() . "/json"));
        if (isset($details->country) && strlen($temp_lang = $lang->getLanguageByShort($details->country))) {
            $lang->setLanguage($temp_lang);
            unset($temp_lang);
        } else {
            $lang->setLanguage($settings['language']);
        }
    }
}
$lang_shop->setLanguage($settings['language']);

$license = new License($lang, $settings);

if (!$license->isValid()) {
    if (get_privilages("manage_settings")) {
        $user->removePrivilages();
        $user->setPrivilages([
            "acp"             => true,
            "manage_settings" => true,
        ]);
    }

    if (SCRIPT_NAME == "index") {
        output_page($license->getPage());
    }

    if (in_array(SCRIPT_NAME, ["jsonhttp", "servers_stuff", "extra_stuff"])) {
        exit;
    }
}

// Cron co wizytę
if ($settings['cron_each_visit'] && SCRIPT_NAME != "cron") {
    include(SCRIPT_ROOT . "cron.php");
}

define('TYPE_NICK', 1 << 0);
define('TYPE_IP', 1 << 1);
define('TYPE_SID', 1 << 2);