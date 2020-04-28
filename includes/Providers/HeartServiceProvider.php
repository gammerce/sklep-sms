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
use App\Verification\PaymentModules\GetPay;
use App\Verification\PaymentModules\Gosetti;
use App\Verification\PaymentModules\Homepay;
use App\Verification\PaymentModules\Hostplay;
use App\Verification\PaymentModules\MicroSMS;
use App\Verification\PaymentModules\OneShotOneKill;
use App\Verification\PaymentModules\Profitsms;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use App\Verification\PaymentModules\Zabijaka;
use App\View\Blocks\BlockAdminContent;
use App\View\Blocks\BlockContent;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use App\View\Pages\Admin\PageAdminAntispamQuestions;
use App\View\Pages\Admin\PageAdminBoughtServices;
use App\View\Pages\Admin\PageAdminGroups;
use App\View\Pages\Admin\PageAdminIncome;
use App\View\Pages\Admin\PageAdminLogs;
use App\View\Pages\Admin\PageAdminMain;
use App\View\Pages\Admin\PageAdminPaymentAdmin;
use App\View\Pages\Admin\PageAdminPaymentDirectBilling;
use App\View\Pages\Admin\PageAdminPaymentPlatforms;
use App\View\Pages\Admin\PageAdminPaymentServiceCode;
use App\View\Pages\Admin\PageAdminPaymentSms;
use App\View\Pages\Admin\PageAdminPaymentTransfer;
use App\View\Pages\Admin\PageAdminPaymentWallet;
use App\View\Pages\Admin\PageAdminPlayersFlags;
use App\View\Pages\Admin\PageAdminPricing;
use App\View\Pages\Admin\PageAdminServers;
use App\View\Pages\Admin\PageAdminServiceCodes;
use App\View\Pages\Admin\PageAdminServices;
use App\View\Pages\Admin\PageAdminSettings;
use App\View\Pages\Admin\PageAdminSmsCodes;
use App\View\Pages\Admin\PageAdminUpdateServers;
use App\View\Pages\Admin\PageAdminUpdateWeb;
use App\View\Pages\Admin\PageAdminUsers;
use App\View\Pages\Admin\PageAdminUserService;
use App\View\Pages\Shop\PageCashbillTransferFinalized;
use App\View\Pages\Shop\PageChangePassword;
use App\View\Pages\Shop\PageContact;
use App\View\Pages\Shop\PageForgottenPassword;
use App\View\Pages\Shop\PageMain;
use App\View\Pages\Shop\PagePayment;
use App\View\Pages\Shop\PagePaymentLog;
use App\View\Pages\Shop\PagePaymentSuccess;
use App\View\Pages\Shop\PageProfile;
use App\View\Pages\Shop\PagePurchase;
use App\View\Pages\Shop\PageRegister;
use App\View\Pages\Shop\PageRegulations;
use App\View\Pages\Shop\PageResetPassword;
use App\View\Pages\Shop\PageTakeOverService;
use App\View\Pages\Shop\PagePaymentError;
use App\View\Pages\Shop\PageTPaySuccess;
use App\View\Pages\Shop\PageUserOwnServices;

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
        $heart->registerPaymentModule(GetPay::MODULE_ID, GetPay::class);
        $heart->registerPaymentModule(Gosetti::MODULE_ID, Gosetti::class);
        $heart->registerPaymentModule(Homepay::MODULE_ID, Homepay::class);
        $heart->registerPaymentModule(Hostplay::MODULE_ID, Hostplay::class);
        $heart->registerPaymentModule(MicroSMS::MODULE_ID, MicroSMS::class);
        $heart->registerPaymentModule(Profitsms::MODULE_ID, Profitsms::class);
        $heart->registerPaymentModule(Pukawka::MODULE_ID, Pukawka::class);
        $heart->registerPaymentModule(SimPay::MODULE_ID, SimPay::class);
        $heart->registerPaymentModule(TPay::MODULE_ID, TPay::class);
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
        $heart->registerUserPage(PagePaymentError::PAGE_ID, PagePaymentError::class);
        $heart->registerUserPage(PagePaymentSuccess::PAGE_ID, PagePaymentSuccess::class);
        $heart->registerUserPage(PageTPaySuccess::PAGE_ID, PageTPaySuccess::class);
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
        $heart->registerAdminPage(
            PageAdminPaymentDirectBilling::PAGE_ID,
            PageAdminPaymentDirectBilling::class
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
