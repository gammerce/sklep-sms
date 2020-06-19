<?php
namespace Tests\Psr4\Concerns;

use App\Install\EnvCreator;
use App\Install\OldShop;
use App\Install\SetupManager;
use App\Payment\General\PurchaseDataService;
use App\Services\ServiceDescriptionService;
use Tests\Psr4\MemoryFileSystem;

trait FileSystemConcern
{
    protected function mockFileSystem()
    {
        $fileSystem = new MemoryFileSystem();

        $serviceDescriptionService = $this->app->makeWith(
            ServiceDescriptionService::class,
            compact("fileSystem")
        );
        $this->app->instance(ServiceDescriptionService::class, $serviceDescriptionService);

        $envCreator = $this->app->makeWith(EnvCreator::class, compact("fileSystem"));
        $this->app->instance(EnvCreator::class, $envCreator);

        $oldShop = $this->app->makeWith(SetupManager::class, compact("fileSystem"));
        $this->app->instance(SetupManager::class, $oldShop);

        $oldShop = $this->app->makeWith(OldShop::class, compact("fileSystem"));
        $this->app->instance(OldShop::class, $oldShop);

        $purchaseDataService = $this->app->makeWith(
            PurchaseDataService::class,
            compact("fileSystem")
        );
        $this->app->instance(PurchaseDataService::class, $purchaseDataService);

        return $fileSystem;
    }
}
