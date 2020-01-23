<?php
namespace App\Providers;

use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\ServiceModules\Other\OtherServiceModule;
use App\System\Application;
use App\System\Heart;
use App\Verification\PaymentModules\Cashbill;
use App\Verification\PaymentModules\Cssetti;
use App\Verification\PaymentModules\Gosetti;
use App\Verification\PaymentModules\Homepay;
use App\Verification\PaymentModules\Hostplay;
use App\Verification\PaymentModules\Microsms;
use App\Verification\PaymentModules\OneShotOneKill;
use App\Verification\PaymentModules\Profitsms;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\Simpay;
use App\Verification\PaymentModules\Transferuj;
use App\Verification\PaymentModules\Zabijaka;
use App\View\Blocks\BlockAdminContent;
use App\View\Blocks\BlockContent;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use App\View\Pages\PageAdminAntispamQuestions;
use App\View\Pages\PageAdminBoughtServices;
use App\View\Pages\PageAdminGroups;
use App\View\Pages\PageAdminIncome;
use App\View\Pages\PageAdminLogs;
use App\View\Pages\PageAdminMain;
use App\View\Pages\PageAdminPaymentAdmin;
use App\View\Pages\PageAdminPaymentPlatforms;
use App\View\Pages\PageAdminPaymentServiceCode;
use App\View\Pages\PageAdminPaymentSms;
use App\View\Pages\PageAdminPaymentTransfer;
use App\View\Pages\PageAdminPaymentWallet;
use App\View\Pages\PageAdminPlayersFlags;
use App\View\Pages\PageAdminPricing;
use App\View\Pages\PageAdminServers;
use App\View\Pages\PageAdminServiceCodes;
use App\View\Pages\PageAdminServices;
use App\View\Pages\PageAdminSettings;
use App\View\Pages\PageAdminSmsCodes;
use App\View\Pages\PageAdminUpdateServers;
use App\View\Pages\PageAdminUpdateWeb;
use App\View\Pages\PageAdminUsers;
use App\View\Pages\PageAdminUserService;
use App\View\Pages\PageCashbillTransferFinalized;
use App\View\Pages\PageChangePassword;
use App\View\Pages\PageContact;
use App\View\Pages\PageForgottenPassword;
use App\View\Pages\PageMain;
use App\View\Pages\PagePayment;
use App\View\Pages\PagePaymentLog;
use App\View\Pages\PageProfile;
use App\View\Pages\PagePurchase;
use App\View\Pages\PageRegister;
use App\View\Pages\PageRegulations;
use App\View\Pages\PageResetPassword;
use App\View\Pages\PageTakeOverService;
use App\View\Pages\PageTransferujBad;
use App\View\Pages\PageTransferujOk;
use App\View\Pages\PageUserOwnServices;

class HeartServiceProvider
{
    public function register(Application $app)
    {
        $app->extend(Heart::class, function (Heart $heart) {
            $this->registerPaymentModules($heart);
            $this->registerPages($heart);
            $this->registerAdminPages($heart);
            $this->registerBlocks($heart);
            $this->registerServices($heart);

            return $heart;
        });
    }

    private function registerPaymentModules(Heart $heart)
    {
        $heart->registerPaymentModule(OneShotOneKill::MODULE_ID, OneShotOneKill::class);
        $heart->registerPaymentModule(Cashbill::MODULE_ID, Cashbill::class);
        $heart->registerPaymentModule(Cssetti::MODULE_ID, Cssetti::class);
        $heart->registerPaymentModule(Gosetti::MODULE_ID, Gosetti::class);
        $heart->registerPaymentModule(Homepay::MODULE_ID, Homepay::class);
        $heart->registerPaymentModule(Hostplay::MODULE_ID, Hostplay::class);
        $heart->registerPaymentModule(Microsms::MODULE_ID, Microsms::class);
        $heart->registerPaymentModule(Profitsms::MODULE_ID, Profitsms::class);
        $heart->registerPaymentModule(Pukawka::MODULE_ID, Pukawka::class);
        $heart->registerPaymentModule(Simpay::MODULE_ID, Simpay::class);
        $heart->registerPaymentModule(Transferuj::MODULE_ID, Transferuj::class);
        $heart->registerPaymentModule(Zabijaka::MODULE_ID, Zabijaka::class);
    }

    private function registerPages(Heart $heart)
    {
        $heart->registerUserPage(
            PageCashbillTransferFinalized::PAGE_ID,
            PageCashbillTransferFinalized::class
        );
        $heart->registerUserPage(PageChangePassword::PAGE_ID, PageChangePassword::class);
        $heart->registerUserPage(PageContact::PAGE_ID, PageContact::class);
        $heart->registerUserPage(PageForgottenPassword::PAGE_ID, PageForgottenPassword::class);
        $heart->registerUserPage(PageMain::PAGE_ID, PageMain::class);
        $heart->registerUserPage(PagePayment::PAGE_ID, PagePayment::class);
        $heart->registerUserPage(PagePaymentLog::PAGE_ID, PagePaymentLog::class);
        $heart->registerUserPage(PageProfile::PAGE_ID, PageProfile::class);
        $heart->registerUserPage(PagePurchase::PAGE_ID, PagePurchase::class);
        $heart->registerUserPage(PageRegister::PAGE_ID, PageRegister::class);
        $heart->registerUserPage(PageRegulations::PAGE_ID, PageRegulations::class);
        $heart->registerUserPage(PageResetPassword::PAGE_ID, PageResetPassword::class);
        $heart->registerUserPage(PageTakeOverService::PAGE_ID, PageTakeOverService::class);
        $heart->registerUserPage(PageTransferujBad::PAGE_ID, PageTransferujBad::class);
        $heart->registerUserPage(PageTransferujOk::PAGE_ID, PageTransferujOk::class);
        $heart->registerUserPage(PageUserOwnServices::PAGE_ID, PageUserOwnServices::class);
    }

    private function registerAdminPages(Heart $heart)
    {
        $heart->registerAdminPage(
            PageAdminAntispamQuestions::PAGE_ID,
            PageAdminAntispamQuestions::class
        );
        $heart->registerAdminPage(PageAdminBoughtServices::PAGE_ID, PageAdminBoughtServices::class);
        $heart->registerAdminPage(PageAdminGroups::PAGE_ID, PageAdminGroups::class);
        $heart->registerAdminPage(PageAdminMain::PAGE_ID, PageAdminMain::class);
        $heart->registerAdminPage(PageAdminIncome::PAGE_ID, PageAdminIncome::class);
        $heart->registerAdminPage(PageAdminLogs::PAGE_ID, PageAdminLogs::class);
        $heart->registerAdminPage(PageAdminPaymentAdmin::PAGE_ID, PageAdminPaymentAdmin::class);
        $heart->registerAdminPage(
            PageAdminPaymentServiceCode::PAGE_ID,
            PageAdminPaymentServiceCode::class
        );
        $heart->registerAdminPage(PageAdminPaymentSms::PAGE_ID, PageAdminPaymentSms::class);
        $heart->registerAdminPage(
            PageAdminPaymentTransfer::PAGE_ID,
            PageAdminPaymentTransfer::class
        );
        $heart->registerAdminPage(PageAdminPaymentWallet::PAGE_ID, PageAdminPaymentWallet::class);
        $heart->registerAdminPage(PageAdminPlayersFlags::PAGE_ID, PageAdminPlayersFlags::class);
        $heart->registerAdminPage(PageAdminPricing::PAGE_ID, PageAdminPricing::class);
        $heart->registerAdminPage(PageAdminServers::PAGE_ID, PageAdminServers::class);
        $heart->registerAdminPage(PageAdminServiceCodes::PAGE_ID, PageAdminServiceCodes::class);
        $heart->registerAdminPage(PageAdminServices::PAGE_ID, PageAdminServices::class);
        $heart->registerAdminPage(PageAdminSettings::PAGE_ID, PageAdminSettings::class);
        $heart->registerAdminPage(PageAdminSmsCodes::PAGE_ID, PageAdminSmsCodes::class);
        $heart->registerAdminPage(
            PageAdminPaymentPlatforms::PAGE_ID,
            PageAdminPaymentPlatforms::class
        );
        $heart->registerAdminPage(PageAdminUpdateServers::PAGE_ID, PageAdminUpdateServers::class);
        $heart->registerAdminPage(PageAdminUpdateWeb::PAGE_ID, PageAdminUpdateWeb::class);
        $heart->registerAdminPage(PageAdminUserService::PAGE_ID, PageAdminUserService::class);
        $heart->registerAdminPage(PageAdminUsers::PAGE_ID, PageAdminUsers::class);
    }

    private function registerBlocks(Heart $heart)
    {
        $heart->registerBlock('admincontent', BlockAdminContent::class);
        $heart->registerBlock('content', BlockContent::class);
        $heart->registerBlock('logged_info', BlockLoggedInfo::class);
        $heart->registerBlock('services_buttons', BlockServicesButtons::class);
        $heart->registerBlock('user_buttons', BlockUserButtons::class);
        $heart->registerBlock('wallet', BlockWallet::class);
    }

    private function registerServices(Heart $heart)
    {
        $heart->registerServiceModule(
            ChargeWalletServiceModule::MODULE_ID,
            'Doładowanie Portfela',
            ChargeWalletServiceModule::class
        );

        $heart->registerServiceModule(
            ExtraFlagsServiceModule::MODULE_ID,
            'Dodatkowe Uprawnienia / Flagi',
            ExtraFlagsServiceModule::class
        );

        $heart->registerServiceModule(
            MybbExtraGroupsServiceModule::MODULE_ID,
            'Dodatkowe Grupy (MyBB)',
            MybbExtraGroupsServiceModule::class
        );

        $heart->registerServiceModule(
            OtherServiceModule::MODULE_ID,
            'Inne',
            OtherServiceModule::class
        );
    }
}
