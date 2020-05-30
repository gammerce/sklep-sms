<?php
namespace Tests\Psr4\Concerns;

use App\ServiceModules\MybbExtraGroups\MybbRepository;
use App\ServiceModules\MybbExtraGroups\MybbRepositoryFactory;
use Mockery;
use Mockery\MockInterface;

trait MybbRepositoryConcern
{
    /** @var MybbRepository|MockInterface */
    public $mybbRepositoryMock;

    public function mockMybbRepository()
    {
        $mybbRepositoryFactory = Mockery::mock(MybbRepositoryFactory::class);
        $this->app->instance(MybbRepositoryFactory::class, $mybbRepositoryFactory);

        $this->mybbRepositoryMock = Mockery::mock(MybbRepository::class);
        $this->mybbRepositoryMock->shouldReceive("connectDb")->andReturnNull();
        $this->mybbRepositoryMock->shouldReceive("getUserByUsername")->andReturn([
            "uid" => 1,
            "additionalgroups" => "1,2",
            "displaygroup" => 1,
            "usergroup" => 1,
        ]);

        $mybbRepositoryFactory
            ->shouldReceive("create")
            ->withArgs(["host", 3306, "user", "password", "name"])
            ->andReturn($this->mybbRepositoryMock);
    }
}
