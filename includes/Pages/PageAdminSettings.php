<?php
namespace App\Pages;

use App\Html\Option;
use App\Html\Select;
use App\Repositories\PaymentPlatformRepository;
use App\System\Heart;
use App\System\Path;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = 'settings';
    protected $privilege = 'manage_settings';

    public function __construct()
    {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->translate('settings');
    }

    protected function content(array $query, array $body)
    {
        /** @var PaymentPlatformRepository $paymentPlatformRepository */
        $paymentPlatformRepository = $this->app->make(PaymentPlatformRepository::class);

        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var Path $path */
        $path = $this->app->make(Path::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $this->lang;
        $langShop = $translationManager->shop();

        $smsPlatforms = [];
        $transferPlatforms = [];
        foreach ($paymentPlatformRepository->all() as $paymentPlatform) {
            $paymentModule = $heart->getPaymentModule($paymentPlatform);

            if ($paymentModule instanceof SupportSms) {
                $smsPlatforms[] = create_dom_element("option", $paymentPlatform->getName(), [
                    'value' => $paymentPlatform->getId(),
                    'selected' =>
                        $paymentPlatform->getId() == $this->settings['sms_platform']
                            ? "selected"
                            : "",
                ]);
            }

            if ($paymentModule instanceof SupportTransfer) {
                $transferPlatforms[] = create_dom_element("option", $paymentPlatform->getName(), [
                    'value' => $paymentPlatform->getId(),
                    'selected' =>
                        $paymentPlatform->getId() == $this->settings['transfer_platform']
                            ? "selected"
                            : "",
                ]);
            }
        }

        $cronSelect = $this->createCronSelect();
        $userEditServiceSelect = $this->createUserEditServiceSelect();

        // Available themes
        $dirList = scandir($path->to('themes'));
        $themesList = [];
        foreach ($dirList as $dirName) {
            if ($dirName[0] != '.' && is_dir($path->to("themes/$dirName"))) {
                $themesList[] = create_dom_element("option", $dirName, [
                    'value' => $dirName,
                    'selected' => $dirName == $this->settings['theme'] ? "selected" : "",
                ]);
            }
        }

        // Available languages
        $dirList = scandir($path->to('translations'));
        $languagesList = [];
        foreach ($dirList as $dirName) {
            if ($dirName[0] != '.' && is_dir($path->to("translations/{$dirName}"))) {
                $languagesList[] = create_dom_element(
                    "option",
                    $lang->translate('language_' . $dirName),
                    [
                        'value' => $dirName,
                        'selected' => $dirName == $langShop->getCurrentLanguage() ? "selected" : "",
                    ]
                );
            }
        }

        return $this->template->render(
            "admin/settings",
            compact("userEditServiceSelect", "cronSelect") + [
                "title" => $this->title,
                "smsPlatforms" => implode("", $smsPlatforms),
                "transferPlatforms" => implode("", $transferPlatforms),
                "themesList" => implode("", $themesList),
                "languagesList" => implode("", $languagesList),
            ]
        );
    }

    protected function createUserEditServiceSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["user_edit_service"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["user_edit_service"]) {
            $noOption->setParam("selected", "selected");
        }

        $userEditServiceSelect = new Select();
        $userEditServiceSelect->setParam("id", "user_edit_service");
        $userEditServiceSelect->setParam("name", "user_edit_service");
        $userEditServiceSelect->addContent($yesOption);
        $userEditServiceSelect->addContent($noOption);

        return $userEditServiceSelect;
    }

    protected function createCronSelect()
    {
        $yesOption = new Option($this->lang->translate("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["cron_each_visit"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->translate("no"));
        $noOption->setParam("value", "0");
        if (!$this->settings["cron_each_visit"]) {
            $noOption->setParam("selected", "selected");
        }

        $cronSelect = new Select();
        $cronSelect->setParam("id", "cron");
        $cronSelect->setParam("name", "cron");
        $cronSelect->addContent($yesOption);
        $cronSelect->addContent($noOption);

        return $cronSelect;
    }
}
