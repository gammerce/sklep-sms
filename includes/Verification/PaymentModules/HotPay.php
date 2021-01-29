<?php
namespace App\Verification\PaymentModules;

use App\Loggers\FileLogger;
use App\Managers\ServiceManager;
use App\Models\FinalizedPayment;
use App\Models\PaymentPlatform;
use App\Models\Purchase;
use App\Payment\General\PaymentResult;
use App\Payment\General\PaymentResultType;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Support\Money;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\Abstracts\SupportSms;
use App\Verification\Abstracts\SupportTransfer;
use App\Verification\DataField;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Exceptions\BadNumberException;
use App\Verification\Exceptions\CustomErrorException;
use App\Verification\Exceptions\NoConnectionException;
use App\Verification\Results\SmsSuccessResult;
use Symfony\Component\HttpFoundation\Request;

/**
 * @link https://hotpay.pl/documentation_v3/tech_directbilling.pdf
 * @link https://hotpay.pl/documentation_v3/tech_paybylink.pdf
 * @link https://hotpay.pl/documentation_v3/tech_smspremium.pdf
 */
class HotPay extends PaymentModule implements SupportSms, SupportTransfer
{
    const MODULE_ID = "hotpay";

    private ServiceManager $serviceManager;

    public function __construct(
        Requester $requester,
        PaymentPlatform $paymentPlatform,
        UrlGenerator $url,
        FileLogger $fileLogger,
        ServiceManager $serviceManager
    ) {
        parent::__construct($requester, $paymentPlatform, $url, $fileLogger);
        $this->serviceManager = $serviceManager;
    }

    public static function getDataFields()
    {
        return [
            //            new DataField("sms_text"),
            //            new DataField("sms_secret"),
            new DataField("transfer_hash"),
            new DataField("transfer_secret"),
            //            new DataField("direct_billing_secret"),
        ];
    }

    public function getSmsNumbers()
    {
        return [
                // TODO Implement it
            ];
    }

    public function verifySms($returnCode, $number)
    {
        $response = $this->requester->get("https://apiv2.hotpay.pl/v1/sms/sprawdz", [
            "sekret" => $this->getSmsSecret(),
            "kod_sms" => $returnCode,
        ]);

        if (!$response) {
            throw new NoConnectionException();
        }

        $result = $response->json();
        $netValue = array_get($result, "netto");
        //        $grossValue = array_get($result, "brutto");
        $firstUsage = array_get($result, "aktywacja") === "1";
        $status = array_get($result, "status");
        $message = array_get($result, "tresc");

        if ($status === "SUKCESS") {
            if (!$firstUsage) {
                throw new BadCodeException();
            }

            if (!$this->isValidNumber($number, $netValue)) {
                throw new BadNumberException(Money::fromPrice($netValue));
            }

            return new SmsSuccessResult();
        }

        if ($status === "ERROR" && $message === "BLEDNA TRESC SMS") {
            throw new BadCodeException();
        }

        throw new CustomErrorException("{$status} {$message}");
    }

    private function isValidNumber($number, $netValue)
    {
        $numberMoney = get_sms_cost($number);
        $netMoney = Money::fromPrice($netValue);
        return $numberMoney->equal($netMoney);
    }

    public function prepareTransfer(Money $price, Purchase $purchase)
    {
        $service = $this->serviceManager->get($purchase->getServiceId());
        assert($service);

        return [
            "url" => "https://platnosc.hotpay.pl",
            "method" => "POST",
            "data" => [
                "SEKRET" => $this->getTransferSecret(),
                "KWOTA" => $price->asPrice(),
                "ID_ZAMOWIENIA" => $purchase->getId(),
                "NAZWA_USLUGI" => $service->getNameI18n(),
                "EMAIL" => $purchase->getEmail(),
                "ADRES_WWW" => $this->url->to("/page/payment_success"),
            ],
        ];
    }

    public function finalizeTransfer(Request $request)
    {
        $amount = Money::fromPrice($request->request->get("KWOTA"));

        return (new FinalizedPayment())
            ->setStatus($this->isTransferValid($request))
            ->setOrderId($request->request->get("ID_PLATNOSCI"))
            ->setCost($amount)
            ->setIncome($amount)
            ->setTransactionId($request->request->get("ID_ZAMOWIENIA"))
            ->setTestMode(false);
    }

    public function prepareDirectBilling($price, Purchase $purchase)
    {
        return new PaymentResult(PaymentResultType::EXTERNAL(), [
            "method" => "POST",
            "url" => "https://directbilling.hotpay.pl",
            "data" => [
                "SEKRET" => $this->getDirectBillingSecret(),
                "KWOTA" => $price / 100,
                "PRZEKIEROWANIE_SUKCESS" => $this->url->to("/page/payment_success"),
                "PRZEKIEROWANIE_BLAD" => $this->url->to("/page/payment_error"),
                "ID_ZAMOWIENIA" => $purchase->getId(),
            ],
        ]);
    }

    public function finalizeDirectBilling(Request $request)
    {
        // TODO cost should not be equal income
        $cost = Money::fromPrice($request->request->get("KWOTA"));
        $income = Money::fromPrice($request->request->get("KWOTA"));

        return (new FinalizedPayment())
            ->setStatus($this->isDirectBillingValid($request))
            ->setOrderId($request->request->get("ID_PLATNOSCI"))
            ->setCost($cost)
            ->setIncome($income)
            ->setTransactionId($request->request->get("ID_ZAMOWIENIA"))
            ->setTestMode(false);
    }

    public function getSmsCode()
    {
        return $this->getData("sms_text");
    }

    private function getSmsSecret()
    {
        return $this->getData("sms_secret");
    }

    private function getTransferSecret()
    {
        return $this->getData("transfer_secret");
    }

    private function getDirectBillingSecret()
    {
        return $this->getData("direct_billing_secret");
    }

    private function getTransferHash()
    {
        return $this->getData("transfer_hash");
    }

    private function isTransferValid(Request $request)
    {
        $hashElements = [
            $this->getTransferHash(),
            $request->request->get("KWOTA"),
            $request->request->get("ID_PLATNOSCI"),
            $request->request->get("ID_ZAMOWIENIA"),
            $request->request->get("STATUS"),
            $request->request->get("SEKRET"),
        ];
        $hash = hash("sha256", implode(";", $hashElements));

        return $hash === $request->request->get("HASH") &&
            $request->request->get("STATUS") === "SUCCESS";
    }

    private function isDirectBillingValid(Request $request)
    {
        // TODO Improve it
        return $request->request->get("STATUS") == 1;
    }
}
