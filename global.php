<?php

if (!defined("IN_SCRIPT"))
	die("Nie ma tu nic ciekawego.");

//error_reporting(E_USER_ERROR);
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Tworzenie / Wznawianie sesji
if (in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin"))) {
	session_name("admin");
	session_start();
} else {
	session_name("user");
	session_start();
}

$working_dir = dirname(__FILE__) ? dirname(__FILE__) : '.';
require_once $working_dir . "/includes/init.php";

// Przenieś do folderu install, jeżeli istnieje
if (file_exists(SCRIPT_ROOT . "install")) {
	header("Location: install");
	exit;
}

$settings['date_format'] = "Y-m-d H:i";
$settings['theme'] = "default";

require_once SCRIPT_ROOT . "includes/config.php";
require_once SCRIPT_ROOT . "includes/functions.php";
require_once SCRIPT_ROOT . "includes/class_heart.php";
require_once SCRIPT_ROOT . "includes/mysqli.php";
require_once SCRIPT_ROOT . "includes/class_payment.php";
require_once SCRIPT_ROOT . "includes/class_language.php";

// Tworzymy obiekt posiadający mnóstwo przydatnych funkcji
$heart = new Heart();

// Tworzymy obiekt języka
$lang = array();
$language = new Language();

// Ustalenie funkcji obsługującej errory
set_error_handler("myErrorHandler");

// Utworzenie połączenia z bazą danych
$db = new Database($db_host, $db_user, $db_pass, $db_name);
$db->query("SET NAMES utf8");

// Dodajemy klasy wszystkich modulow platnosci
require_once SCRIPT_ROOT . "includes/verification/payment_module.php";
require_once SCRIPT_ROOT . "includes/verification/payment_sms.php";
require_once SCRIPT_ROOT . "includes/verification/payment_transfer.php";
foreach (scandir(SCRIPT_ROOT . "includes/verification") as $file)
	if (substr($file, -4) == ".php")
		require_once SCRIPT_ROOT . "includes/verification/" . $file;

// Dodajemy klasy wszystkich usług
require_once SCRIPT_ROOT . "includes/services/service.php";
require_once SCRIPT_ROOT . "includes/services/service_purchase.php";
require_once SCRIPT_ROOT . "includes/services/service_purchase_web.php";
require_once SCRIPT_ROOT . "includes/services/service_admin_manage_user_service.php";
require_once SCRIPT_ROOT . "includes/services/service_user_edit.php";
require_once SCRIPT_ROOT . "includes/services/service_execute_action.php";
require_once SCRIPT_ROOT . "includes/services/service_create_new.php";
require_once SCRIPT_ROOT . "includes/services/service_take_over.php";
require_once SCRIPT_ROOT . "includes/services/service_must_be_logged.php";
foreach (scandir(SCRIPT_ROOT . "includes/services") as $file)
	if (substr($file, -4) == ".php")
		require_once SCRIPT_ROOT . "includes/services/" . $file;

// Dodajemy klasy wszystkich bloków
require_once SCRIPT_ROOT . "includes/blocks/block.php";
foreach (scandir(SCRIPT_ROOT . "includes/blocks") as $file)
	if (substr($file, -4) == ".php")
		require_once SCRIPT_ROOT . "includes/blocks/" . $file;

// Dodajemy klasy wszystkich stron
require_once SCRIPT_ROOT . "includes/pages/page.php";
require_once SCRIPT_ROOT . "includes/pages/pageadmin.php";
foreach (scandir(SCRIPT_ROOT . "includes/pages") as $file)
	if (substr($file, -4) == ".php")
		require_once SCRIPT_ROOT . "includes/pages/" . $file;

// Pobieramy id strony oraz obecna numer strony
$G_PID = isset($_GET['pid']) ? $_GET['pid'] : "main_content";
$G_PAGE = isset($_GET['page']) && intval($_GET['page']) >= 1 ? intval($_GET['page']) : 1;

// Logowanie się do panelu admina
if (in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin"))) {
	if (isset($_POST['username']) && isset($_POST['password'])) { // Logujemy się
		$user = $heart->get_user(0, $_POST['username'], $_POST['password']);
		if (is_logged() && get_privilages("acp"))
			$_SESSION['uid'] = $user['uid'];
		else {
			$_SESSION['info'] = "wrong_data";
			$user = array();
		}
	} else if ($_POST['action'] == "logout") { // Wylogowujemy
		// Unset all of the session variables.
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
		}

		// Finally, destroy the session.
		session_destroy();
	}
}

// Pobieramy dane gracza, jeżeli jeszcze ich nie ma
if (!isset($user) && isset($_SESSION['uid']))
	$user = $heart->get_user($_SESSION['uid']);

// Jeżeli próbujemy wejść do PA i nie jesteśmy zalogowani, to zmień stronę
if (in_array(SCRIPT_NAME, array("admin", "jsonhttp_admin")) && (!is_logged() || !get_privilages("acp"))) {
	$G_PID = "login";

	// Jeżeli jest zalogowany, ale w międzyczasie odebrano mu dostęp do PA
	if (is_logged()) {
		$_SESSION['info'] = "no_privilages";
		$user = array();
	}
}

// Pobieramy dane pustego użytkownika / gościa
if (!isset($user) || empty($user))
	$user = $heart->get_user(0);

// Aktualizujemy aktywność użytkownika
if (is_logged())
	update_activity($user['uid']);

// Pobranie stałych
$result = $db->query("SELECT * FROM `" . TABLE_PREFIX . "settings`");
while ($row = $db->fetch_array_assoc($result))
	$settings[$row['key']] = $row['value'];

// Poprawiamy adres URL sklepu
if ($settings['shop_url']) {
	if (strpos($settings['shop_url'], "http://") !== 0 && strpos($settings['shop_url'], "https://") !== 0)
		$settings['shop_url'] = "http://" . $settings['shop_url'];
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
CONCAT_WS('', pa.ip, ps.ip, pt.ip, pw.ip) AS `ip`,
CONCAT_WS('', pa.platform, ps.platform, pt.platform, pw.platform) AS `platform`,
CONCAT_WS('', ps.income, pt.income) AS `income`,
CONCAT_WS('', ps.cost, pt.income, pw.cost) AS `cost`,
pa.aid AS `aid`,
ps.code AS `sms_code`,
ps.text AS `sms_text`,
ps.number AS `sms_number`,
IFNULL(ps.free,0) AS `free`,
bs.timestamp AS `timestamp`
FROM `" . TABLE_PREFIX . "bought_services` AS bs
LEFT JOIN `" . TABLE_PREFIX . "users` AS u ON u.uid = bs.uid
LEFT JOIN `" . TABLE_PREFIX . "payment_admin` AS pa ON bs.payment = 'admin' AND pa.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_sms` AS ps ON bs.payment = 'sms' AND ps.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_transfer` AS pt ON bs.payment = 'transfer' AND pt.id = bs.payment_id
LEFT JOIN `" . TABLE_PREFIX . "payment_wallet` AS pw ON bs.payment = 'wallet' AND pw.id = bs.payment_id)";

// Ustawianie strefy
if ($settings['timezone'])
	date_default_timezone_set($settings['timezone']);

$settings['date_format'] = strlen($settings['date_format']) ? $settings['date_format'] : "Y-m-d H:i";

// Sprawdzanie czy taki szablon istnieje, jak nie to ustaw defaultowy
$settings['theme'] = file_exists(SCRIPT_ROOT . "themes/{$settings['theme']}") ? $settings['theme'] : "default";

// Dodawanie biblioteki językowej
$settings['language'] = file_exists(SCRIPT_ROOT . "includes/languages/{$settings['language']}/{$settings['language']}.php") ? $settings['language'] : "polish";

// Ładujemy bibliotekę językową
if (isset($_SESSION['language']))
	$language->set_language($_SESSION['language']);
else if (isset($_GET['language']))
	$language->set_language($_GET['language']);
else
	$language->set_language($settings['language']);

$a_Tasks = json_decode(curl_get_contents("http://license.sklep-sms.pl/license.php?action=login_web" . "&lid=" . urlencode($settings['license_login']) . "&lpa=" . urlencode($settings['license_password']) .
	"&name=" . urlencode($settings['shop_url']) . "&version=" . VERSION), true);

// Brak tekstu, wywalamy błąd
if (!isset($a_Tasks['text']))
	output_page($lang['verification_error']);

if ($a_Tasks['expire']) {
	if ($a_Tasks['expire'] == '-1')
		$a_Tasks['expire'] = $lang['never'];
	else
		$a_Tasks['expire'] = date($settings['date_format'], $a_Tasks['expire']);
}

if ($a_Tasks['text'] != "logged_in") {
	if (get_privilages("manage_settings", $user))
		$user['privilages'] = array(
			"acp" => true,
			"manage_settings" => true
		);

	if (SCRIPT_NAME == "index")
		output_page($a_Tasks['page']);
	else if (in_array(SCRIPT_NAME, array("jsonhttp", "servers_stuff", "extra_stuff")))
		exit;
}

// Cron co wizytę
if ($settings['cron_each_visit'] && SCRIPT_NAME != "cron")
	include(SCRIPT_ROOT . "cron.php");

define('TYPE_NICK', 1 << 0);
define('TYPE_IP', 1 << 1);
define('TYPE_SID', 1 << 2);