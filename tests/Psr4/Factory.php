<?php
namespace Tests\Psr4;

use App\Models\Group;
use App\Models\PaymentPlatform;
use App\Models\Price;
use App\Models\PromoCode;
use App\Models\Server;
use App\Models\ServerService;
use App\Models\Service;
use App\Models\SmsCode;
use App\Models\User;
use App\PromoCode\QuantityType;
use App\Repositories\GroupRepository;
use App\Repositories\LogRepository;
use App\Repositories\PaymentPlatformRepository;
use App\Repositories\PriceRepository;
use App\Repositories\PromoCodeRepository;
use App\Repositories\ServerRepository;
use App\Repositories\ServerServiceRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\SmsCodeRepository;
use App\Repositories\UserRepository;
use App\ServiceModules\ExtraFlags\ExtraFlagsServiceModule;
use App\ServiceModules\ExtraFlags\ExtraFlagType;
use App\ServiceModules\ExtraFlags\ExtraFlagUserService;
use App\ServiceModules\ExtraFlags\ExtraFlagUserServiceRepository;
use App\ServiceModules\MybbExtraGroups\MybbExtraGroupsServiceModule;
use App\ServiceModules\MybbExtraGroups\MybbUserService;
use App\ServiceModules\MybbExtraGroups\MybbUserServiceRepository;
use App\Support\Money;
use App\Verification\PaymentModules\Cssetti;
use Faker\Factory as FakerFactory;
use Faker\Generator;

function resolve($value)
{
    if (is_callable($value)) {
        return call_user_func($value);
    }

    return $value;
}

class Factory
{
    private Generator $faker;
    private UserRepository $userRepository;
    private ServerRepository $serverRepository;
    private ServerServiceRepository $serverServiceRepository;
    private ServiceRepository $serviceRepository;
    private PaymentPlatformRepository $paymentPlatformRepository;
    private PromoCodeRepository $promoCodeRepository;
    private PriceRepository $priceRepository;
    private LogRepository $logRepository;
    private SmsCodeRepository $smsCodeRepository;
    private ExtraFlagUserServiceRepository $extraFlagUserServiceRepository;
    private MybbUserServiceRepository $mybbUserServiceRepository;
    private GroupRepository $groupRepository;

    public function __construct(
        UserRepository $userRepository,
        ServerRepository $serverRepository,
        ServiceRepository $serviceRepository,
        PriceRepository $priceRepository,
        ServerServiceRepository $serverServiceRepository,
        PaymentPlatformRepository $paymentPlatformRepository,
        PromoCodeRepository $promoCodeRepository,
        LogRepository $logRepository,
        SmsCodeRepository $smsCodeRepository,
        ExtraFlagUserServiceRepository $extraFlagUserServiceRepository,
        MybbUserServiceRepository $mybbUserServiceRepository,
        GroupRepository $groupRepository
    ) {
        $this->faker = FakerFactory::create();
        $this->userRepository = $userRepository;
        $this->serverRepository = $serverRepository;
        $this->serverServiceRepository = $serverServiceRepository;
        $this->serviceRepository = $serviceRepository;
        $this->paymentPlatformRepository = $paymentPlatformRepository;
        $this->priceRepository = $priceRepository;
        $this->promoCodeRepository = $promoCodeRepository;
        $this->logRepository = $logRepository;
        $this->smsCodeRepository = $smsCodeRepository;
        $this->extraFlagUserServiceRepository = $extraFlagUserServiceRepository;
        $this->mybbUserServiceRepository = $mybbUserServiceRepository;
        $this->groupRepository = $groupRepository;
    }

    public function server(array $attributes = []): Server
    {
        $attributes = array_merge(
            [
                "name" => $this->faker->word,
                "ip" => $this->faker->ipv4,
                "port" => $this->faker->numberBetween(1000, 20000),
                "sms_platform_id" => null,
                "transfer_platform_ids" => [],
            ],
            $attributes
        );

        return $this->serverRepository->create(
            $attributes["name"],
            $attributes["ip"],
            $attributes["port"],
            $attributes["sms_platform_id"],
            $attributes["transfer_platform_ids"]
        );
    }

    public function service(array $attributes = []): Service
    {
        $attributes = merge_recursive(
            [
                "data" => [],
                "description" => $this->faker->sentence,
                "groups" => [],
                "id" => strtolower($this->faker->word),
                "module" => null,
                "name" => $this->faker->word,
                "order" => 1,
                "short_description" => $this->faker->word,
                "tag" => $this->faker->word,
            ],
            $attributes
        );

        return $this->serviceRepository->create(
            $attributes["id"],
            $attributes["name"],
            $attributes["short_description"],
            $attributes["description"],
            $attributes["tag"],
            $attributes["module"],
            $attributes["groups"],
            $attributes["order"],
            $attributes["data"]
        );
    }

    public function extraFlagService(array $attributes = []): Service
    {
        return $this->service(
            merge_recursive(
                [
                    "module" => ExtraFlagsServiceModule::MODULE_ID,
                ],
                $attributes
            )
        );
    }

    public function mybbService(array $attributes = []): Service
    {
        return $this->service(
            merge_recursive(
                [
                    "module" => MybbExtraGroupsServiceModule::MODULE_ID,
                    "data" => [
                        "db_host" => "host",
                        "db_name" => "name",
                        "db_password" => "password",
                        "db_user" => "user",
                        "mybb_groups" => "1",
                    ],
                ],
                $attributes
            )
        );
    }

    public function serverService(array $attributes = []): ServerService
    {
        $attributes = array_merge(
            [
                "service_id" => "vip",
            ],
            $attributes
        );

        return $this->serverServiceRepository->create(
            $attributes["server_id"],
            $attributes["service_id"]
        );
    }

    public function price(array $attributes = []): Price
    {
        $attributes = array_merge(
            [
                "direct_billing_price" => 15,
                "discount" => null,
                "service_id" => "vip",
                "server_id" => null,
                "sms_price" => 100,
                "transfer_price" => null,
                "quantity" => $this->faker->numberBetween(1, 100),
            ],
            $attributes
        );

        return $this->priceRepository->create(
            $attributes["service_id"],
            $attributes["server_id"],
            $attributes["sms_price"],
            $attributes["transfer_price"],
            $attributes["direct_billing_price"],
            $attributes["quantity"],
            $attributes["discount"]
        );
    }

    public function admin(array $attributes = []): User
    {
        return $this->user(array_merge(["groups" => "2"], $attributes));
    }

    public function user(array $attributes = []): User
    {
        $attributes = array_merge(
            [
                "username" => $this->faker->userName,
                "password" => $this->faker->password,
                "email" => $this->faker->email,
                "forename" => $this->faker->firstName,
                "surname" => $this->faker->lastName,
                "steam_id" => "",
                "ip" => $this->faker->ipv4,
                "groups" => "1",
                "wallet" => 0,
            ],
            $attributes
        );

        return $this->userRepository->create(
            $attributes["username"],
            $attributes["password"],
            $attributes["email"],
            $attributes["forename"],
            $attributes["surname"],
            $attributes["steam_id"],
            $attributes["ip"],
            $attributes["groups"],
            $attributes["wallet"]
        );
    }

    public function paymentPlatform(array $attributes = []): PaymentPlatform
    {
        $attributes = array_merge(
            [
                "name" => $this->faker->word,
                "module" => Cssetti::MODULE_ID,
                "data" => [
                    "account_id" => "5b2f-30ea-b814-3585710",
                ],
            ],
            $attributes
        );

        return $this->paymentPlatformRepository->create(
            $attributes["name"],
            $attributes["module"],
            $attributes["data"]
        );
    }

    public function promoCode(array $attributes = []): PromoCode
    {
        $attributes = array_merge(
            [
                "code" => $this->faker->word,
                "expires_at" => null,
                "quantity_type" => QuantityType::PERCENTAGE(),
                "quantity" => 30,
                "server_id" => null,
                "service_id" => "vip",
                "usage_limit" => null,
                "user_id" => null,
            ],
            $attributes
        );

        return $this->promoCodeRepository->create(
            $attributes["code"],
            $attributes["quantity_type"],
            $attributes["quantity"],
            $attributes["usage_limit"],
            $attributes["expires_at"],
            $attributes["service_id"],
            $attributes["server_id"],
            $attributes["user_id"]
        );
    }

    public function log(array $attributes = [])
    {
        $attributes = array_merge(
            [
                "text" => $this->faker->sentence,
            ],
            $attributes
        );

        return $this->logRepository->create($attributes["text"]);
    }

    public function smsCode(array $attributes = []): SmsCode
    {
        $attributes = array_merge(
            [
                "code" => $this->faker->word,
                "sms_price" => 100,
                "free" => false,
                "expires" => null,
            ],
            $attributes
        );

        return $this->smsCodeRepository->create(
            $attributes["code"],
            new Money($attributes["sms_price"]),
            $attributes["free"],
            $attributes["expires"]
        );
    }

    public function extraFlagUserService(array $attributes = []): ExtraFlagUserService
    {
        $attributes = array_merge(
            [
                "service_id" => "vip",
                "user_id" => null,
                "seconds" => 35 * 24 * 60 * 60,
                "server_id" => null,
                "type" => ExtraFlagType::TYPE_NICK,
                "auth_data" => "my_nickname",
                "password" => "pokll12",
            ],
            $attributes
        );

        return $this->extraFlagUserServiceRepository->create(
            $attributes["service_id"],
            $attributes["user_id"],
            $attributes["seconds"],
            $attributes["server_id"],
            $attributes["type"],
            $attributes["auth_data"],
            $attributes["password"]
        );
    }

    public function mybbUserService(array $attributes = []): MybbUserService
    {
        $attributes = array_merge(
            [
                "service_id" => fn() => $this->mybbService()->getId(),
                "user_id" => null,
                "seconds" => 35 * 24 * 60 * 60,
                "mybb_uid" => 1,
            ],
            $attributes
        );

        return $this->mybbUserServiceRepository->create(
            resolve($attributes["service_id"]),
            $attributes["user_id"],
            $attributes["seconds"],
            $attributes["mybb_uid"]
        );
    }

    public function group(array $attributes = []): Group
    {
        $attributes = array_merge(
            [
                "name" => $this->faker->word,
            ],
            $attributes
        );

        return $this->groupRepository->create($attributes["name"], $attributes);
    }
}
