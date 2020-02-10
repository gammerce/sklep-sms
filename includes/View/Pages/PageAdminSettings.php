<?php
namespace App\View\Pages;

use App\Repositories\PaymentPlatformRepository;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\Option;
use App\View\Html\Select;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = 'settings';
    protected $privilege = 'manage_settings';

    /** @var Settings */
    private $settings;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct(
        Settings $settings,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        parent::__construct();

        $this->heart->pageTitle = $this->title = $this->lang->t('settings');
        $this->settings = $settings;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    protected function content(array $query, array $body)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $langShop = $translationManager->shop();

        $smsPlatforms = [];
        $transferPlatforms = [];
        foreach ($this->paymentPlatformRepository->all() as $paymentPlatform) {
            $paymentModule = $this->heart->getPaymentModule($paymentPlatform);

            if ($paymentModule instanceof SupportSms) {
                $smsPlatforms[] = create_dom_element("option", $paymentPlatform->getName(), [
                    'value' => $paymentPlatform->getId(),
                    'selected' =>
                        $paymentPlatform->getId() === $this->settings->getSmsPlatformId()
                            ? "selected"
                            : "",
                ]);
            }

            if ($paymentModule instanceof SupportTransfer) {
                $transferPlatforms[] = create_dom_element("option", $paymentPlatform->getName(), [
                    'value' => $paymentPlatform->getId(),
                    'selected' =>
                        $paymentPlatform->getId() === $this->settings->getTransferPlatformId()
                            ? "selected"
                            : "",
                ]);
            }
        }

        $cronSelect = $this->createCronSelect();
        $userEditServiceSelect = $this->createUserEditServiceSelect();

        // Available themes
        $dirList = $this->fileSystem->scanDirectory($this->path->to('themes'));
        $themesList = [];
        foreach ($dirList as $dirName) {
            if (
                $dirName[0] != '.' &&
                $this->fileSystem->isDirectory($this->path->to("themes/$dirName"))
            ) {
                $themesList[] = create_dom_element("option", $dirName, [
                    'value' => $dirName,
                    'selected' => $dirName == $this->settings->getTheme() ? "selected" : "",
                ]);
            }
        }

        // Available languages
        $dirList = $this->fileSystem->scanDirectory($this->path->to('translations'));
        $languagesList = [];
        foreach ($dirList as $dirName) {
            if (
                $dirName[0] != '.' &&
                $this->fileSystem->isDirectory($this->path->to("translations/{$dirName}"))
            ) {
                $languagesList[] = create_dom_element(
                    "option",
                    $this->lang->t('language_' . $dirName),
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
        $yesOption = new Option($this->lang->t("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["user_edit_service"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->t("no"));
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
        $yesOption = new Option($this->lang->t("yes"));
        $yesOption->setParam("value", "1");
        if ($this->settings["cron_each_visit"]) {
            $yesOption->setParam("selected", "selected");
        }

        $noOption = new Option($this->lang->t("no"));
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
