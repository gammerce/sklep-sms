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
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class JsonHttpController
{
    public function action(
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth
    ) {
        $lang = $translationManager->user();

        $user = $auth->user();
        $action = $request->request->get("action");

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
