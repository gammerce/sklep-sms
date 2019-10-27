<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Heart;
use App\Models\Purchase;
use App\Pages\PageAdminIncome;
use App\Payment;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use App\Responses\PlainResponse;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceTakeOver;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\Settings;
use App\Template;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class JsonHttpController
{
    public function action(
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth,
        Template $templates,
        Settings $settings
    ) {
        $lang = $translationManager->user();

        $user = $auth->user();
        $action = $request->request->get("action");

        $data = [];

        if ($action == "purchase_form_validate") {
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServicePurchaseWeb)
            ) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
            if (!$heart->userCanUseService($user->getUid(), $serviceModule->service)) {
                return new ApiResponse(
                    "no_permission",
                    $lang->translate('service_no_permission'),
                    0
                );
            }

            // Przeprowadzamy walidację danych wprowadzonych w formularzu
            $returnData = $serviceModule->purchaseFormValidate($_POST);

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($returnData['status'] == "warnings") {
                $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
            } else {
                //
                // Uzupełniamy brakujące dane
                /** @var Purchase $purchaseData */
                $purchaseData = $returnData['purchase_data'];

                if ($purchaseData->getService() === null) {
                    $purchaseData->setService($serviceModule->service['id']);
                }

                if (!$purchaseData->getPayment('cost') && $purchaseData->getTariff() !== null) {
                    $purchaseData->setPayment([
                        'cost' => $purchaseData->getTariff()->getProvision(),
                    ]);
                }

                if (
                    $purchaseData->getPayment('sms_service') === null &&
                    !$purchaseData->getPayment("no_sms") &&
                    strlen($settings['sms_service'])
                ) {
                    $purchaseData->setPayment([
                        'sms_service' => $settings['sms_service'],
                    ]);
                }

                // Ustawiamy taryfe z numerem
                if ($purchaseData->getPayment('sms_service') !== null) {
                    $payment = new Payment($purchaseData->getPayment('sms_service'));
                    $purchaseData->setTariff(
                        $payment
                            ->getPaymentModule()
                            ->getTariffById($purchaseData->getTariff()->getId())
                    );
                }

                if ($purchaseData->getEmail() === null && strlen($user->getEmail())) {
                    $purchaseData->setEmail($user->getEmail());
                }

                $purchaseDataEncoded = base64_encode(serialize($purchaseData));
                $returnData['data'] = [
                    'length' => 8000,
                    'data' => $purchaseDataEncoded,
                    'sign' => md5($purchaseDataEncoded . $settings['random_key']),
                ];
            }

            return new ApiResponse(
                $returnData['status'],
                $returnData['text'],
                $returnData['positive'],
                $returnData['data']
            );
        }

        if ($action == "payment_form_validate") {
            // Sprawdzanie hashu danych przesłanych przez formularz
            if (
                !isset($_POST['purchase_sign']) ||
                $_POST['purchase_sign'] != md5($_POST['purchase_data'] . $settings['random_key'])
            ) {
                return new ApiResponse("wrong_sign", $lang->translate('wrong_sign'), 0);
            }

            /** @var Purchase $purchaseData */
            $purchaseData = unserialize(base64_decode($_POST['purchase_data']));

            // Fix: get user data again to avoid bugs linked with user wallet
            $purchaseData->user = $heart->getUser($purchaseData->user->getUid());

            // Dodajemy dane płatności
            $purchaseData->setPayment([
                'method' => $_POST['method'],
                'sms_code' => $_POST['sms_code'],
                'service_code' => $_POST['service_code'],
            ]);

            $returnPayment = validate_payment($purchaseData);
            return new ApiResponse(
                $returnPayment['status'],
                $returnPayment['text'],
                $returnPayment['positive'],
                $returnPayment['data']
            );
        }

        if ($action == "refresh_blocks") {
            $data = [];
            if (isset($_POST['bricks'])) {
                $bricks = explode(";", $_POST['bricks']);

                foreach ($bricks as $brick) {
                    // Nie ma takiego bloku do odświeżenia
                    if (($block = $heart->getBlock($brick)) === null) {
                        continue;
                    }

                    $data[$block->getContentId()]['content'] = $block->getContent(
                        $request->query->all(),
                        $request->request->all()
                    );
                    if ($data[$block->getContentId()]['content'] !== null) {
                        $data[$block->getContentId()]['class'] = $block->getContentClass();
                    } else {
                        $data[$block->getContentId()]['class'] = "";
                    }
                }
            }

            return new PlainResponse(json_encode($data));
        }

        if ($action == "get_service_long_description") {
            $output = "";
            if (($serviceModule = $heart->getServiceModule($_POST['service'])) !== null) {
                $output = $serviceModule->descriptionFullGet();
            }

            return new PlainResponse($output);
        }

        if ($action == "get_purchase_info") {
            return new PlainResponse(
                purchase_info([
                    'purchase_id' => $_POST['purchase_id'],
                    'action' => "web",
                ])
            );
        }

        if ($action == "form_user_service_edit") {
            if (!is_logged()) {
                return new HtmlResponse($lang->translate('service_cant_be_modified'));
            }

            // Użytkownik nie może edytować usługi
            if (!$settings['user_edit_service']) {
                return new HtmlResponse($lang->translate('not_logged'));
            }

            $userService = get_users_services($_POST['id']);

            if (empty($userService)) {
                return new HtmlResponse($lang->translate('dont_play_games'));
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($userService['uid'] != $user->getUid()) {
                return new HtmlResponse($lang->translate('dont_play_games'));
            }

            if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
                return new HtmlResponse($lang->translate('service_cant_be_modified'));
            }

            if (
                !$settings['user_edit_service'] ||
                !($serviceModule instanceof IServiceUserOwnServicesEdit)
            ) {
                return new HtmlResponse($lang->translate('service_cant_be_modified'));
            }

            $buttons = $templates->render("services/my_services_savencancel");

            return new HtmlResponse(
                $buttons . $serviceModule->userOwnServiceEditFormGet($userService)
            );
        }

        if ($action == "get_user_service_brick") {
            if (!is_logged()) {
                return new HtmlResponse($lang->translate('not_logged'));
            }

            $userService = get_users_services($_POST['id']);

            // Brak takiej usługi w bazie
            if (empty($userService)) {
                return new HtmlResponse($lang->translate('dont_play_games'));
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($userService['uid'] != $user->getUid()) {
                return new HtmlResponse($lang->translate('dont_play_games'));
            }

            if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
                return new HtmlResponse($lang->translate('service_not_displayed'));
            }

            if (!($serviceModule instanceof IServiceUserOwnServices)) {
                return new HtmlResponse($lang->translate('service_not_displayed'));
            }

            if (
                $settings['user_edit_service'] &&
                $serviceModule instanceof IServiceUserOwnServicesEdit
            ) {
                $buttonEdit = create_dom_element("button", $lang->translate('edit'), [
                    'class' => "button is-small edit_row",
                    'type' => 'button',
                ]);
            }

            return new HtmlResponse(
                $serviceModule->userOwnServiceInfoGet($userService, $buttonEdit)
            );
        }

        if ($action == "user_service_edit") {
            if (!is_logged()) {
                return new ApiResponse("not_logged", $lang->translate('not_logged'), 0);
            }

            $userService = get_users_services($_POST['id']);

            // Brak takiej usługi w bazie
            if (empty($userService)) {
                return new ApiResponse("dont_play_games", $lang->translate('dont_play_games'), 0);
            }

            // Dany użytkownik nie jest właścicielem usługi o danym id
            if ($userService['uid'] != $user->getUid()) {
                return new ApiResponse("dont_play_games", $lang->translate('dont_play_games'), 0);
            }

            if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Wykonujemy metode edycji usługi użytkownika na module, który ją obsługuje
            if (
                !$settings['user_edit_service'] ||
                !($serviceModule instanceof IServiceUserOwnServicesEdit)
            ) {
                return new ApiResponse(
                    "service_cant_be_modified",
                    $lang->translate('service_cant_be_modified'),
                    0
                );
            }

            $returnData = $serviceModule->userOwnServiceEdit($_POST, $userService);

            if ($returnData['status'] == "warnings") {
                $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
            }

            return new ApiResponse(
                $returnData['status'],
                $returnData['text'],
                $returnData['positive'],
                $returnData['data']
            );
        }

        if ($action == "service_take_over_form_get") {
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServiceTakeOver)
            ) {
                return new PlainResponse($lang->translate('bad_module'));
            }

            return new PlainResponse($serviceModule->serviceTakeOverFormGet());
        }

        if ($action == "service_take_over") {
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServiceTakeOver)
            ) {
                return new PlainResponse($lang->translate('bad_module'));
            }

            $returnData = $serviceModule->serviceTakeOver($_POST);

            if ($returnData['status'] == "warnings") {
                $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
            }

            return new ApiResponse(
                $returnData['status'],
                $returnData['text'],
                $returnData['positive'],
                $returnData['data']
            );
        }

        if ($request->query->get("action") === "get_income") {
            $user->setPrivileges([
                'acp' => true,
                'view_income' => true,
            ]);
            $page = new PageAdminIncome();

            return new HtmlResponse(
                $page->getContent($request->query->all(), $request->request->all())
            );
        }

        if ($action == "service_action_execute") {
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServiceActionExecute)
            ) {
                return new PlainResponse($lang->translate('bad_module'));
            }

            return new PlainResponse(
                $serviceModule->actionExecute($_POST['service_action'], $_POST)
            );
        }

        if ($action === "get_template") {
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
                $data['template'] = $templates->render(
                    "jsonhttp/" . $template,
                    compact('username', 'email')
                );
            }

            return new PlainResponse(json_encode($data));
        }

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
