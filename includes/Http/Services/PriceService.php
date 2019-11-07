<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PriceService
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(Heart $heart, TranslationManager $translationManager)
    {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
    }

    public function validateBody(array $body)
    {
        $service = $body['service'];
        $server = $body['server'];
        $tariff = $body['tariff'];
        $amount = $body['amount'];

        $warnings = [];

        // Usługa
        if (is_null($this->heart->getService($service))) {
            $warnings['service'][] = $this->lang->translate('no_such_service');
        }

        // Serwer
        if ($server != -1 && $this->heart->getServer($server) === null) {
            $warnings['server'][] = $this->lang->translate('no_such_server');
        }

        // Taryfa
        if ($this->heart->getTariff($tariff) === null) {
            $warnings['tariff'][] = $this->lang->translate('no_such_tariff');
        }

        // Ilość
        if ($warning = check_for_warnings("number", $amount)) {
            $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }
}
