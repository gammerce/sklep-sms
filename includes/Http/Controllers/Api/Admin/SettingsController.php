<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\LanguageRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SupportDirectBillingRule;
use App\Http\Validation\Rules\SupportSmsRule;
use App\Http\Validation\Rules\SupportTransferRule;
use App\Http\Validation\Rules\ThemeRule;
use App\Http\Validation\Rules\YesNoRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Repositories\SettingsRepository;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class SettingsController
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        Settings $settings,
        SettingsRepository $settingsRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = new Validator(
            [
                'contact' => $request->request->get('contact'),
                'cron' => $request->request->get('cron'),
                'currency' => $request->request->get('currency'),
                'date_format' => $request->request->get('date_format'),
                'delete_logs' => $request->request->get('delete_logs'),
                'direct_billing_platform' => $request->request->get('direct_billing_platform'),
                'gadugadu' => $request->request->get('gadugadu'),
                'google_analytics' => trim($request->request->get('google_analytics')),
                'language' => escape_filename($request->request->get('language')),
                'license_token' => is_demo() ? null : $request->request->get('license_token'),
                'row_limit' => $request->request->get('row_limit'),
                'sender_email' => $request->request->get('sender_email'),
                'sender_email_name' => $request->request->get('sender_email_name'),
                'shop_name' => $request->request->get('shop_name'),
                'shop_url' => is_demo()
                    ? $settings->getShopUrl()
                    : $request->request->get('shop_url'),
                'signature' => $request->request->get('signature'),
                'sms_platform' => $request->request->get('sms_platform'),
                'theme' => escape_filename($request->request->get('theme')),
                'transfer_platform' => $request->request->get('transfer_platform'),
                'user_edit_service' => $request->request->get('user_edit_service'),
                'vat' => $request->request->get('vat'),
            ],
            [
                'contact' => [],
                'cron' => [new RequiredRule(), new YesNoRule()],
                'currency' => [],
                'date_format' => [],
                'delete_logs' => [new NumberRule()],
                'direct_billing_platform' => [new SupportDirectBillingRule()],
                'gadugadu' => [],
                'google_analytics' => [],
                'language' => [new RequiredRule(), new LanguageRule()],
                'license_token' => [],
                'row_limit' => [new NumberRule()],
                'sender_email' => [new EmailRule()],
                'sender_email_name' => [],
                'shop_name' => [],
                'shop_url' => [],
                'signature' => [],
                'sms_platform' => [new SupportSmsRule()],
                'theme' => [new RequiredRule(), new ThemeRule()],
                'transfer_platform' => [new SupportTransferRule()],
                'user_edit_service' => [new RequiredRule(), new YesNoRule()],
                'vat' => [new NumberRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $values = [
            'contact' => $validated['contact'],
            'cron_each_visit' => $validated['cron'],
            'currency' => $validated['currency'],
            'date_format' => $validated['date_format'],
            'delete_logs' => $validated['delete_logs'],
            'direct_billing_platform' => $validated['direct_billing_platform'],
            'gadugadu' => $validated['gadugadu'],
            'google_analytics' => $validated['google_analytics'],
            'language' => $validated['language'],
            'row_limit' => $validated['row_limit'],
            'sender_email' => $validated['sender_email'],
            'sender_email_name' => $validated['sender_email_name'],
            'shop_name' => $validated['shop_name'],
            'shop_url' => $validated['shop_url'],
            'signature' => $validated['signature'],
            'sms_platform' => $validated['sms_platform'],
            'theme' => $validated['theme'],
            'transfer_platform' => $validated['transfer_platform'],
            'user_edit_service' => $validated['user_edit_service'],
            'vat' => $validated['vat'],
        ];

        if ($validated['license_token']) {
            $values['license_password'] = $validated['license_token'];
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
