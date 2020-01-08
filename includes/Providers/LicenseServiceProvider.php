<?php
namespace App\Providers;

use App\LicenseServerService;
use App\Loggers\DatabaseLogger;
use App\Requesting\Requester;
use App\Services\ShopSmsLicense\ShopSmsLicense;
use App\Services\ShopSmsLicenseEdit\ShopSmsLicenseEdit;
use App\Services\ShopSmsLicenseProlong\ShopSmsLicenseProlong;
use App\System\Application;
use App\System\Heart;
use App\System\Mailer;
use App\System\Settings;

class LicenseServiceProvider
{
    public function register(Application $app)
    {
        $app->bind(Mailer::class, function (Application $app) {
            $config = [
                'Host' => getenv('MAIL_HOST'),
                'Password' => getenv('MAIL_PASSWORD'),
            ];
            return new Mailer(
                $app->make(Settings::class),
                $app->make(DatabaseLogger::class),
                $config
            );
        });

        $app->bind(LicenseServerService::class, function (Application $app) {
            $url = 'https://license.sklep-sms.pl';
            $licenseSecret = getenv('LICENSE_SECRET');
            return new LicenseServerService($url, $licenseSecret, $app->make(Requester::class));
        });

        $app->extend(Heart::class, function (Heart $heart) {
            $heart->registerServiceModule(
                ShopSmsLicense::MODULE_ID,
                "Licencja Sklep-SMS",
                ShopSmsLicense::class
            );

            $heart->registerServiceModule(
                ShopSmsLicenseEdit::MODULE_ID,
                "Edycja Licencji Sklep-SMS",
                ShopSmsLicenseEdit::class
            );

            $heart->registerServiceModule(
                ShopSmsLicenseProlong::MODULE_ID,
                "Przedłużenie Licencji Sklep-SMS",
                ShopSmsLicenseProlong::class
            );

            return $heart;
        });
    }
}
