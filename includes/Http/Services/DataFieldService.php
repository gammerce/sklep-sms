<?php
namespace App\Http\Services;

use App\Managers\PaymentModuleManager;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class DataFieldService
{
    private Template $template;
    private Translator $lang;
    private PaymentModuleManager $paymentModuleManager;

    public function __construct(
        PaymentModuleManager $paymentModuleManager,
        Template $template,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->lang = $translationManager->user();
        $this->paymentModuleManager = $paymentModuleManager;
    }

    public function renderDataFields($moduleId, array $data)
    {
        $dataFields = $this->paymentModuleManager->dataFields($moduleId);

        $dataFieldOptions = [];
        foreach ($dataFields as $dataField) {
            $text = $dataField->getName() ?: $this->getCustomDataText($dataField->getId());
            $value = array_get($data, $dataField->getId());

            $dataFieldOptions[] = $this->template->render("admin/tr_name_input", [
                "name" => "data[{$dataField->getId()}]",
                "value" => $value,
                "text" => $text,
            ]);
        }

        return implode("", $dataFieldOptions);
    }

    private function getCustomDataText($name)
    {
        switch ($name) {
            case "sms_text":
                return to_upper($this->lang->t("sms_code"));
            case "account_id":
                return to_upper($this->lang->t("account_id"));
            default:
                return to_upper($name);
        }
    }
}
