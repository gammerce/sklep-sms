<?php
namespace App\Kernels;

use App\Auth;
use App\Database;
use App\Heart;
use App\Mailer;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use App\Middlewares\UpdateUserActivity;
use App\Models\Purchase;
use App\Payment;
use App\Settings;
use App\Template;
use App\TranslationManager;
use PageAdminIncome;
use Symfony\Component\HttpFoundation\Request;

class JsonHttpKernel extends Kernel
{
    protected $middlewares = [
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
        UpdateUserActivity::class,
    ];

    public function run(Request $request)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $langShop = $translationManager->shop();

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var Auth $auth */
        $auth = $this->app->make(Auth::class);
        $user = $auth->user();

        /** @var Template $templates */
        $templates = $this->app->make(Template::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var Database $db */
        $db = $this->app->make(Database::class);

        /** @var Mailer $mailer */
        $mailer = app()->make(Mailer::class);

        // Pobranie akcji
        $action = $_POST['action'];

        $warnings = [];

        // Send no cache headers
        header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");

        $data = [];
        if ($action == "login") {
            if (is_logged()) {
                json_output("already_logged_in");
            }

            if (!$_POST['username'] || !$_POST['password']) {
                json_output("no_data", $lang->translate('no_login_password'), 0);
            }

            $user = $heart->get_user(0, $_POST['username'], $_POST['password']);
            if ($user->isLogged()) {
                $_SESSION['uid'] = $user->getUid();
                $user->updateActivity();
                json_output("logged_in", $lang->translate('login_success'), 1);
            }

            json_output("not_logged", $lang->translate('bad_pass_nick'), 0);
        } elseif ($action == "logout") {
            if (!is_logged()) {
                json_output("already_logged_out");
            }

            // Unset all of the session variables.
            $_SESSION = [];

            // If it's desired to kill the session, also delete the session cookie.
            // Note: This will destroy the session, and not just the session data!
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

            // Finally, destroy the session.
            session_destroy();

            json_output("logged_out", $lang->translate('logout_success'), 1);
        } elseif ($action == "set_session_language") {
            setcookie("language", escape_filename($_POST['language']), time() + (86400 * 30), "/"); // 86400 = 1 day
            exit;
        } elseif ($action == "register") {
            if (is_logged()) {
                json_output("logged_in", $lang->translate('logged'), 0);
            }

            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $passwordr = $_POST['password_repeat'];
            $email = trim($_POST['email']);
            $emailr = trim($_POST['email_repeat']);
            $forename = trim($_POST['forename']);
            $surname = trim($_POST['surname']);
            $as_id = $_POST['as_id'];
            $as_answer = $_POST['as_answer'];

            // Pobranie nowego pytania antyspamowego
            $antispam_question = $db->fetch_array_assoc($db->query(
                "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
                "ORDER BY RAND() " .
                "LIMIT 1"
            ));
            $data['antispam']['question'] = $antispam_question['question'];
            $data['antispam']['id'] = $antispam_question['id'];

            // Sprawdzanie czy podane id pytania antyspamowego jest prawidlowe
            if (!isset($_SESSION['asid']) || $as_id != $_SESSION['asid']) {
                json_output("wrong_sign", $lang->translate('wrong_sign'), 0, $data);
            }

            // Zapisujemy id pytania antyspamowego
            $_SESSION['asid'] = $antispam_question['id'];

            // Nazwa użytkownika
            if ($warning = check_for_warnings("username", $username)) {
                $warnings['username'] = array_merge((array)$warnings['username'], $warning);
            }

            $result = $db->query($db->prepare(
                "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
                "WHERE `username` = '%s'",
                [$username]
            ));
            if ($db->num_rows($result)) {
                $warnings['username'][] = $lang->translate('nick_occupied');
            }

            // Hasło
            if ($warning = check_for_warnings("password", $password)) {
                $warnings['password'] = array_merge((array)$warnings['password'], $warning);
            }
            if ($password != $passwordr) {
                $warnings['password_repeat'][] = $lang->translate('different_pass');
            }

            if ($warning = check_for_warnings("email", $email)) {
                $warnings['email'] = array_merge((array)$warnings['email'], $warning);
            }

            // Email
            $result = $db->query($db->prepare(
                "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
                "WHERE `email` = '%s'",
                [$email]
            ));
            if ($db->num_rows($result)) {
                $warnings['email'][] = $lang->translate('email_occupied');
            }

            if ($email != $emailr) {
                $warnings['email_repeat'][] = $lang->translate('different_email');
            }

            // Pobranie z bazy pytania antyspamowego
            $result = $db->query($db->prepare(
                "SELECT * FROM `" . TABLE_PREFIX . "antispam_questions` " .
                "WHERE `id` = '%d'",
                [$as_id]
            ));
            $antispam_question = $db->fetch_array_assoc($result);
            if (!in_array(strtolower($as_answer), explode(";", $antispam_question['answers']))) {
                $warnings['as_answer'][] = $lang->translate('wrong_anti_answer');
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                json_output("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            $salt = get_random_string(8);
            $db->query($db->prepare(
                "INSERT INTO `" . TABLE_PREFIX . "users` (`username`, `password`, `salt`, `email`, `forename`, `surname`, `regip`) " .
                "VALUES ('%s','%s','%s','%s','%s','%s','%s')",
                [$username, hash_password($password, $salt), $salt, $email, $forename, $surname, $user->getLastIp()]
            ));

            // LOGING
            log_info($langShop->sprintf($langShop->translate('new_account'), $db->last_id(), $username,
                $user->getLastIp()));

            json_output("registered", $lang->translate('register_success'), 1, $data);
        } elseif ($action == "forgotten_password") {
            if (is_logged()) {
                json_output("logged_in", $lang->translate('logged'), 0);
            }

            $username = trim($_POST['username']);
            $email = trim($_POST['email']);

            if ($username || (!$username && !$email)) {
                if ($warning = check_for_warnings("username", $username)) {
                    $warnings['username'] = array_merge((array)$warnings['username'], $warning);
                }
                if (strlen($username)) {
                    $result = $db->query($db->prepare(
                        "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
                        "WHERE `username` = '%s'",
                        [$username]
                    ));

                    if (!$db->num_rows($result)) {
                        $warnings['username'][] = $lang->translate('nick_no_account');
                    } else {
                        $row = $db->fetch_array_assoc($result);
                    }
                }
            }

            if (!strlen($username)) {
                if ($warning = check_for_warnings("email", $email)) {
                    $warnings['email'] = array_merge((array)$warnings['email'], $warning);
                }
                if (strlen($email)) {
                    $result = $db->query($db->prepare(
                        "SELECT `uid` FROM `" . TABLE_PREFIX . "users` " .
                        "WHERE `email` = '%s'",
                        [$email]
                    ));

                    if (!$db->num_rows($result)) {
                        $warnings['email'][] = $lang->translate('email_no_account');
                    } else {
                        $row = $db->fetch_array_assoc($result);
                    }
                }
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                json_output("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            // Pobranie danych użytkownika
            $user2 = $heart->get_user($row['uid']);

            $key = get_random_string(32);
            $db->query($db->prepare(
                "UPDATE `" . TABLE_PREFIX . "users` " .
                "SET `reset_password_key`='%s' " .
                "WHERE `uid`='%d'",
                [$key, $user2->getUid()]
            ));

            $link = $settings['shop_url_slash'] . "index.php?pid=reset_password&code=" . htmlspecialchars($key);
            $text = $templates->render("emails/forgotten_password", compact('user2', 'link'));
            $ret = $mailer->send($user2->getEmail(), $user2->getUsername(), "Reset Hasła", $text);

            if ($ret == "not_sent") {
                json_output("not_sent", $lang->translate('keyreset_error'), 0);
            } elseif ($ret == "wrong_email") {
                json_output("wrong_sender_email", $lang->translate('wrong_email'), 0);
            } elseif ($ret == "sent") {
                log_info($langShop->sprintf($langShop->translate('reset_key_email'), $user2->getUsername(),
                    $user2->getUid(), $user2->getEmail(), $username, $email));
                $data['username'] = $user2->getUsername();
                json_output("sent", $lang->translate('email_sent'), 1, $data);
            }
        } elseif ($action == "reset_password") {
            if (is_logged()) {
                json_output("logged_in", $lang->translate('logged'), 0);
            }

            $uid = $_POST['uid'];
            $sign = $_POST['sign'];
            $pass = $_POST['pass'];
            $passr = $_POST['pass_repeat'];

            // Sprawdzanie hashu najwazniejszych danych
            if (!$sign || $sign != md5($uid . $settings['random_key'])) {
                json_output("wrong_sign", $lang->translate('wrong_sign'), 0);
            }

            if ($warning = check_for_warnings("password", $pass)) {
                $warnings['pass'] = array_merge((array)$warnings['pass'], $warning);
            }
            if ($pass != $passr) {
                $warnings['pass_repeat'][] = $lang->translate('different_pass');
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                json_output("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            // Zmień hasło
            $salt = get_random_string(8);

            $db->query($db->prepare(
                "UPDATE `" . TABLE_PREFIX . "users` " .
                "SET `password` = '%s', `salt` = '%s', `reset_password_key` = '' " .
                "WHERE `uid` = '%d'",
                [hash_password($pass, $salt), $salt, $uid]
            ));

            // LOGING
            log_info($langShop->sprintf($langShop->translate('reset_pass'), $uid));

            json_output("password_changed", $lang->translate('password_changed'), 1);
        } elseif ($action == "change_password") {
            if (!is_logged()) {
                json_output("logged_in", $lang->translate('not_logged'), 0);
            }

            $oldpass = $_POST['old_pass'];
            $pass = $_POST['pass'];
            $passr = $_POST['pass_repeat'];

            if ($warning = check_for_warnings("password", $pass)) {
                $warnings['pass'] = array_merge((array)$warnings['pass'], $warning);
            }
            if ($pass != $passr) {
                $warnings['pass_repeat'][] = $lang->translate('different_pass');
            }

            if (hash_password($oldpass, $user->getSalt()) != $user->getPassword()) {
                $warnings['old_pass'][] = $lang->translate('old_pass_wrong');
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                json_output("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }
            // Zmień hasło
            $salt = get_random_string(8);

            $db->query($db->prepare(
                "UPDATE `" . TABLE_PREFIX . "users` " .
                "SET password='%s', salt='%s'" .
                "WHERE uid='%d'",
                [hash_password($pass, $salt), $salt, $user->getUid()]
            ));

            // LOGING
            log_info("Zmieniono hasło. ID użytkownika: {$user->getUid()}.");

            json_output("password_changed", $lang->translate('password_changed'), 1);
        } elseif ($action == "purchase_form_validate") {
            if (($service_module = $heart->get_service_module($_POST['service'])) === null
                || !object_implements($service_module, "IService_PurchaseWeb")
            ) {
                json_output("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
            if (!$heart->user_can_use_service($user->getUid(), $service_module->service)) {
                json_output("no_permission", $lang->translate('service_no_permission'), 0);
            }

            // Przeprowadzamy walidację danych wprowadzonych w formularzu
            $return_data = $service_module->purchase_form_validate($_POST);

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($return_data['status'] == "warnings") {
                foreach ($return_data['data']['warnings'] as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $return_data['data']['warnings'][$brick] = $warning;
                }
            } else {
                //
                // Uzupełniamy brakujące dane
                /** @var Purchase $purchase_data */
                $purchase_data = $return_data['purchase_data'];

                if ($purchase_data->getService() === null) {
                    $purchase_data->setService($service_module->service['id']);
                }

                if (!$purchase_data->getPayment('cost') && $purchase_data->getTariff() !== null) {
                    $purchase_data->setPayment([
                        'cost' => $purchase_data->getTariff()->getProvision(),
                    ]);
                }

                if ($purchase_data->getPayment('sms_service') === null && !$purchase_data->getPayment("no_sms") && strlen($settings['sms_service'])) {
                    $purchase_data->setPayment([
                        'sms_service' => $settings['sms_service'],
                    ]);
                }

                // Ustawiamy taryfe z numerem
                if ($purchase_data->getPayment('sms_service') !== null) {
                    $payment = new Payment($purchase_data->getPayment('sms_service'));
                    $purchase_data->setTariff($payment->getPaymentModule()->getTariffById($purchase_data->getTariff()->getId()));
                }

                if ($purchase_data->getEmail() === null && strlen($user->getEmail())) {
                    $purchase_data->setEmail($user->getEmail());
                }

                $purchase_data_encoded = base64_encode(serialize($purchase_data));
                $return_data['data'] = [
                    'length' => 8000,
                    'data'   => $purchase_data_encoded,
                    'sign'   => md5($purchase_data_encoded . $settings['random_key']),
                ];
            }

            json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
        } elseif ($action == "payment_form_validate") {
            // Sprawdzanie hashu danych przesłanych przez formularz
            if (!isset($_POST['purchase_sign']) || $_POST['purchase_sign'] != md5($_POST['purchase_data'] . $settings['random_key'])) {
                json_output("wrong_sign", $lang->translate('wrong_sign'), 0);
            }

            /** @var Purchase $purchase_data */
            $purchase_data = unserialize(base64_decode($_POST['purchase_data']));

            // Fix: get user data again to avoid bugs linked with user wallet
            $purchase_data->user = $heart->get_user($purchase_data->user->getUid());

            // Dodajemy dane płatności
            $purchase_data->setPayment([
                'method'       => $_POST['method'],
                'sms_code'     => $_POST['sms_code'],
                'service_code' => $_POST['service_code'],
            ]);

            $return_payment = validate_payment($purchase_data);
            json_output(
                $return_payment['status'], $return_payment['text'], $return_payment['positive'], $return_payment['data']
            );
        } elseif ($action == "refresh_blocks") {
            $data = [];
            if (isset($_POST['bricks'])) {
                $bricks = explode(";", $_POST['bricks']);

                foreach ($bricks as $brick) {
                    // Nie ma takiego bloku do odświeżenia
                    if (($block = $heart->get_block($brick)) === null) {
                        continue;
                    }

                    $data[$block->get_content_id()]['content'] = $block->get_content($_GET, $_POST);
                    if ($data[$block->get_content_id()]['content'] !== null) {
                        $data[$block->get_content_id()]['class'] = $block->get_content_class();
                    } else {
                        $data[$block->get_content_id()]['class'] = "";
                    }
                }
            }

            output_page(json_encode($data), 1);
        } elseif ($action == "get_service_long_description") {
            $output = "";
            if (($service_module = $heart->get_service_module($_POST['service'])) !== null) {
                $output = $service_module->description_full_get();
            }

            output_page($output, 1);
        } elseif ($action == "get_purchase_info") {
            output_page(purchase_info([
                'purchase_id' => $_POST['purchase_id'],
                'action'      => "web",
            ]), 1);
        } elseif ($action == "form_user_service_edit") {
            if (!is_logged()) {
                output_page($lang->translate('service_cant_be_modified'));
            }

            // Użytkownik nie może edytować usługi
            if (!$settings['user_edit_service']) {
                output_page($lang->translate('not_logged'));
            }

            $user_service = get_users_services($_POST['id']);

            if (empty($user_service)) {
                output_page($lang->translate('dont_play_games'));
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($user_service['uid'] != $user->getUid()) {
                output_page($lang->translate('dont_play_games'));
            }

            if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
                output_page($lang->translate('service_cant_be_modified'));
            }

            if (!$settings['user_edit_service'] || !object_implements($service_module,
                    "IService_UserOwnServicesEdit")) {
                output_page($lang->translate('service_cant_be_modified'));
            }

            $buttons = $templates->render("services/my_services_savencancel");

            output_page($buttons . $service_module->user_own_service_edit_form_get($user_service));
        } elseif ($action == "get_user_service_brick") {
            if (!is_logged()) {
                output_page($lang->translate('not_logged'));
            }

            $user_service = get_users_services($_POST['id']);

            // Brak takiej usługi w bazie
            if (empty($user_service)) {
                output_page($lang->translate('dont_play_games'));
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($user_service['uid'] != $user->getUid()) {
                output_page($lang->translate('dont_play_games'));
            }

            if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
                output_page($lang->translate('service_not_displayed'));
            }

            if (!object_implements($service_module, "IService_UserOwnServices")) {
                output_page($lang->translate('service_not_displayed'));
            }

            if ($settings['user_edit_service'] && object_implements($service_module, "IService_UserOwnServicesEdit")) {
                $button_edit = create_dom_element("button", $lang->translate('edit'), [
                    'class' => "button edit_row",
                    'type'  => 'button',
                ]);
            }

            output_page($service_module->user_own_service_info_get($user_service, $button_edit));
        } elseif ($action == "user_service_edit") {
            if (!is_logged()) {
                json_output("not_logged", $lang->translate('not_logged'), 0);
            }

            $user_service = get_users_services($_POST['id']);

            // Brak takiej usługi w bazie
            if (empty($user_service)) {
                json_output("dont_play_games", $lang->translate('dont_play_games'), 0);
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($user_service['uid'] != $user->getUid()) {
                json_output("dont_play_games", $lang->translate('dont_play_games'), 0);
            }

            if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
                json_output("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Wykonujemy metode edycji usługi użytkownika na module, który ją obsługuje
            if (!$settings['user_edit_service'] || !object_implements($service_module,
                    "IService_UserOwnServicesEdit")) {
                json_output("service_cant_be_modified", $lang->translate('service_cant_be_modified'), 0);
            }

            $return_data = $service_module->user_own_service_edit($_POST, $user_service);

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($return_data['status'] == "warnings") {
                foreach ($return_data['data']['warnings'] as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $return_data['data']['warnings'][$brick] = $warning;
                }
            }

            json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
        } elseif ($action == "service_take_over_form_get") {
            if (($service_module = $heart->get_service_module($_POST['service'])) === null || !object_implements($service_module,
                    "IService_TakeOver")) {
                output_page($lang->translate('bad_module'), 1);
            }

            output_page($service_module->service_take_over_form_get(), 1);
        } elseif ($action == "service_take_over") {
            if (($service_module = $heart->get_service_module($_POST['service'])) === null || !object_implements($service_module,
                    "IService_TakeOver")) {
                output_page($lang->translate('bad_module'), 1);
            }

            $return_data = $service_module->service_take_over($_POST);

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($return_data['status'] == "warnings") {
                foreach ($return_data['data']['warnings'] as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $return_data['data']['warnings'][$brick] = $warning;
                }
            }

            json_output($return_data['status'], $return_data['text'], $return_data['positive'], $return_data['data']);
        } elseif ($_GET['action'] == "get_income") {
            $user->setPrivilages([
                'acp'         => true,
                'view_income' => true,
            ]);
            $page = new PageAdminIncome();

            output_page($page->get_content($_GET, $_POST), 1);
        } elseif ($action == "service_action_execute") {
            if (($service_module = $heart->get_service_module($_POST['service'])) === null
                || !object_implements($service_module, "IService_ActionExecute")
            ) {
                output_page($lang->translate('bad_module'), 1);
            }

            output_page($service_module->action_execute($_POST['service_action'], $_POST), 1);
        } elseif ($action == "get_template") {
            $template = $_POST['template'];
            // Zabezpieczanie wszystkich wartości post
            foreach ($_POST as $key => $value) {
                $_POST[$key] = htmlspecialchars($value);
            }

            if ($template == "register_registered") {
                $username = htmlspecialchars($_POST['username']);
                $email = htmlspecialchars($_POST['email']);
            } elseif ($template == "forgotten_password_sent") {
                $username = htmlspecialchars($_POST['username']);
            }

            if (!isset($data['template'])) {
                $data['template'] = $templates->render("jsonhttp/" . $template, compact('username', 'email'));
            }

            output_page(json_encode($data), 1);
        }

        json_output("script_error", "An error occured: no action.");
    }
}
