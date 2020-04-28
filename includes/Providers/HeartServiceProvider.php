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
use App\View\BlockManager;
use App\View\Blocks\BlockAdminContent;
use App\View\Blocks\BlockContent;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use App\View\PageManager;
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
use App\View\Pages\Shop\PagePaymentError;
use App\View\Pages\Shop\PagePaymentLog;
use App\View\Pages\Shop\PagePaymentSuccess;
use App\View\Pages\Shop\PageProfile;
use App\View\Pages\Shop\PagePurchase;
use App\View\Pages\Shop\PageRegister;
use App\View\Pages\Shop\PageRegulations;
use App\View\Pages\Shop\PageResetPassword;
use App\View\Pages\Shop\PageSignIn;
use App\View\Pages\Shop\PageTakeOverService;
use App\View\Pages\Shop\PageTPaySuccess;
use App\View\Pages\Shop\PageUserOwnServices;
use App\View\ServiceModuleManager;

class HeartServiceProvider
{
    public function register(Application $app)
    {
        $app->extend(Heart::class, function (Heart $heart) {
            $this->registerPaymentModules($heart);
            return $heart;
        });

        $app->extend(BlockManager::class, function (BlockManager $blockManager) {
            $this->registerBlocks($blockManager);
            return $blockManager;
        });

        $app->extend(PageManager::class, function (PageManager $pageManager) {
            $this->registerPages($pageManager);
            $this->registerAdminPages($pageManager);
            return $pageManager;
        });

        $app->extend(ServiceModuleManager::class, function (
            ServiceModuleManager $serviceModuleManager
        ) {
            $this->registerServices($serviceModuleManager);
            return $serviceModuleManager;
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

    private function registerPages(PageManager $pageManager)
    {
        $pageManager->registerUser(PageCashbillTransferFinalized::class);
        $pageManager->registerUser(PageChangePassword::class);
        $pageManager->registerUser(PageContact::class);
        $pageManager->registerUser(PageForgottenPassword::class);
        $pageManager->registerUser(PageMain::class);
        $pageManager->registerUser(PagePayment::class);
        $pageManager->registerUser(PagePaymentLog::class);
        $pageManager->registerUser(PageProfile::class);
        $pageManager->registerUser(PagePurchase::class);
        $pageManager->registerUser(PageRegister::class);
        $pageManager->registerUser(PageRegulations::class);
        $pageManager->registerUser(PageResetPassword::class);
        $pageManager->registerUser(PageTakeOverService::class);
        $pageManager->registerUser(PagePaymentError::class);
        $pageManager->registerUser(PagePaymentSuccess::class);
        $pageManager->registerUser(PageTPaySuccess::class);
        $pageManager->registerUser(PageUserOwnServices::class);
        $pageManager->registerUser(PageSignIn::class);
    }

    private function registerAdminPages(PageManager $pageManager)
    {
        $pageManager->registerAdmin(PageAdminAntispamQuestions::PAGE_ID);
        $pageManager->registerAdmin(PageAdminBoughtServices::class);
        $pageManager->registerAdmin(PageAdminGroups::class);
        $pageManager->registerAdmin(PageAdminMain::class);
        $pageManager->registerAdmin(PageAdminIncome::class);
        $pageManager->registerAdmin(PageAdminLogs::class);
        $pageManager->registerAdmin(PageAdminPaymentAdmin::class);
        $pageManager->registerAdmin(PageAdminPaymentServiceCode::class);
        $pageManager->registerAdmin(PageAdminPaymentSms::class);
        $pageManager->registerAdmin(PageAdminPaymentTransfer::class);
        $pageManager->registerAdmin(PageAdminPaymentDirectBilling::class);
        $pageManager->registerAdmin(PageAdminPaymentWallet::class);
        $pageManager->registerAdmin(PageAdminPlayersFlags::class);
        $pageManager->registerAdmin(PageAdminPricing::class);
        $pageManager->registerAdmin(PageAdminServers::class);
        $pageManager->registerAdmin(PageAdminServiceCodes::class);
        $pageManager->registerAdmin(PageAdminServices::class);
        $pageManager->registerAdmin(PageAdminSettings::class);
        $pageManager->registerAdmin(PageAdminSmsCodes::class);
        $pageManager->registerAdmin(PageAdminPaymentPlatforms::class);
        $pageManager->registerAdmin(PageAdminUpdateServers::class);
        $pageManager->registerAdmin(PageAdminUpdateWeb::class);
        $pageManager->registerAdmin(PageAdminUserService::class);
        $pageManager->registerAdmin(PageAdminUsers::class);
    }

    private function registerBlocks(BlockManager $blockManager)
    {
        $blockManager->register(BlockAdminContent::class);
        $blockManager->register(BlockContent::class);
        $blockManager->register(BlockLoggedInfo::class);
        $blockManager->register(BlockServicesButtons::class);
        $blockManager->register(BlockUserButtons::class);
        $blockManager->register(BlockWallet::class);
    }

    private function registerServices(ServiceModuleManager $serviceModuleManager)
    {
        // TODO Translate it
        $serviceModuleManager->register(ChargeWalletServiceModule::class, "Doładowanie Portfela");

        $serviceModuleManager->register(
            ExtraFlagsServiceModule::class,
            "Dodatkowe Uprawnienia / Flagi"
        );

        $serviceModuleManager->register(
            MybbExtraGroupsServiceModule::class,
            "Dodatkowe Grupy (MyBB)"
        );

        $serviceModuleManager->register(OtherServiceModule::class, "Inne");
    }
}
