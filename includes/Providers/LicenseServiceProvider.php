<?php
namespace App\Providers;

use App\LicenseServerService;
use App\Services\ShopSmsLicense\ShopSmsLicense;
use App\Services\ShopSmsLicenseEdit\ShopSmsLicenseEdit;
use App\Services\ShopSmsLicenseProlong\ShopSmsLicenseProlong;
use App\System\Application;
use App\System\Heart;
use App\System\Mailer;

class LicenseServiceProvider
{
    public function register(Application $app)
    {
        $app->bind(Mailer::class, function (Application $app) {
            $config = [
                'Host' => getenv('MAIL_HOST'),
                'Password' => getenv('MAIL_PASSWORD'),
            ];
            return $app->makeWith(Mailer::class, compact('config'));
        });

        $app->bind(LicenseServerService::class, function () use ($app) {
            $url = 'https://license.sklep-sms.pl';
            $licenseSecret = getenv('LICENSE_SECRET');
            return $app->makeWith(LicenseServerService::class, compact('url', 'licenseSecret'));
        });
    }

    public function boot(Application $app)
    {
        $app->extend(Heart::class, function (Heart $heart) {
            $heart->registerServiceModule(
                "shopsms_license",
                "Licencja Sklep-SMS",
                ShopSmsLicense::class
            );

            $heart->registerServiceModule(
                "shopsms_license_edit",
                "Edycja Licencji Sklep-SMS",
                ShopSmsLicenseEdit::class
            );

            $heart->registerServiceModule(
                "shopsms_license_prolong",
                "Przedłużenie Licencji Sklep-SMS",
                ShopSmsLicenseProlong::class
            );

            return $heart;
        });
    }
}
