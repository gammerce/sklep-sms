<?php
namespace App\Providers;

use App\Blocks\BlockAdminContent;
use App\Blocks\BlockContent;
use App\Blocks\BlockLoggedInfo;
use App\Blocks\BlockServicesButtons;
use App\Blocks\BlockUserButtons;
use App\Blocks\BlockWallet;
use App\Heart;
use App\Pages\PageAdminAntispamQuestions;
use App\Pages\PageAdminBoughtServices;
use App\Pages\PageAdminGroups;
use App\Pages\PageAdminIncome;
use App\Pages\PageAdminLogs;
use App\Pages\PageAdminMain;
use App\Pages\PageAdminPaymentAdmin;
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
use App\Pages\PageAdminTransactionServices;
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
use App\Services\ExtraFlags\ServiceExtraFlagsSimple;
use App\Services\MybbExtraGroups\ServiceMybbExtraGroups;
use App\Services\MybbExtraGroups\ServiceMybbExtraGroupsSimple;
use App\Services\Other\ServiceOther;
use App\Services\Other\ServiceOtherSimple;
use App\Verification\Bizneshost;
use App\Verification\Cashbill;
use App\Verification\Cssetti;
use App\Verification\Gosetti;
use App\Verification\Homepay;
use App\Verification\Hostplay;
use App\Verification\Microsms;
use App\Verification\Mintshost;
use App\Verification\OneShotOneKill;
use App\Verification\Profitsms;
use App\Verification\Pukawka;
use App\Verification\Simpay;
use App\Verification\Transferuj;
use App\Verification\Zabijaka;
use App\Services\ChargeWallet\ServiceChargeWalletSimple;

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

    // TODO Get id from class

    protected function registerPaymentModules(Heart $heart)
    {
        $heart->registerPaymentModule('1s1k', OneShotOneKill::class);
        $heart->registerPaymentModule('bizneshost', Bizneshost::class);
        $heart->registerPaymentModule('cashbill', Cashbill::class);
        $heart->registerPaymentModule('cssetti', Cssetti::class);
        $heart->registerPaymentModule('gosetti', Gosetti::class);
        $heart->registerPaymentModule('homepay', Homepay::class);
        $heart->registerPaymentModule('hostplay', Hostplay::class);
        $heart->registerPaymentModule('microsms', Microsms::class);
        $heart->registerPaymentModule('mintshost', Mintshost::class);
        $heart->registerPaymentModule('profitsms', Profitsms::class);
        $heart->registerPaymentModule('pukawka', Pukawka::class);
        $heart->registerPaymentModule('simpay', Simpay::class);
        $heart->registerPaymentModule('transferuj', Transferuj::class);
        $heart->registerPaymentModule('zabijaka', Zabijaka::class);
    }

    protected function registerPages(Heart $heart)
    {
        $heart->registerPage('transfer_finalized', PageCashbillTransferFinalized::class);
        $heart->registerPage('change_password', PageChangePassword::class);
        $heart->registerPage('contact', PageContact::class);
        $heart->registerPage('forgotten_password', PageForgottenPassword::class);
        $heart->registerPage('home', PageMain::class);
        $heart->registerPage('payment', PagePayment::class);
        $heart->registerPage('payment_log', PagePaymentLog::class);
        $heart->registerPage('purchase', PagePurchase::class);
        $heart->registerPage('register', PageRegister::class);
        $heart->registerPage('regulations', PageRegulations::class);
        $heart->registerPage('reset_password', PageResetPassword::class);
        $heart->registerPage('service_take_over', PageTakeOverService::class);
        $heart->registerPage('transferuj_bad', PageTransferujBad::class);
        $heart->registerPage('transferuj_ok', PageTransferujOk::class);
        $heart->registerPage('user_own_services', PageUserOwnServices::class);
    }

    protected function registerAdminPages(Heart $heart)
    {
        $heart->registerPage('antispam_questions', PageAdminAntispamQuestions::class, 'admin');
        $heart->registerPage('bought_services', PageAdminBoughtServices::class, 'admin');
        $heart->registerPage('groups', PageAdminGroups::class, 'admin');
        $heart->registerPage('home', PageAdminMain::class, 'admin');
        $heart->registerPage('income', PageAdminIncome::class, 'admin');
        $heart->registerPage('logs', PageAdminLogs::class, 'admin');
        $heart->registerPage('payment_admin', PageAdminPaymentAdmin::class, 'admin');
        $heart->registerPage('payment_service_code', PageAdminPaymentServiceCode::class, 'admin');
        $heart->registerPage('payment_sms', PageAdminPaymentSms::class, 'admin');
        $heart->registerPage('payment_transfer', PageAdminPaymentTransfer::class, 'admin');
        $heart->registerPage('payment_wallet', PageAdminPaymentWallet::class, 'admin');
        $heart->registerPage('players_flags', PageAdminPlayersFlags::class, 'admin');
        $heart->registerPage('pricelist', PageAdminPriceList::class, 'admin');
        $heart->registerPage('servers', PageAdminServers::class, 'admin');
        $heart->registerPage('service_codes', PageAdminServiceCodes::class, 'admin');
        $heart->registerPage('services', PageAdminServices::class, 'admin');
        $heart->registerPage('settings', PageAdminSettings::class, 'admin');
        $heart->registerPage('sms_codes', PageAdminSmsCodes::class, 'admin');
        $heart->registerPage('tariffs', PageAdminTariffs::class, 'admin');
        $heart->registerPage('transaction_services', PageAdminTransactionServices::class, 'admin');
        $heart->registerPage('update_servers', PageAdminUpdateServers::class, 'admin');
        $heart->registerPage('update_web', PageAdminUpdateWeb::class, 'admin');
        $heart->registerPage('user_service', PageAdminUserService::class, 'admin');
        $heart->registerPage('users', PageAdminUsers::class, 'admin');
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
            ServiceChargeWalletSimple::MODULE_ID,
            'Doładowanie Portfela',
            ServiceChargeWallet::class,
            ServiceChargeWalletSimple::class
        );

        $heart->registerServiceModule(
            ServiceExtraFlagsSimple::MODULE_ID,
            'Dodatkowe Uprawnienia / Flagi',
            ServiceExtraFlags::class,
            ServiceExtraFlagsSimple::class
        );

        $heart->registerServiceModule(
            ServiceMybbExtraGroupsSimple::MODULE_ID,
            'Dodatkowe Grupy (MyBB)',
            ServiceMybbExtraGroups::class,
            ServiceMybbExtraGroupsSimple::class
        );

        $heart->registerServiceModule(
            ServiceOtherSimple::MODULE_ID,
            'Inne',
            ServiceOther::class,
            ServiceOtherSimple::class
        );
    }
}
