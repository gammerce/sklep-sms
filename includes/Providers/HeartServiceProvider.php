<?php
namespace App\Providers;

use App\Heart;
use BlockAdminContent;
use BlockContent;
use BlockLoggedInfo;
use BlockServicesButtons;
use BlockUserButtons;
use BlockWallet;
use Page_UserOIwnServices;
use PageAdmin_UserService;
use PageAdminAntispamQuestions;
use PageAdminBoughtServices;
use PageAdminGroups;
use PageAdminIncome;
use PageAdminLogs;
use PageAdminMain;
use PageAdminPaymentAdmin;
use PageAdminPaymentServiceCode;
use PageAdminPaymentSms;
use PageAdminPaymentTransfer;
use PageAdminPaymentWallet;
use PageAdminPlayersFlags;
use PageAdminPriceList;
use PageAdminServers;
use PageAdminServiceCodes;
use PageAdminServices;
use PageAdminSettings;
use PageAdminSmsCodes;
use PageAdminTariffs;
use PageAdminTransactionServices;
use PageAdminUpdateServers;
use PageAdminUpdateWeb;
use PageAdminUsers;
use PageCashbillTransferFinalized;
use PageChangePassword;
use PageContact;
use PageForgottenPassword;
use PageMain;
use PagePayment;
use PagePaymentLog;
use PagePurchase;
use PageRegister;
use PageRegulations;
use PageResetPassword;
use PageTakeOverService;
use PageTransferujBad;
use PageTransferujOk;
use PaymentModule_1s1k;
use PaymentModule_Bizneshost;
use PaymentModule_Cashbill;
use PaymentModule_Cssetti;
use PaymentModule_Gosetti;
use PaymentModuleMicrosms;
use PaymentModule_Mintshost;
use PaymentModule_Profitsms;
use PaymentModule_Pukawka;
use PaymentModule_Simpay;
use PaymentModule_Zabijaka;
use PaymentModuleHomepay;
use PaymentModuleHostplay;
use PaymentModuleTransferuj;
use ServiceChargeWallet;
use ServiceChargeWalletSimple;
use ServiceExtraFlags;
use ServiceExtraFlagsSimple;
use ServiceMybbExtraGroups;
use ServiceMybbExtraGroupsSimple;
use ServiceOther;
use ServiceOtherSimple;

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
        $heart->register_payment_module('1s1k', PaymentModule_1s1k::class);
        $heart->register_payment_module('bizneshost', PaymentModule_Bizneshost::class);
        $heart->register_payment_module('cashbill', PaymentModule_Cashbill::class);
        $heart->register_payment_module('cssetti', PaymentModule_Cssetti::class);
        $heart->register_payment_module('gosetti', PaymentModule_Gosetti::class);
        $heart->register_payment_module('homepay', PaymentModuleHomepay::class);
        $heart->register_payment_module('hostplay', PaymentModuleHostplay::class);
        $heart->register_payment_module('microsms', PaymentModuleMicrosms::class);
        $heart->register_payment_module('mintshost', PaymentModule_Mintshost::class);
        $heart->register_payment_module('profitsms', PaymentModule_Profitsms::class);
        $heart->register_payment_module('pukawka', PaymentModule_Pukawka::class);
        $heart->register_payment_module('simpay', PaymentModule_Simpay::class);
        $heart->register_payment_module('transferuj', PaymentModuleTransferuj::class);
        $heart->register_payment_module('zabijaka', PaymentModule_Zabijaka::class);
    }

    protected function registerPages(Heart $heart)
    {
        $heart->register_page('transfer_finalized', PageCashbillTransferFinalized::class);
        $heart->register_page('change_password', PageChangePassword::class);
        $heart->register_page('contact', PageContact::class);
        $heart->register_page('forgotten_password', PageForgottenPassword::class);
        $heart->register_page('home', PageMain::class);
        $heart->register_page('payment', PagePayment::class);
        $heart->register_page('payment_log', PagePaymentLog::class);
        $heart->register_page('purchase', PagePurchase::class);
        $heart->register_page('register', PageRegister::class);
        $heart->register_page('regulations', PageRegulations::class);
        $heart->register_page('reset_password', PageResetPassword::class);
        $heart->register_page('service_take_over', PageTakeOverService::class);
        $heart->register_page('transferuj_bad', PageTransferujBad::class);
        $heart->register_page('transferuj_ok', PageTransferujOk::class);
        $heart->register_page('user_own_services', Page_UserOIwnServices::class);
    }

    protected function registerAdminPages(Heart $heart)
    {
        $heart->register_page('antispam_questions', PageAdminAntispamQuestions::class, 'admin');
        $heart->register_page('bought_services', PageAdminBoughtServices::class, 'admin');
        $heart->register_page('groups', PageAdminGroups::class, 'admin');
        $heart->register_page('home', PageAdminMain::class, 'admin');
        $heart->register_page('income', PageAdminIncome::class, 'admin');
        $heart->register_page('logs', PageAdminLogs::class, 'admin');
        $heart->register_page('payment_admin', PageAdminPaymentAdmin::class, 'admin');
        $heart->register_page('payment_service_code', PageAdminPaymentServiceCode::class, 'admin');
        $heart->register_page('payment_sms', PageAdminPaymentSms::class, 'admin');
        $heart->register_page('payment_transfer', PageAdminPaymentTransfer::class, 'admin');
        $heart->register_page('payment_wallet', PageAdminPaymentWallet::class, 'admin');
        $heart->register_page('players_flags', PageAdminPlayersFlags::class, 'admin');
        $heart->register_page('pricelist', PageAdminPriceList::class, 'admin');
        $heart->register_page('servers', PageAdminServers::class, 'admin');
        $heart->register_page('service_codes', PageAdminServiceCodes::class, 'admin');
        $heart->register_page('services', PageAdminServices::class, 'admin');
        $heart->register_page('settings', PageAdminSettings::class, 'admin');
        $heart->register_page('sms_codes', PageAdminSmsCodes::class, 'admin');
        $heart->register_page('tariffs', PageAdminTariffs::class, 'admin');
        $heart->register_page('transaction_services', PageAdminTransactionServices::class, 'admin');
        $heart->register_page('update_servers', PageAdminUpdateServers::class, 'admin');
        $heart->register_page('update_web', PageAdminUpdateWeb::class, 'admin');
        $heart->register_page('user_service', PageAdmin_UserService::class, 'admin');
        $heart->register_page('users', PageAdminUsers::class, 'admin');
    }

    protected function registerBlocks(Heart $heart)
    {
        $heart->register_block('admincontent', BlockAdminContent::class);
        $heart->register_block('content', BlockContent::class);
        $heart->register_block('logged_info', BlockLoggedInfo::class);
        $heart->register_block('services_buttons', BlockServicesButtons::class);
        $heart->register_block('user_buttons', BlockUserButtons::class);
        $heart->register_block('wallet', BlockWallet::class);
    }

    protected function registerServices(Heart $heart)
    {
        $heart->register_service_module(
            'charge_wallet', 'Doładowanie Portfela', ServiceChargeWallet::class, ServiceChargeWalletSimple::class
        );

        $heart->register_service_module(
            'extra_flags', 'Dodatkowe Uprawnienia / Flagi', ServiceExtraFlags::class, ServiceExtraFlagsSimple::class
        );

        $heart->register_service_module(
            'mybb_extra_groups',
            'Dodatkowe Grupy (MyBB)',
            ServiceMybbExtraGroups::class,
            ServiceMybbExtraGroupsSimple::class
        );

        $heart->register_service_module('other', 'Inne', ServiceOther::class, ServiceOtherSimple::class);
    }
}
