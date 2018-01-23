<?php
namespace Tests\Psr4;

use App\Models\Pricelist;
use App\Models\Server;
use App\Models\ServerService;
use Faker\Factory as FakerFactory;
use Faker\Generator;

class Factory
{
    /** @var Generator */
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    public function server(array $attributes = [])
    {
        $attributes = array_merge([
            'name' => $this->faker->sentence,
            'ip'   => $this->faker->ipv4,
            'port' => $this->faker->numberBetween(1000, 20000),
        ], $attributes);

        return Server::create($attributes['name'], $attributes['ip'], $attributes['port']);
    }

    public function serverService(array $attributes = [])
    {
        $attributes = array_merge([
            'service_id' => 'gosetti',
        ], $attributes);

        return ServerService::create($attributes['server_id'], $attributes['service_id']);
    }

    public function pricelist(array $attributes = [])
    {
        $attributes = array_merge([
            'service_id' => 'gosetti',
            'amount'     => $this->faker->numberBetween(1, 100),
        ], $attributes);

        return Pricelist::create(
            $attributes['service_id'],
            $attributes['tariff'],
            $attributes['amount'],
            $attributes['server_id']
        );
    }
}
