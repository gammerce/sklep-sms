<?php
namespace App\Providers;

use App\Managers\BlockManager;
use App\Managers\PageManager;
use App\Managers\PaymentModuleManager;
use App\Managers\ServiceModuleManager;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\ServiceModules\Other\OtherServiceModule;
use App\Support\Meta;
use App\System\Application;
use App\Verification\PaymentModules\CashBill;
use App\Verification\PaymentModules\Cssetti;
use App\Verification\PaymentModules\GetPay;
use App\Verification\PaymentModules\Gosetti;
use App\Verification\PaymentModules\Homepay;
use App\Verification\PaymentModules\Hostplay;
use App\Verification\PaymentModules\HotPay;
use App\Verification\PaymentModules\MicroSMS;
use App\Verification\PaymentModules\OneShotOneKill;
use App\Verification\PaymentModules\PayPal;
use App\Verification\PaymentModules\Profitsms;
use App\Verification\PaymentModules\Pukawka;
use App\Verification\PaymentModules\SimPay;
use App\Verification\PaymentModules\TPay;
use App\View\Blocks\BlockAdminContent;
use App\View\Blocks\BlockContent;
use App\View\Blocks\BlockLoggedInfo;
use App\View\Blocks\BlockServersButtons;
use App\View\Blocks\BlockServicesButtons;
use App\View\Blocks\BlockUserButtons;
use App\View\Blocks\BlockWallet;
use App\View\Pages\Admin\PageAdminBoughtServices;
use App\View\Pages\Admin\PageAdminGroups;
use App\View\Pages\Admin\PageAdminIncome;
use App\View\Pages\Admin\PageAdminLogs;
use App\View\Pages\Admin\PageAdminMain;
use App\View\Pages\Admin\PageAdminPaymentPlatforms;
use App\View\Pages\Admin\PageAdminPayments;
use App\View\Pages\Admin\PageAdminPlayersFlags;
use App\View\Pages\Admin\PageAdminPricing;
use App\View\Pages\Admin\PageAdminPromoCodes;
use App\View\Pages\Admin\PageAdminServers;
use App\View\Pages\Admin\PageAdminServices;
use App\View\Pages\Admin\PageAdminSettings;
use App\View\Pages\Admin\PageAdminSmsCodes;
use App\View\Pages\Admin\PageAdminTheme;
use App\View\Pages\Admin\PageAdminUpdateServers;
use App\View\Pages\Admin\PageAdminUpdateWeb;
use App\View\Pages\Admin\PageAdminUsers;
use App\View\Pages\Admin\PageAdminUserService;
use App\View\Pages\Shop\PageCashBillTransferFinalized;
use App\View\Pages\Shop\PageChangePassword;
use App\View\Pages\Shop\PageContact;
use App\View\Pages\Shop\PageForgottenPassword;
use App\View\Pages\Shop\PageLogin;
use App\View\Pages\Shop\PageMain;
use App\View\Pages\Shop\PagePayment;
use App\View\Pages\Shop\PagePaymentError;
use App\View\Pages\Shop\PagePaymentLog;
use App\View\Pages\Shop\PagePaymentSuccess;
use App\View\Pages\Shop\PagePayPalApproved;
use App\View\Pages\Shop\PagePrivacyPolicy;
use App\View\Pages\Shop\PageProfile;
use App\View\Pages\Shop\PagePurchase;
use App\View\Pages\Shop\PageRegister;
use App\View\Pages\Shop\PageRegulations;
use App\View\Pages\Shop\PageResetPassword;
use App\View\Pages\Shop\PageServer;
use App\View\Pages\Shop\PageServers;
use App\View\Pages\Shop\PageServices;
use App\View\Pages\Shop\PageTakeOverService;
use App\View\Pages\Shop\PageTPaySuccess;
use App\View\Pages\Shop\PageUserOwnServices;

class HeartServiceProvider
{
    public function register(Application $app)
    {
        $app->extend(PaymentModuleManager::class, function (PaymentModuleManager $manager) {
            $this->registerPaymentModules($manager);
            return $manager;
        });

        $app->extend(BlockManager::class, function (BlockManager $manager) {
            $this->registerBlocks($manager);
            return $manager;
        });

        $app->extend(PageManager::class, function (PageManager $manager) {
            $this->registerPages($manager);
            $this->registerAdminPages($manager);
            return $manager;
        });

        $app->extend(ServiceModuleManager::class, function (ServiceModuleManager $manager) {
            $this->registerServices($manager);
            return $manager;
        });
    }

    public function boot(Meta $meta)
    {
        $meta->load();
    }

    private function registerPaymentModules(PaymentModuleManager $paymentModuleManager)
    {
        $paymentModuleManager->register(CashBill::class);
        $paymentModuleManager->register(Cssetti::class);
        $paymentModuleManager->register(GetPay::class);
        $paymentModuleManager->register(Gosetti::class);
        $paymentModuleManager->register(Homepay::class);
        $paymentModuleManager->register(Hostplay::class);
        $paymentModuleManager->register(HotPay::class);
        $paymentModuleManager->register(MicroSMS::class);
        $paymentModuleManager->register(OneShotOneKill::class);
        $paymentModuleManager->register(PayPal::class);
        $paymentModuleManager->register(Profitsms::class);
        $paymentModuleManager->register(Pukawka::class);
        $paymentModuleManager->register(SimPay::class);
        $paymentModuleManager->register(TPay::class);
    }

    private function registerPages(PageManager $pageManager)
    {
        $pageManager->registerUser(PageCashBillTransferFinalized::class);
        $pageManager->registerUser(PageChangePassword::class);
        $pageManager->registerUser(PageContact::class);
        $pageManager->registerUser(PageForgottenPassword::class);
        $pageManager->registerUser(PageLogin::class);
        $pageManager->registerUser(PageMain::class);
        $pageManager->registerUser(PagePayPalApproved::class);
        $pageManager->registerUser(PagePayment::class);
        $pageManager->registerUser(PagePaymentError::class);
        $pageManager->registerUser(PagePaymentLog::class);
        $pageManager->registerUser(PagePaymentSuccess::class);
        $pageManager->registerUser(PageProfile::class);
        $pageManager->registerUser(PagePrivacyPolicy::class);
        $pageManager->registerUser(PagePurchase::class);
        $pageManager->registerUser(PageRegister::class);
        $pageManager->registerUser(PageRegulations::class);
        $pageManager->registerUser(PageResetPassword::class);
        $pageManager->registerUser(PageServices::class);
        $pageManager->registerUser(PageServer::class);
        $pageManager->registerUser(PageServers::class);
        $pageManager->registerUser(PageTPaySuccess::class);
        $pageManager->registerUser(PageTakeOverService::class);
        $pageManager->registerUser(PageUserOwnServices::class);
    }

    private function registerAdminPages(PageManager $pageManager)
    {
        $pageManager->registerAdmin(PageAdminBoughtServices::class);
        $pageManager->registerAdmin(PageAdminGroups::class);
        $pageManager->registerAdmin(PageAdminIncome::class);
        $pageManager->registerAdmin(PageAdminLogs::class);
        $pageManager->registerAdmin(PageAdminMain::class);
        $pageManager->registerAdmin(PageAdminPayments::class);
        $pageManager->registerAdmin(PageAdminPaymentPlatforms::class);
        $pageManager->registerAdmin(PageAdminPlayersFlags::class);
        $pageManager->registerAdmin(PageAdminPricing::class);
        $pageManager->registerAdmin(PageAdminPromoCodes::class);
        $pageManager->registerAdmin(PageAdminServers::class);
        $pageManager->registerAdmin(PageAdminServices::class);
        $pageManager->registerAdmin(PageAdminSettings::class);
        $pageManager->registerAdmin(PageAdminSmsCodes::class);
        $pageManager->registerAdmin(PageAdminTheme::class);
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
        $blockManager->register(BlockServersButtons::class);
        $blockManager->register(BlockUserButtons::class);
        $blockManager->register(BlockWallet::class);
    }

    private function registerServices(ServiceModuleManager $serviceModuleManager)
    {
        $serviceModuleManager->register(ChargeWalletServiceModule::class, "wallet_top_up");
        $serviceModuleManager->register(ExtraFlagsServiceModule::class, "extra_flags");
        $serviceModuleManager->register(MybbExtraGroupsServiceModule::class, "mybb_groups");
        $serviceModuleManager->register(OtherServiceModule::class, "other");
    }
}
