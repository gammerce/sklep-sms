<?php
namespace App\Http\Services;

use App\Exceptions\ValidationException;
use App\Repositories\SmsPriceRepository;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PriceService
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    public function __construct(
        Heart $heart,
        SmsPriceRepository $smsPriceRepository,
        TranslationManager $translationManager
    ) {
        $this->heart = $heart;
        $this->smsPriceRepository = $smsPriceRepository;
        $this->lang = $translationManager->user();
    }

    public function validateBody(array $body)
    {
        $serviceId = array_get($body, 'service_id');
        $serverId = array_get($body, 'server');
        $smsPrice = array_get($body, 'sms_price');
        $quantity = array_get($body, 'quantity');

        $warnings = [];

        if ($this->heart->getService($serviceId) === null) {
            $warnings['service_id'][] = $this->lang->t('no_such_service');
        }

        if ($serverId != -1 && $this->heart->getServer($serverId) === null) {
            $warnings['server_id'][] = $this->lang->t('no_such_server');
        }

        if (in_array($smsPrice, $this->smsPriceRepository->all(), true)) {
            $warnings['sms_price'][] = $this->lang->t('no_such_sms_price');
        }

        if ($warning = check_for_warnings("number", $quantity)) {
            $warnings['quantity'] = array_merge((array) $warnings['quantity'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }
}
