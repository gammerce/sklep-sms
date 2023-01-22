<?php
namespace App\Verification\PaymentModules;

use App\Models\FinalizedPayment;
use App\Models\Purchase;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by MilyGosc.
 * @see https://forum.sklep-sms.pl/showthread.php?tid=88
 */
class TPay extends PaymentModule implements SupportTransfer
{
    const MODULE_ID = "transferuj";

    public static function getDataFields(): array
    {
        return [new DataField("key"), new DataField("account_id")];
    }

    public function prepareTransfer(Money $price, Purchase $purchase): array
    {
        $crc = $purchase->getId();

        return [
            "url" => "https://secure.tpay.com",
            "method" => "POST",
            "data" => [
                "id" => $this->getAccountId(),
                "kwota" => $price->asPrice(),
                "opis" => $purchase->getTransferDescription(),
                "crc" => $crc,
                "md5sum" => $this->calculateMD5($price->asPrice(), $crc),
                "imie" => $purchase->user->getForename(),
                "nazwisko" => $purchase->user->getSurname(),
                "email" => $purchase->getEmail(),
                "pow_url" => $this->url->to("/page/tpay_success"),
                "pow_url_blad" => $this->url->to("/page/payment_error"),
                "wyn_url" => $this->url->to("/api/ipn/transfer/{$this->paymentPlatform->getId()}"),
            ],
        ];
    }

    public function finalizeTransfer(Request $request): FinalizedPayment
    {
        // e.g. "40.80"
        $amount = Money::fromPrice($request->request->get("tr_amount"));

        return (new FinalizedPayment())
            ->setStatus($this->isPaymentValid($request))
            ->setOrderId($request->request->get("tr_id"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($request->request->get("tr_crc"))
            ->setExternalServiceId($request->request->get("id"))
            ->setTestMode($request->request->get("test_mode", false))
            ->setOutput("TRUE");
    }

    private function isPaymentValid(Request $request): bool
    {
        $isMd5Valid = $this->isMd5Valid(
            $request->request->get("md5sum"),
            number_format($request->request->get("tr_amount"), 2, ".", ""),
            $request->request->get("tr_crc"),
            $request->request->get("tr_id")
        );

        return $isMd5Valid &&
            $request->request->get("tr_status") === "TRUE" &&
            $request->request->get("tr_error") === "none";
    }

    private function isMd5Valid($md5sum, $transactionAmount, $crc, $transactionId): bool
    {
        if (!is_string($md5sum) || strlen($md5sum) !== 32) {
            return false;
        }

        $sign = md5(
            $this->getAccountId() . $transactionId . $transactionAmount . $crc . $this->getKey()
        );

        return $md5sum === $sign;
    }

    private function calculateMD5(string $price, string $crc): string
    {
        $parts = [$this->getAccountId(), $price, $crc, $this->getKey()];
        $joined = collect($parts)->join("&");
        return md5($joined);
    }

    private function getKey(): string
    {
        return (string) $this->getData("key");
    }

    private function getAccountId(): string
    {
        return (string) $this->getData("account_id");
    }
}
