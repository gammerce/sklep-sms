<?php
namespace App\Services;

use App\Models\Service;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PriceTextService
{
    /** @var Settings */
    private $settings;

    /** @var Translator */
    private $lang;

    public function __construct(Settings $settings, TranslationManager $translationManager)
    {
        $this->settings = $settings;
        $this->lang = $translationManager->user();
    }

    /**
     * @param int|null $price
     * @return string
     */
    public function getSmsGrossText($price)
    {
        return $price !== null
            ? number_format(($price / 100) * $this->settings->getVat(), 2)
            : null;
    }

    /**
     * @param int|null $price
     * @return string
     */
    public function getSmsNetText($price)
    {
        return $price !== null ? number_format($price / 100, 2) : null;
    }

    /**
     * @param int|null $price
     * @return string
     */
    public function getSmsText($price)
    {
        return "{$this->getSmsNetText($price)} + VAT ({$this->getSmsGrossText($price)})";
    }

    /**
     * @param int|null $price
     * @return string
     */
    public function getTransferText($price)
    {
        return $price !== null ? number_format($price / 100, 2) : null;
    }

    /**
     * @param int|null $quantity
     * @param Service $service
     * @return string
     */
    public function getQuantityText($quantity, Service $service)
    {
        return $quantity === null
            ? $this->lang->t('forever')
            : $quantity . " " . $service->getTag();
    }
}
