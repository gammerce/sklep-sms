<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Responses\ApiResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class ServiceCodeCollection
{
    public function post(
        $serviceId,
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        Heart $heart,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $uid = $request->request->get("uid");
        $code = $request->request->get("code");

        $warnings = [];

        if (($serviceModule = $heart->getServiceModule($serviceId)) === null) {
            return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
        }

        // Id użytkownika
        if (strlen($uid) && ($warning = check_for_warnings("uid", $uid))) {
            $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
        }

        // Kod
        if (!strlen($code)) {
            $warnings['code'][] = $lang->translate('field_no_empty');
        } else {
            if (strlen($code) > 16) {
                $warnings['code'][] = $lang->translate('return_code_length_warn');
            }
        }

        // Łączymy zwrócone błędy
        $warnings = array_merge(
            (array) $warnings,
            (array) $serviceModule->serviceCodeAdminAddValidate($_POST)
        );

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        // Pozyskujemy dane kodu do dodania
        $codeData = $serviceModule->serviceCodeAdminAddInsert($_POST);

        $db->query(
            $db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "service_codes` " .
                    "SET `code` = '%s', `service` = '%s', `uid` = '%d', `server` = '%d', `amount` = '%d', `tariff` = '%d', `data` = '%s'",
                [
                    $code,
                    $serviceModule->service['id'],
                    if_strlen($uid, 0),
                    if_isset($codeData['server'], 0),
                    if_isset($codeData['amount'], 0),
                    if_isset($codeData['tariff'], 0),
                    $codeData['data'],
                ]
            )
        );

        log_info(
            $langShop->sprintf(
                $langShop->translate('code_added_admin'),
                $user->getUsername(),
                $user->getUid(),
                $code,
                $serviceModule->service['id']
            )
        );

        return new ApiResponse('ok', $lang->translate('code_added'), 1);
    }
}
