<?php
namespace Tests\Psr4\Concerns;

use App\Models\PaymentPlatform;
use App\Payment\PaymentModuleFactory;
use App\Verification\Results\SmsSuccessResult;
use Mockery;
use Mockery\MockInterface;

trait PaymentModuleFactoryConcern
{
    /** @var PaymentModuleFactory|MockInterface */
    private $paymentModuleFactoryMock;

    public function mockPaymentModuleFactory()
    {
        $this->paymentModuleFactoryMock = \Mockery::mock(PaymentModuleFactory::class);
        $this->app->instance(PaymentModuleFactory::class, $this->paymentModuleFactoryMock);
    }

    public function makeVerifySmsSuccessful($paymentModuleClass)
    {
        $this->paymentModuleFactoryMock
            ->shouldReceive('create')
            ->withArgs([$paymentModuleClass, Mockery::any()])
            ->andReturnUsing(function ($className, PaymentPlatform $paymentPlatform) {
                $paymentModule = $this->app->makeWith($className, compact('paymentPlatform'));
                $paymentModuleMock = Mockery::mock($paymentModule)->makePartial();
                $paymentModuleMock->shouldReceive('verifySms')->andReturn(new SmsSuccessResult());
                return $paymentModuleMock;
            });
    }
}
