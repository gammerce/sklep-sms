<?php
namespace Tests\Psr4\Concerns;

use Mockery;

trait MockeryConcern
{
    protected function closeMockery()
    {
        if (class_exists('Mockery')) {
            if ($container = Mockery::getContainer()) {
                $this->addToAssertionCount($container->mockery_getExpectationCount());
            }

            Mockery::close();
        }
    }
}
