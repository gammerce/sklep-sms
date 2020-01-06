<?php
namespace App\Http\Services;

use App\System\Heart;
use App\System\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class DataFieldService
{
    /** @var Heart */
    private $heart;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    public function __construct(
        Heart $heart,
        Template $template,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->template = $template;
        $this->lang = $translationManager->user();
    }

    public function renderDataFields($moduleId, array $data)
    {
        $dataFields = $this->heart->getPaymentModuleDataFields($moduleId);

        $dataFieldOptions = [];
        foreach ($dataFields as $dataField) {
            $text = $dataField->getName() ?: $this->getCustomDataText($dataField->getId());
            $value = array_get($data, $dataField->getId());

            $dataFieldOptions[] = $this->template->render("tr_name_input", [
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
            case 'sms_text':
                return $this->lang->strtoupper($this->lang->t('sms_code'));
            case 'account_id':
                return $this->lang->strtoupper($this->lang->t('account_id'));
            default:
                return $this->lang->strtoupper($name);
        }
    }
}
