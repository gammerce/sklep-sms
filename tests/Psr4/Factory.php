<?php
namespace Tests\Psr4;

use App\Repositories\PaymentPlatformRepository;
use App\Repositories\PriceRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
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

    /** @var PriceRepository */
    private $priceRepository;

    public function __construct(
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServiceRepository $serviceRepository,
        PriceRepository $priceRepository,
        ServerServiceRepository $serverServiceRepository,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $this->faker = FakerFactory::create();
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->serverServiceRepository = $serverServiceRepository;
        $this->serviceRepository = $serviceRepository;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->priceRepository = $priceRepository;
    }

    public function server(array $attributes = [])
    {
        $attributes = array_merge(
            [
                'name' => $this->faker->word,
                'ip' => $this->faker->ipv4,
                'port' => $this->faker->numberBetween(1000, 20000),
                'sms_platform' => null,
            ],
            $attributes
        );

        return $this->serverRepository->create(
            $attributes['name'],
            $attributes['ip'],
            $attributes['port'],
            $attributes['sms_platform']
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
                'service_id' => 'gosetti',
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
                    "account_id" => $this->faker->uuid,
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
}
