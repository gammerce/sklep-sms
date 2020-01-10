<?php
namespace App\Providers;

use App\Services\LicenseServerService;
use App\Loggers\DatabaseLogger;
use App\Requesting\Requester;
use App\ServiceModules\ShopSmsLicense\LicenseServiceModule;
use App\ServiceModules\ShopSmsLicenseEdit\LicenseEditServiceModule;
use App\ServiceModules\ShopSmsLicenseProlong\LicenseProlongServiceModule;
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
                LicenseServiceModule::MODULE_ID,
                "Licencja Sklep-SMS",
                LicenseServiceModule::class
            );

            $heart->registerServiceModule(
                LicenseEditServiceModule::MODULE_ID,
                "Edycja Licencji Sklep-SMS",
                LicenseEditServiceModule::class
            );

            $heart->registerServiceModule(
                LicenseProlongServiceModule::MODULE_ID,
                "Przedłużenie Licencji Sklep-SMS",
                LicenseProlongServiceModule::class
            );

            return $heart;
        });
    }
}
