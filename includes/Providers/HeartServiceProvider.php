<?php
namespace App\Providers;

use App\Blocks\BlockAdminContent;
use App\Blocks\BlockContent;
use App\Blocks\BlockLoggedInfo;
use App\Blocks\BlockServicesButtons;
use App\Blocks\BlockUserButtons;
use App\Blocks\BlockWallet;
use App\Pages\PageAdminAntispamQuestions;
use App\Pages\PageAdminBoughtServices;
use App\Pages\PageAdminGroups;
use App\Pages\PageAdminIncome;
use App\Pages\PageAdminLogs;
use App\Pages\PageAdminMain;
use App\Pages\PageAdminPaymentAdmin;
use App\Pages\PageAdminPaymentPlatforms;
use App\Pages\PageAdminPaymentServiceCode;
use App\Pages\PageAdminPaymentSms;
use App\Pages\PageAdminPaymentTransfer;
use App\Pages\PageAdminPaymentWallet;
use App\Pages\PageAdminPlayersFlags;
use App\Pages\PageAdminPriceList;
use App\Pages\PageAdminServers;
use App\Pages\PageAdminServiceCodes;
use App\Pages\PageAdminServices;
use App\Pages\PageAdminSettings;
use App\Pages\PageAdminSmsCodes;
use App\Pages\PageAdminTariffs;
use App\Pages\PageAdminUpdateServers;
use App\Pages\PageAdminUpdateWeb;
use App\Pages\PageAdminUsers;
use App\Pages\PageAdminUserService;
use App\Pages\PageCashbillTransferFinalized;
use App\Pages\PageChangePassword;
use App\Pages\PageContact;
use App\Pages\PageForgottenPassword;
use App\Pages\PageMain;
use App\Pages\PagePayment;
use App\Pages\PagePaymentLog;
use App\Pages\PageProfile;
use App\Pages\PagePurchase;
use App\Pages\PageRegister;
use App\Pages\PageRegulations;
use App\Pages\PageResetPassword;
use App\Pages\PageTakeOverService;
use App\Pages\PageTransferujBad;
use App\Pages\PageTransferujOk;
use App\Pages\PageUserOwnServices;
use App\Services\ChargeWallet\ServiceChargeWallet;
use App\Services\ExtraFlags\ServiceExtraFlags;
use App\Services\MybbExtraGroups\ServiceMybbExtraGroups;
use App\Services\Other\ServiceOther;
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

class HeartServiceProvider
{
    public function boot(Heart $heart)
    {
        $this->registerPaymentModules($heart);
        $this->registerPages($heart);
        $this->registerAdminPages($heart);
        $this->registerBlocks($heart);
        $this->registerServices($heart);
    }

    protected function registerPaymentModules(Heart $heart)
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

    protected function registerPages(Heart $heart)
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

    protected function registerAdminPages(Heart $heart)
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
        $heart->registerAdminPage(PageAdminPriceList::PAGE_ID, PageAdminPriceList::class);
        $heart->registerAdminPage(PageAdminServers::PAGE_ID, PageAdminServers::class);
        $heart->registerAdminPage(PageAdminServiceCodes::PAGE_ID, PageAdminServiceCodes::class);
        $heart->registerAdminPage(PageAdminServices::PAGE_ID, PageAdminServices::class);
        $heart->registerAdminPage(PageAdminSettings::PAGE_ID, PageAdminSettings::class);
        $heart->registerAdminPage(PageAdminSmsCodes::PAGE_ID, PageAdminSmsCodes::class);
        $heart->registerAdminPage(PageAdminTariffs::PAGE_ID, PageAdminTariffs::class);
        $heart->registerAdminPage(
            PageAdminPaymentPlatforms::PAGE_ID,
            PageAdminPaymentPlatforms::class
        );
        $heart->registerAdminPage(PageAdminUpdateServers::PAGE_ID, PageAdminUpdateServers::class);
        $heart->registerAdminPage(PageAdminUpdateWeb::PAGE_ID, PageAdminUpdateWeb::class);
        $heart->registerAdminPage(PageAdminUserService::PAGE_ID, PageAdminUserService::class);
        $heart->registerAdminPage(PageAdminUsers::PAGE_ID, PageAdminUsers::class);
    }

    protected function registerBlocks(Heart $heart)
    {
        $heart->registerBlock('admincontent', BlockAdminContent::class);
        $heart->registerBlock('content', BlockContent::class);
        $heart->registerBlock('logged_info', BlockLoggedInfo::class);
        $heart->registerBlock('services_buttons', BlockServicesButtons::class);
        $heart->registerBlock('user_buttons', BlockUserButtons::class);
        $heart->registerBlock('wallet', BlockWallet::class);
    }

    protected function registerServices(Heart $heart)
    {
        $heart->registerServiceModule(
            ServiceChargeWallet::MODULE_ID,
            'Doładowanie Portfela',
            ServiceChargeWallet::class
        );

        $heart->registerServiceModule(
            ServiceExtraFlags::MODULE_ID,
            'Dodatkowe Uprawnienia / Flagi',
            ServiceExtraFlags::class
        );

        $heart->registerServiceModule(
            ServiceMybbExtraGroups::MODULE_ID,
            'Dodatkowe Grupy (MyBB)',
            ServiceMybbExtraGroups::class
        );

        $heart->registerServiceModule(ServiceOther::MODULE_ID, 'Inne', ServiceOther::class);
    }
}
