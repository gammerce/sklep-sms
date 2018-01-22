<?php

use App\Auth;
use App\CronExceutor;
use App\CurrentPage;
use App\Database;
use App\Heart;
use App\License;
use App\Settings;
use App\Template;
use App\TranslationManager;

foreach ($_GET as $key => $value) {
    $_GET[$key] = urldecode($value);
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

/** @var TranslationManager $translationManager */
$translationManager = $app->make(TranslationManager::class);

/** @var License $license */
$license = $app->make(License::class);

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

$lang = $translationManager->user();

if (isset($_GET['language'])) {
    $lang->setLanguage($_GET['language']);
} elseif (isset($_COOKIE['language'])) {
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

$settings->load();
$translationManager->shop()->setLanguage($settings['language']);
$license->validate();

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
