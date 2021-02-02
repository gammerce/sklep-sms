<?php
namespace Tests\Psr4\Concerns;

use App\Models\PaymentPlatform;
use App\Payment\General\PaymentModuleFactory;
use App\Verification\Exceptions\BadCodeException;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Mockery\MockInterface;

trait PaymentModuleFactoryConcern
{
    /** @var PaymentModuleFactory|MockInterface */
    private $paymentModuleFactoryMock;

    public function mockPaymentModuleFactory(): void
    {
        $this->paymentModuleFactoryMock = Mockery::mock(PaymentModuleFactory::class);
        $this->app->instance(PaymentModuleFactory::class, $this->paymentModuleFactoryMock);
    }

    public function makeVerifySmsSuccessful($paymentModuleClass): void
    {
        $this->paymentModuleFactoryMock
            ->shouldReceive("create")
            ->withArgs([$paymentModuleClass, Mockery::any()])
            ->andReturnUsing(function ($className, PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->app->makeWith($className, compact("paymentPlatform"));
                $paymentModuleMock = Mockery::mock($paymentModule)->makePartial();
                $paymentModuleMock->shouldReceive("verifySms")->andReturn(new SmsSuccessResult());
                return $paymentModuleMock;
            });
    }

    public function makeVerifySmsUnsuccessful($paymentModuleClass): void
    {
        $this->paymentModuleFactoryMock
            ->shouldReceive("create")
            ->withArgs([$paymentModuleClass, Mockery::any()])
            ->andReturnUsing(function ($className, PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->app->makeWith($className, compact("paymentPlatform"));
                $paymentModuleMock = Mockery::mock($paymentModule)->makePartial();
                $paymentModuleMock->shouldReceive("verifySms")->andThrow(new BadCodeException());
                return $paymentModuleMock;
            });
    }
}
