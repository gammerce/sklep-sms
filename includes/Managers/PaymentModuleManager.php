<?php
namespace App\Managers;

use App\Exceptions\InvalidConfigException;
use App\Exceptions\InvalidPaymentModuleException;
use App\Models\PaymentPlatform;
use App\Payment\General\PaymentModuleFactory;
use App\Repositories\PaymentPlatformRepository;
use App\Verification\Abstracts\PaymentModule;
use App\Verification\DataField;

class PaymentModuleManager
{
    /** @var PaymentModuleFactory */
    private $paymentModuleFactory;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    private $classes = [];

    public function __construct(
        PaymentModuleFactory $paymentModuleFactory,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->paymentModuleFactory = $paymentModuleFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    public function register($className)
    {
        $moduleId = $className::MODULE_ID;

        if (isset($this->classes[$moduleId])) {
            throw new InvalidConfigException(
                "There is a payment api with such an id: [$moduleId] already."
            );
        }

        $this->classes[$moduleId] = $className;
    }

    public function allIds()
    {
        return array_keys($this->classes);
    }

    /**
     * @param string $moduleId
     * @return DataField[]
     */
    public function dataFields($moduleId)
    {
        $className = array_get($this->classes, $moduleId);

        if ($className) {
            return $className::getDataFields();
        }

        throw new InvalidPaymentModuleException();
    }

    /**
     * @param PaymentPlatform $paymentPlatform
     * @return PaymentModule|null
     */
    public function get(PaymentPlatform $paymentPlatform)
    {
        $paymentModuleClass = array_get($this->classes, $paymentPlatform->getModuleId());

        if ($paymentModuleClass) {
            return $this->paymentModuleFactory->create($paymentModuleClass, $paymentPlatform);
        }

        return null;
    }

    /**
     * @param string $platformId
     * @return PaymentModule|null
     */
    public function getByPlatformId($platformId)
    {
        $paymentPlatform = $this->paymentPlatformRepository->get($platformId);
        if (!$paymentPlatform) {
            return null;
        }

        return $this->get($paymentPlatform);
    }
}
