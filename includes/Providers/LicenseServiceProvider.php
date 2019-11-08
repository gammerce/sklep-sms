<?php
namespace App\Providers;

use App\System\Application;
use App\System\Heart;
use App\LicenseServerService;
use App\System\Mailer;
use App\Requesting\Requester;
use App\Services\ShopSmsLicense\ShopSmsLicense;
use App\Services\ShopSmsLicense\ShopSmsLicenseSimple;
use App\Services\ShopSmsLicenseEdit\ShopSmsLicenseEdit;
use App\Services\ShopSmsLicenseEdit\ShopSmsLicenseEditSimple;
use App\Services\ShopSmsLicenseProlong\ShopSmsLicenseProlong;
use App\Services\ShopSmsLicenseProlong\ShopSmsLicenseProlongSimple;
use App\System\Settings;
use App\Translation\TranslationManager;

class LicenseServiceProvider
{
    public function register(Application $app)
    {
        $app->bind(Mailer::class, function () use ($app) {
            return new Mailer($app->make(Settings::class), $app->make(TranslationManager::class), [
                'Host' => getenv('MAIL_HOST'),
                'Password' => getenv('MAIL_PASSWORD'),
            ]);
        });

        $app->bind(LicenseServerService::class, function () use ($app) {
            return new LicenseServerService(
                'https://license.sklep-sms.pl',
                getenv('LICENSE_SECRET'),
                $app->make(Requester::class)
            );
        });
    }

    public function boot(Heart $heart)
    {
        $heart->registerServiceModule(
            "shopsms_license",
            "Licencja Sklep-SMS",
            ShopSmsLicense::class,
            ShopSmsLicenseSimple::class
        );

        $heart->registerServiceModule(
            "shopsms_license_edit",
            "Edycja Licencji Sklep-SMS",
            ShopSmsLicenseEdit::class,
            ShopSmsLicenseEditSimple::class
        );

        $heart->registerServiceModule(
            "shopsms_license_prolong",
            "Przedłużenie Licencji Sklep-SMS",
            ShopSmsLicenseProlong::class,
            ShopSmsLicenseProlongSimple::class
        );
    }
}
