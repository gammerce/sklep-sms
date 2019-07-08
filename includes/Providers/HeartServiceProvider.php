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

    protected function registerPaymentModules(Heart $heart)
    {
        $heart->register_payment_module('1s1k', OneShotOneKill::class);
        $heart->register_payment_module('bizneshost', Bizneshost::class);
        $heart->register_payment_module('cashbill', Cashbill::class);
        $heart->register_payment_module('cssetti', Cssetti::class);
        $heart->register_payment_module('gosetti', Gosetti::class);
        $heart->register_payment_module('homepay', Homepay::class);
        $heart->register_payment_module('hostplay', Hostplay::class);
        $heart->register_payment_module('microsms', Microsms::class);
        $heart->register_payment_module('mintshost', Mintshost::class);
        $heart->register_payment_module('profitsms', Profitsms::class);
        $heart->register_payment_module('pukawka', Pukawka::class);
        $heart->register_payment_module('simpay', Simpay::class);
        $heart->register_payment_module('transferuj', Transferuj::class);
        $heart->register_payment_module('zabijaka', Zabijaka::class);
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
        $heart->register_page('user_own_services', PageUserOwnServices::class);
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
        $heart->register_page('user_service', PageAdminUserService::class, 'admin');
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
            ServiceChargeWalletSimple::MODULE_ID,
            'Doładowanie Portfela',
            ServiceChargeWallet::class,
            ServiceChargeWalletSimple::class
        );

        $heart->register_service_module(
            ServiceExtraFlagsSimple::MODULE_ID,
            'Dodatkowe Uprawnienia / Flagi',
            ServiceExtraFlags::class,
            ServiceExtraFlagsSimple::class
        );

        $heart->register_service_module(
            ServiceMybbExtraGroupsSimple::MODULE_ID,
            'Dodatkowe Grupy (MyBB)',
            ServiceMybbExtraGroups::class,
            ServiceMybbExtraGroupsSimple::class
        );

        $heart->register_service_module(
            ServiceOtherSimple::MODULE_ID,
            'Inne',
            ServiceOther::class,
            ServiceOtherSimple::class
        );
    }
}
