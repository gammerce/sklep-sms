<?php
namespace Tests\Psr4\Concerns;

use App\Install\EnvCreator;
use App\Install\SetupManager;
use App\Services\ExtraFlags\ServiceDescriptionCreator;
use Tests\Psr4\MemoryFileSystem;

trait FileSystemConcern
{
    protected function mockFileSystem()
    {
        $fileSystem = new MemoryFileSystem();

        $serviceDescriptionCreator = $this->app->makeWith(ServiceDescriptionCreator::class, compact('fileSystem'));
        $this->app->instance(ServiceDescriptionCreator::class, $serviceDescriptionCreator);

        $envCreator = $this->app->makeWith(EnvCreator::class, compact('fileSystem'));
        $this->app->instance(EnvCreator::class, $envCreator);

        $setupManager = $this->app->makeWith(SetupManager::class, compact('fileSystem'));
        $this->app->instance(SetupManager::class, $setupManager);
    }
}
