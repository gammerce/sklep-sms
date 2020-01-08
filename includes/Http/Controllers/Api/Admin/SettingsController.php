<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\SettingsRepository;
use App\System\Application;
use App\System\FileSystemContract;
use App\System\Heart;
use App\System\Path;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use Symfony\Component\HttpFoundation\Request;

class SettingsController
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        Heart $heart,
        Path $path,
        Settings $settings,
        SettingsRepository $settingsRepository,
        FileSystemContract $fileSystem,
        DatabaseLogger $logger,
        Application $app
    ) {
        $lang = $translationManager->user();

        $smsPaymentPlatformId = $request->request->get('sms_platform');
        $transferPaymentPlatformId = $request->request->get('transfer_platform');
        $currency = $request->request->get('currency');
        $shopName = $request->request->get('shop_name');
        $shopUrl = $app->isDemo() ? $settings['shop_url'] : $request->request->get('shop_url');
        $senderEmail = $request->request->get('sender_email');
        $senderEmailName = $request->request->get('sender_email_name');
        $signature = $request->request->get('signature');
        $vat = $request->request->get('vat');
        $contact = $request->request->get('contact');
        $rowLimit = $request->request->get('row_limit');
        $licenseToken = $app->isDemo() ? null : $request->request->get('license_token');
        $cron = $request->request->get('cron');
        $language = escape_filename($request->request->get('language'));
        $theme = escape_filename($request->request->get('theme'));
        $dateFormat = $request->request->get('date_format');
        $deleteLogs = $request->request->get('delete_logs');
        $googleAnalytics = trim($request->request->get('google_analytics'));
        $gadugadu = $request->request->get('gadugadu');
        $userEditService = $request->request->get('user_edit_service');

        $warnings = [];

        // TODO Refactor it to use rules

        if (strlen($smsPaymentPlatformId)) {
            $paymentModule = $heart->getPaymentModuleByPlatformId($smsPaymentPlatformId);
            if (!($paymentModule instanceof SupportSms)) {
                $warnings['sms_platform'][] = $lang->t('no_sms_platform');
            }
        }

        if (strlen($transferPaymentPlatformId)) {
            $paymentModule = $heart->getPaymentModuleByPlatformId($transferPaymentPlatformId);
            if (!($paymentModule instanceof SupportTransfer)) {
                $warnings['transfer_platform'][] = $lang->t('no_transfer_platform');
            }
        }

        if (strlen($senderEmail) && ($warning = check_for_warnings("email", $senderEmail))) {
            $warnings['sender_email'] = array_merge((array) $warnings['sender_email'], $warning);
        }

        if ($warning = check_for_warnings("number", $vat)) {
            $warnings['vat'] = array_merge((array) $warnings['vat'], $warning);
        }

        if ($warning = check_for_warnings("number", $deleteLogs)) {
            $warnings['delete_logs'] = array_merge((array) $warnings['delete_logs'], $warning);
        }

        if ($warning = check_for_warnings("number", $rowLimit)) {
            $warnings['row_limit'] = array_merge((array) $warnings['row_limit'], $warning);
        }

        if (!in_array($cron, ["1", "0"])) {
            $warnings['cron'][] = $lang->t('only_yes_no');
        }

        if (!in_array($userEditService, ["1", "0"])) {
            $warnings['user_edit_service'][] = $lang->t('only_yes_no');
        }

        if (
            !$theme ||
            !$fileSystem->isDirectory($path->to("themes/{$theme}")) ||
            $theme[0] == '.'
        ) {
            $warnings['theme'][] = $lang->t('no_theme');
        }

        if (
            !$language ||
            !$fileSystem->isDirectory($path->to("translations/{$language}")) ||
            $language[0] == '.'
        ) {
            $warnings['language'][] = $lang->t('no_language');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $values = [
            'sms_platform' => $smsPaymentPlatformId,
            'transfer_platform' => $transferPaymentPlatformId,
            'currency' => $currency,
            'shop_name' => $shopName,
            'shop_url' => $shopUrl,
            'sender_email' => $senderEmail,
            'sender_email_name' => $senderEmailName,
            'signature' => $signature,
            'vat' => $vat,
            'contact' => $contact,
            'row_limit' => $rowLimit,
            'cron_each_visit' => $cron,
            'user_edit_service' => $userEditService,
            'theme' => $theme,
            'language' => $language,
            'date_format' => $dateFormat,
            'delete_logs' => $deleteLogs,
            'google_analytics' => $googleAnalytics,
            'gadugadu' => $gadugadu,
        ];

        if ($licenseToken) {
            $values['license_password'] = $licenseToken;
            $values['license_login'] = 'license';
        }

        $updated = $settingsRepository->update($values);

        if ($updated) {
            $logger->logWithActor('log_settings_edited');
            return new SuccessApiResponse($lang->t('settings_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('settings_no_edit'), false);
    }
}
