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
    private PaymentModuleFactory $paymentModuleFactory;
    private PaymentPlatformRepository $paymentPlatformRepository;
    /** @var string[] */
    private array $classes = [];

    public function __construct(
        PaymentModuleFactory $paymentModuleFactory,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->paymentModuleFactory = $paymentModuleFactory;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
    }

    public function register($className): void
    {
        $moduleId = $className::MODULE_ID;

        if (isset($this->classes[$moduleId])) {
            throw new InvalidConfigException(
                "There is a payment api with such an id: [$moduleId] already."
            );
        }

        $this->classes[$moduleId] = $className;
    }

    public function allIds(): array
    {
        return array_keys($this->classes);
    }

    /**
     * @param string $moduleId
     * @return DataField[]
     * @throws InvalidPaymentModuleException
     */
    public function dataFields($moduleId): array
    {
        $className = $this->getClass($moduleId);
        return $className::getDataFields();
    }

    /**
     * @param string $moduleId
     * @return string
     * @throws InvalidPaymentModuleException
     */
    public function getClass($moduleId): string
    {
        $className = array_get($this->classes, $moduleId);

        if ($className) {
            return $className;
        }

        throw new InvalidPaymentModuleException();
    }

    /**
     * @param PaymentPlatform $paymentPlatform
     * @return PaymentModule|null
     */
    public function get(PaymentPlatform $paymentPlatform): ?PaymentModule
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
    public function getByPlatformId($platformId): ?PaymentModule
    {
        $paymentPlatform = $this->paymentPlatformRepository->get($platformId);
        if (!$paymentPlatform) {
            return null;
        }

        return $this->get($paymentPlatform);
    }
}
