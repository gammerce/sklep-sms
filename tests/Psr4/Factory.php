<?php
namespace Tests\Psr4;

use App\Repositories\LogRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\PriceRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceCodeRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\SmsCodeRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\Verification\PaymentModules\Cssetti;
use Faker\Factory as FakerFactory;
use Faker\Generator;

class Factory
{
    /** @var Generator */
    private $faker;

    /** @var UserRepository */
    private $userRepository;

    /** @var ServerRepository */
    private $serverRepository;

    /** @var ServerServiceRepository */
    private $serverServiceRepository;

    /** @var ServiceRepository */
    private $serviceRepository;

    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    /** @var ServiceCodeRepository */
    private $serviceCodeRepository;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var LogRepository */
    private $logRepository;

    /** @var SmsCodeRepository */
    private $smsCodeRepository;

    public function __construct(
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServiceRepository $serviceRepository,
        PriceRepository $priceRepository,
        ServerServiceRepository $serverServiceRepository,
        PaymentPlatformRepository $paymentPlatformRepository,
        ServiceCodeRepository $serviceCodeRepository,
        LogRepository $logRepository,
        SmsCodeRepository $smsCodeRepository
    ) {
        $this->faker = FakerFactory::create();
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->serverServiceRepository = $serverServiceRepository;
        $this->serviceRepository = $serviceRepository;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->priceRepository = $priceRepository;
        $this->serviceCodeRepository = $serviceCodeRepository;
        $this->logRepository = $logRepository;
        $this->smsCodeRepository = $smsCodeRepository;
    }

    public function server(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'name' => $this->faker->word,
                'ip' => $this->faker->ipv4,
                'port' => $this->faker->numberBetween(1000, 20000),
                'sms_platform_id' => null,
            ],
            $attributes
        );

        return $this->serverRepository->create(
            $attributes['name'],
            $attributes['ip'],
            $attributes['port'],
            $attributes['sms_platform_id']
        );
    }

    public function service(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'id' => strtolower($this->faker->word),
                'name' => $this->faker->word,
                'short_description' => $this->faker->word,
                'description' => $this->faker->sentence,
                'tag' => $this->faker->word,
                'module' => ExtraFlagsServiceModule::MODULE_ID,
                'groups' => [],
                'order' => 1,
            ],
            $attributes
        );

        return $this->serviceRepository->create(
            $attributes['id'],
            $attributes['name'],
            $attributes['short_description'],
            $attributes['description'],
            $attributes['tag'],
            $attributes['module'],
            $attributes['groups'],
            $attributes['order']
        );
    }

    public function serverService(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'service_id' => 'vip',
            ],
            $attributes
        );

        return $this->serverServiceRepository->create(
            $attributes['server_id'],
            $attributes['service_id']
        );
    }

    public function price(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'service_id' => 'vip',
                'server_id' => null,
                'sms_price' => 100,
                'transfer_price' => null,
                'quantity' => $this->faker->numberBetween(1, 100),
            ],
            $attributes
        );

        return $this->priceRepository->create(
            $attributes['service_id'],
            $attributes['server_id'],
            $attributes['sms_price'],
            $attributes['transfer_price'],
            $attributes['quantity']
        );
    }

    public function admin(array $attributes = [])
    {
        return $this->user(array_merge(["groups" => "2"], $attributes));
    }

    public function user(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'username' => $this->faker->userName,
                'password' => $this->faker->password,
                'email' => $this->faker->email,
                'forename' => $this->faker->firstName,
                'surname' => $this->faker->lastName,
                'steam_id' => '',
                'ip' => $this->faker->ipv4,
                'groups' => '1',
                'wallet' => 0,
            ],
            $attributes
        );

        return $this->userRepository->create(
            $attributes['username'],
            $attributes['password'],
            $attributes['email'],
            $attributes['forename'],
            $attributes['surname'],
            $attributes['steam_id'],
            $attributes['ip'],
            $attributes['groups'],
            $attributes['wallet']
        );
    }

    public function paymentPlatform(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'name' => $this->faker->word,
                'module' => Cssetti::MODULE_ID,
                'data' => [
                    "account_id" => "5b2f-30ea-b814-3585710",
                ],
            ],
            $attributes
        );

        return $this->paymentPlatformRepository->create(
            $attributes['name'],
            $attributes['module'],
            $attributes['data']
        );
    }

    public function serviceCode(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'code' => $this->faker->word,
                'service_id' => 'vip',
                'server_id' => null,
                'uid' => null,
            ],
            $attributes
        );

        return $this->serviceCodeRepository->create(
            $attributes['code'],
            $attributes['service_id'],
            $attributes['price_id'],
            $attributes['server_id'],
            $attributes['uid']
        );
    }

    public function log(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'text' => $this->faker->sentence,
            ],
            $attributes
        );

        $this->logRepository->create($attributes['text']);
    }

    public function smsCode(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'code' => $this->faker->word,
                'sms_price' => 100,
                'free' => false,
            ],
            $attributes
        );

        $this->smsCodeRepository->create(
            $attributes['code'],
            $attributes['sms_price'],
            $attributes['free']
        );
    }
}
