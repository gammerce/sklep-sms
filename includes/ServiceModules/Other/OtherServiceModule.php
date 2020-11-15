<?php
namespace App\ServiceModules\Other;

use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServerLinkedToServiceRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\General\BoughtServiceService;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\ServiceModules\ServiceModule;

class OtherServiceModule extends ServiceModule implements
    IServiceCreate,
    IServiceAdminManage,
    IServicePurchaseExternal
{
    const MODULE_ID = "other";

    /** @var BoughtServiceService */
    private $boughtServiceService;

    public function __construct(BoughtServiceService $boughtServiceService, Service $service = null)
    {
        parent::__construct($service);
        $this->boughtServiceService = $boughtServiceService;
    }

    public function purchaseDataValidate(Purchase $purchase)
    {
        return new Validator(
            [
                "email" => $purchase->getEmail(),
                "server_id" => $purchase->getOrder(Purchase::ORDER_SERVER),
            ],
            [
                "email" => [new EmailRule()],
                "server_id" => [
                    new RequiredRule(),
                    new ServerExistsRule(),
                    new ServerLinkedToServiceRule($this->service),
                ],
            ]
        );
    }

    public function purchase(Purchase $purchase)
    {
        $promoCode = $purchase->getPromoCode();

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->getAddressIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder("auth_data"),
            $purchase->getEmail(),
            $promoCode ? $promoCode->getCode() : null
        );
    }

    public function serviceAdminManagePost(array $body)
    {
        return [];
    }

    public function serviceAdminExtraFieldsGet()
    {
        return "";
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        //
    }
}
