<?php
namespace App\View\Pages\Admin;

use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Support\FileSystem;
use App\Support\Path;
use App\Support\Template;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\Option;
use App\View\Html\Select;
use Symfony\Component\HttpFoundation\Request;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = "settings";

    /** @var Settings */
    private $settings;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $langShop;

    /** @var FileSystem */
    private $fileSystem;

    /** @var Path */
    private $path;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Settings $settings,
        PaymentPlatformRepository $paymentPlatformRepository,
        Heart $heart,
        FileSystem $fileSystem,
        Path $path
    ) {
        parent::__construct($template, $translationManager);

        $this->settings = $settings;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->heart = $heart;
        $this->langShop = $translationManager->shop();
        $this->fileSystem = $fileSystem;
        $this->path = $path;
    }

    public function getPrivilege()
    {
        return "manage_settings";
    }

    public function getTitle(Request $request)
    {
        return $this->lang->t("settings");
    }

    public function getContent(Request $request)
    {
        $smsPlatforms = [];
        $transferPlatforms = [];
        $directBillingPlatforms = [];

        foreach ($this->paymentPlatformRepository->all() as $paymentPlatform) {
            $paymentModule = $this->heart->getPaymentModule($paymentPlatform);

            if ($paymentModule instanceof SupportSms) {
                $smsPlatforms[] = $this->createPaymentPlatformOption(
                    $paymentPlatform,
                    $this->settings->getSmsPlatformId()
                );
            }

            if ($paymentModule instanceof SupportTransfer) {
                $transferPlatforms[] = $this->createPaymentPlatformOption(
                    $paymentPlatform,
                    $this->settings->getTransferPlatformId()
                );
            }

            if ($paymentModule instanceof SupportDirectBilling) {
                $directBillingPlatforms[] = $this->createPaymentPlatformOption(
                    $paymentPlatform,
                    $this->settings->getDirectBillingPlatformId()
                );
            }
        }

        $cronSelect = $this->createCronSelect();
        $userEditServiceSelect = $this->createUserEditServiceSelect();
        $themesList = to_array($this->createThemesList());
        $languagesList = to_array($this->createLanguagesList());
        $pageTitle = $this->template->render("admin/page_title", [
            "buttons" => "",
            "title" => $this->getTitle($request),
        ]);

        return $this->template->render("admin/settings", [
            "cronSelect" => $cronSelect,
            "directBillingPlatforms" => implode("", $directBillingPlatforms),
            "languagesList" => implode("", $languagesList),
            "pageTitle" => $pageTitle,
            "smsPlatforms" => implode("", $smsPlatforms),
            "themesList" => implode("", $themesList),
            "transferPlatforms" => implode("", $transferPlatforms),
            "userEditServiceSelect" => $userEditServiceSelect,
        ]);
    }

    private function createUserEditServiceSelect()
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

    private function createCronSelect()
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

        return (new Select())
            ->setParam("id", "cron")
            ->setParam("name", "cron")
            ->addContent($yesOption)
            ->addContent($noOption);
    }

    private function createPaymentPlatformOption(PaymentPlatform $paymentPlatform, $currentId)
    {
        return create_dom_element("option", $paymentPlatform->getName(), [
            "value" => $paymentPlatform->getId(),
            "selected" => $paymentPlatform->getId() === $currentId ? "selected" : "",
        ]);
    }

    private function createThemesList()
    {
        $dirList = $this->fileSystem->scanDirectory($this->path->to("themes"));

        foreach ($dirList as $dirName) {
            if (
                $dirName[0] != "." &&
                $this->fileSystem->isDirectory($this->path->to("themes/$dirName"))
            ) {
                yield create_dom_element("option", $dirName, [
                    "value" => $dirName,
                    "selected" => $dirName == $this->settings->getTheme() ? "selected" : "",
                ]);
            }
        }
    }

    private function createLanguagesList()
    {
        $dirList = $this->fileSystem->scanDirectory($this->path->to("translations"));

        foreach ($dirList as $dirName) {
            if (
                $dirName[0] != "." &&
                $this->fileSystem->isDirectory($this->path->to("translations/{$dirName}"))
            ) {
                yield create_dom_element("option", $this->lang->t("language_" . $dirName), [
                    "value" => $dirName,
                    "selected" =>
                        $dirName == $this->langShop->getCurrentLanguage() ? "selected" : "",
                ]);
            }
        }
    }
}
