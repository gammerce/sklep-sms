<?php
namespace Tests\Psr4\Concerns;

use App\Install\SetupManager;
use Mockery;

trait SetupManagerConcern
{
    protected function mockSetupManager()
    {
        $setupManager = Mockery::mock(SetupManager::class);
        $setupManager->shouldReceive("start")->andReturnNull();
        $setupManager->shouldReceive("finish")->andReturnNull();
        $setupManager->shouldReceive("markAsFailed")->andReturnNull();
        $setupManager->shouldReceive("hasFailed")->andReturnFalse();
        $setupManager->shouldReceive("isInProgress")->andReturnFalse();
        $this->app->instance(SetupManager::class, $setupManager);
    }
}
