<?php

use App\Auth;
use App\CronExceutor;
use App\CurrentPage;
use App\Database;
use App\Exceptions\ShopNeedsInstallException;
use App\Heart;
use App\License;
use App\Settings;
use App\ShopState;
use App\Template;
use App\Translator;

foreach ($_GET as $key => $value) {
    $_GET[$key] = urldecode($value);
}

if (!ShopState::isInstalled() || !$app->make(ShopState::class)->isUpToDate()) {
    throw new ShopNeedsInstallException();
}

/** @var Database $db */
$db = $app->make(Database::class);

/** @var Settings $settings */
$settings = $app->make(Settings::class);

// Tworzymy obiekt posiadający mnóstwo przydatnych funkcji
/** @var Heart $heart */
$heart = $app->make(Heart::class);

/** @var Auth $auth */
$auth = $app->make(Auth::class);

// Tworzymy obiekt szablonów
/** @var Template $templates */
$templates = $app->make(Template::class);

// Tworzymy obiekt języka
/** @var Translator $lang */
$lang = $app->make(Translator::class);
/** @var Translator $lang_shop */
$lang_shop = $app->make(Translator::class);

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

// Logowanie się do panelu admina
if (admin_session()) {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $auth->loginAdminUsingCredentials($_POST['username'], $_POST['password']);
    } elseif ($_POST['action'] == "logout") {
        $auth->logoutAdmin();
    }
}

// Pozyskujemy dane gracza, jeżeli jeszcze ich nie ma
if (!$auth->check() && isset($_SESSION['uid'])) {
    $auth->loginUserUsingId($_SESSION['uid']);
}

// Jeżeli próbujemy wejść do PA i nie jesteśmy zalogowani, to zmień stronę
if (admin_session() && (!$auth->check() || !get_privilages("acp"))) {
    /** @var CurrentPage $currentPage */
    $currentPage = $app->make(CurrentPage::class);
    $currentPage->setPid('login');

    // Jeżeli jest zalogowany, ale w międzyczasie odebrano mu dostęp do PA
    if ($auth->check()) {
        $_SESSION['info'] = "no_privilages";
    }
}

// Aktualizujemy aktywność użytkownika
$user = $auth->user();
$user->setLastip(get_ip());
$user->updateActivity();

$settings->load();

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

$license = $app->make(License::class);

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
    /** @var CronExceutor $cronExecutor */
    $cronExecutor = $app->make(CronExceutor::class);
    $cronExecutor->run();
}
