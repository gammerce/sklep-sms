<?php
namespace App\Services;

use App\Models\Service;
use App\Support\Money;
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
     * @param Money|null $price
     * @return string
     */
    public function getPriceGrossText(Money $price)
    {
        if ($price === null) {
            return null;
        }

        $grossValue = ($price->asInt() / 100.0) * $this->settings->getVat();
        return number_format($grossValue, 2) . " " . $this->settings->getCurrency();
    }

    /**
     * @param Money|int|null $price
     * @return string
     */
    public function getPriceText($price)
    {
        if ($price === null) {
            return null;
        }

        if ($price instanceof Money) {
            return $price->asPrice() . " " . $this->settings->getCurrency();
        }

        return number_format($price / 100.0, 2) . " " . $this->settings->getCurrency();
    }

    /**
     * @param int|null $price
     * @return string
     */
    public function getPlainPrice($price)
    {
        return $price !== null ? number_format($price / 100.0, 2) : null;
    }

    /**
     * @param Money|null $price
     * @return string
     */
    public function getPlainPriceGross(Money $price)
    {
        if ($price === null) {
            return null;
        }

        $grossValue = ($price->asInt() / 100.0) * $this->settings->getVat();
        return number_format($grossValue, 2);
    }

    /**
     * @param int|null $quantity
     * @param Service $service
     * @return string
     */
    public function getQuantityText($quantity, Service $service)
    {
        return $quantity === null || $quantity === -1
            ? $this->lang->t('forever')
            : $quantity . " " . $service->getTag();
    }
}
