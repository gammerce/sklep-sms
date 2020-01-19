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
        $serverId = array_get($body, 'server_id');
        $quantity = array_get($body, 'quantity');

        if (strlen(array_get($body, 'sms_price'))) {
            $smsPrice = (int) array_get($body, 'sms_price');
        } else {
            $smsPrice = null;
        }

        if (strlen(array_get($body, 'transfer_price'))) {
            $transferPrice = array_get($body, 'transfer_price') * 100;
        } else {
            $transferPrice = null;
        }

        $warnings = [];

        if (!$this->heart->getService($serviceId)) {
            $warnings['service_id'][] = $this->lang->t('no_such_service');
        }

        if (strlen($serverId) && !$this->heart->getServer($serverId)) {
            $warnings['server_id'][] = $this->lang->t('no_such_server');
        }

        if (strlen($smsPrice) && !$this->smsPriceRepository->exists($smsPrice)) {
            $warnings['sms_price'][] = $this->lang->t('invalid_price');
        }

        if (strlen($transferPrice) && $transferPrice < 1) {
            $warnings['transfer_price'][] = $this->lang->t('invalid_price');
        }

        if ($warning = check_for_warnings("number", $quantity)) {
            $warnings['quantity'] = array_merge((array) $warnings['quantity'], $warning);
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }
    }
}
