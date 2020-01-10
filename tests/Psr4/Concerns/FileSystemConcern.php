<?php
namespace Tests\Psr4\Concerns;

use App\Install\EnvCreator;
use App\Install\SetupManager;
use App\Services\ServiceDescriptionService;
use Tests\Psr4\MemoryFileSystem;

trait FileSystemConcern
{
    protected function mockFileSystem()
    {
        $fileSystem = new MemoryFileSystem();

        $serviceDescriptionService = $this->app->makeWith(
            ServiceDescriptionService::class,
            compact('fileSystem')
        );
        $this->app->instance(ServiceDescriptionService::class, $serviceDescriptionService);

        $envCreator = $this->app->makeWith(EnvCreator::class, compact('fileSystem'));
        $this->app->instance(EnvCreator::class, $envCreator);

        $setupManager = $this->app->makeWith(SetupManager::class, compact('fileSystem'));
        $this->app->instance(SetupManager::class, $setupManager);
    }
}
