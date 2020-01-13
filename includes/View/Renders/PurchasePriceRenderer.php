<?php
namespace App\View\Renders;

use App\Models\Service;
use App\System\Settings;
use App\System\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PurchasePriceRenderer
{
    /** @var Settings */
    private $settings;

    /** @var Template */
    private $template;

    /** @var Translator */
    private $lang;

    public function __construct(
        Settings $settings,
        TranslationManager $translationManager,
        Template $template
    ) {
        $this->settings = $settings;
        $this->template = $template;
        $this->lang = $translationManager->user();
    }

    public function render(array $price, Service $service)
    {
        $smsPrice =
            $price['sms_price'] !== null
                ? number_format(($price['sms_price'] / 100) * $this->settings->getVat(), 2)
                : null;

        $transferPrice =
            $price['transfer_price'] !== null
                ? number_format($price['transfer_price'] / 100, 2)
                : null;

        $quantity =
            $price['quantity'] === null
                ? $this->lang->t('forever')
                : $price['quantity'] . " " . $service->getTag();

        return $this->template->renderNoComments(
            "purchase/purchase_price",
            compact('priceId', 'quantity', 'smsPrice', 'transferPrice')
        );
    }
}
