<?php
namespace App\View\Pages\Admin;

use App\Managers\PaymentModuleManager;
use App\Models\PaymentPlatform;
use App\Repositories\PaymentPlatformRepository;
use App\Support\FileSystem;
use App\Support\Path;
use App\Support\Template;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\View\Html\DOMElement;
use App\View\Html\Option;
use App\View\Html\Select;
use Symfony\Component\HttpFoundation\Request;

class PageAdminSettings extends PageAdmin
{
    const PAGE_ID = "settings";

    private Settings $settings;
    private PaymentPlatformRepository $paymentPlatformRepository;
    private Translator $langShop;
    private FileSystem $fileSystem;
    private Path $path;
    private PaymentModuleManager $paymentModuleManager;

    public function __construct(
        Template $template,
        TranslationManager $translationManager,
        Settings $settings,
        PaymentPlatformRepository $paymentPlatformRepository,
        PaymentModuleManager $paymentModuleManager,
        FileSystem $fileSystem,
        Path $path
    ) {
        parent::__construct($template, $translationManager);

        $this->settings = $settings;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->langShop = $translationManager->shop();
        $this->fileSystem = $fileSystem;
        $this->path = $path;
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function getPrivilege(): Permission
    {
        return Permission::MANAGE_SETTINGS();
    }

    public function getTitle(Request $request): string
    {
        return $this->lang->t("settings");
    }

    public function getContent(Request $request)
    {
        $smsPlatforms = [];
        $transferPlatforms = [];
        $directBillingPlatforms = [];

        foreach ($this->paymentPlatformRepository->all() as $paymentPlatform) {
            $paymentModule = $this->paymentModuleManager->get($paymentPlatform);

            if ($paymentModule instanceof SupportSms) {
                $smsPlatforms[] = $this->createPaymentPlatformOption($paymentPlatform, [
                    $this->settings->getSmsPlatformId(),
                ]);
            }

            if ($paymentModule instanceof SupportTransfer) {
                $transferPlatforms[] = $this->createPaymentPlatformOption(
                    $paymentPlatform,
                    $this->settings->getTransferPlatformIds()
                );
            }

            if ($paymentModule instanceof SupportDirectBilling) {
                $directBillingPlatforms[] = $this->createPaymentPlatformOption($paymentPlatform, [
                    $this->settings->getDirectBillingPlatformId(),
                ]);
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

    private function createUserEditServiceSelect(): DOMElement
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

    private function createPaymentPlatformOption(
        PaymentPlatform $paymentPlatform,
        array $currentIds
    ): DOMElement {
        $selected = in_array($paymentPlatform->getId(), $currentIds);
        return new Option($paymentPlatform->getName(), $paymentPlatform->getId(), [
            "selected" => selected($selected),
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
                yield new Option($dirName, $dirName, [
                    "selected" => selected($dirName == $this->settings->getTheme()),
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
                yield new Option($this->lang->t("language_" . $dirName), $dirName, [
                    "selected" => selected($dirName == $this->langShop->getCurrentLanguage()),
                ]);
            }
        }
    }
}
