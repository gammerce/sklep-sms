<?php
namespace App\ServiceModules\Other;

use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\PriceAvailableRule;
use App\Http\Validation\Rules\PriceExistsRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServerLinkedToServiceRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceAvailableOnServers;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\ServiceModules\ServiceModule;

class OtherServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseOutside,
    IServiceCreate,
    IServiceAdminManage,
    IServiceAvailableOnServers
{
    const MODULE_ID = "other";

    /** @var BoughtServiceService */
    private $boughtServiceService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
    }

    public function purchaseDataValidate(Purchase $purchase)
    {
        $price = $purchase->getPrice();

        return new Validator(
            [
                'email' => $purchase->getEmail(),
                'price_id' => $price ? $price->getId() : null,
                'server_id' => $purchase->getOrder(Purchase::ORDER_SERVER),
            ],
            [
                'email' => [new EmailRule()],
                'price_id' => [new PriceExistsRule(), new PriceAvailableRule($this->service)],
                'server_id' => [
                    new RequiredRule(),
                    new ServerExistsRule(),
                    new ServerLinkedToServiceRule($this->service),
                ],
            ]
        );
    }

    public function purchase(Purchase $purchase)
    {
        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder('auth_data'),
            $purchase->getEmail()
        );
    }

    public function serviceAdminManagePost(array $body)
    {
        return [];
    }

    public function serviceAdminExtraFieldsGet()
    {
        return '';
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        //
    }
}
