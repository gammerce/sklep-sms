<?php
namespace App\Providers;

use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Requesting\Requester;
use App\ServiceModules\ShopSmsLicense\LicenseServiceModule;
use App\ServiceModules\ShopSmsLicenseEdit\LicenseEditServiceModule;
use App\ServiceModules\ShopSmsLicenseProlong\LicenseProlongServiceModule;
use App\Services\LicenseServerService;
use App\Support\Mailer;
use App\System\Application;
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

        $app->extend(ServiceModuleManager::class, function (
            ServiceModuleManager $serviceModuleManager
        ) {
            $serviceModuleManager->register(LicenseServiceModule::class, "Licencja Sklep-SMS");

            $serviceModuleManager->register(
                LicenseEditServiceModule::class,
                "Edycja Licencji Sklep-SMS"
            );

            $serviceModuleManager->register(
                LicenseProlongServiceModule::class,
                "Przedłużenie Licencji Sklep-SMS"
            );

            return $serviceModuleManager;
        });
    }
}
