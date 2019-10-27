<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Heart;
use App\Pages\PageAdminIncome;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use App\Responses\PlainResponse;
use App\Services\Interfaces\IServiceActionExecute;
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

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
