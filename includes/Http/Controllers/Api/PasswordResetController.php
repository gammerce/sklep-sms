<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\UserPasswordService;
use Symfony\Component\HttpFoundation\Request;

class PasswordResetController
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        UserPasswordService $userPasswordService
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();

        if (is_logged()) {
            return new ApiResponse("logged_in", $lang->translate('logged'), 0);
        }

        $warnings = [];

        $uid = $request->request->get('uid');
        $sign = $request->request->get('sign');
        $pass = $request->request->get('pass');
        $passr = $request->request->get('pass_repeat');

        // Sprawdzanie hashu najwazniejszych danych
        if (!$sign || $sign != md5($uid . $settings['random_key'])) {
            return new ApiResponse("wrong_sign", $lang->translate('wrong_sign'), 0);
        }

        if ($warning = check_for_warnings("password", $pass)) {
            $warnings['pass'] = array_merge((array) $warnings['pass'], $warning);
        }
        if ($pass != $passr) {
            $warnings['pass_repeat'][] = $lang->translate('different_values');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $userPasswordService->change($uid, $pass);

        log_info($langShop->sprintf($langShop->translate('reset_pass'), $uid));

        return new ApiResponse("password_changed", $lang->translate('password_changed'), 1);
    }
}
